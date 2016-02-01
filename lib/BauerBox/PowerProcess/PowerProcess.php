<?php

/**
 * This file is a part of the PowerProcess package for PHP by BauerBox Labs
 *
 * @copyright
 * Copyright (c) 2013 Don Bauer <lordgnu@me.com> BauerBox Labs
 *
 * @license https://github.com/lordgnu/PowerProcess/blob/master/LICENSE MIT License
 */

namespace BauerBox\PowerProcess;

use BauerBox\PowerProcess\Exception\ProcessForkException;
use BauerBox\PowerProcess\Job\Job;
use BauerBox\PowerProcess\Log\AbstractLogger;
use BauerBox\PowerProcess\Log\NullLogger;
use BauerBox\PowerProcess\Posix\Identification;
use BauerBox\PowerProcess\Posix\Signals;
use BauerBox\PowerProcess\Util\SystemInfo;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * PowerProcess is an abstraction class for PHP's posix and pcntl extensions.
 *
 * It enables easy process forking or threading to allow use of parallel
 * processes for completing complicated tasks that would otherwise be
 * inefficient for normal serial and/or procedural processing
 *
 * @author Don Bauer <lordgnu@me.com>
 */
class PowerProcess implements LoggerAwareInterface
{
    /* Version Constants */
    const VERSION         = '3.0.0-alpha1';
    const VERSION_ID      = '30000';
    const MAJOR_VERSION   = '3';
    const MINOR_VERSION   = '0';
    const RELEASE_VERSION = '0';
    const EXTRA_VERSION   = 'alpha1';

    /* Callback Constants (Bitwise-combination supported) */
    const CALLBACK_IGNORE           =   0;
    const CALLBACK_CONTINUE         =   1;
    const CALLBACK_SHUTDOWN         =   2;  // Request that the control process shutdown
    const CALLBACK_RESTART          =   4;  // Request that the control process restart
    const CALLBACK_REMOVE           =   8;  // Remove this callback from the stack (1-shot)
    const CALLBACK_STOP_PROPOGATION =   16; // Prevent remaining callbacks from being executed

    /**
     * Public debug logging constant
     *
     * @var bool
     */
    public static $debugLogging     =   false;

    /**
     * Callback spawn counter for unique ID generation
     *
     * @var int
     */
    protected static $callbackCounter;

    /**
     * @var array
     */
    protected $aliasSignals = [
        'SIG_PRE_FORK'          =>  1001,
        'SIG_POST_FORK'         =>  1002,
        'SIG_PRE_DAEMONIZE'     =>  1003,
        'SIG_POST_DAEMONIZE'    =>  1004,
        'SIG_JOB_TIME_OVER'     =>  1005,
        'SIG_JOB_COMPLETE'      =>  1006,
        'SIG_PRE_SHUTDOWN'      =>  1007,
        'SIG_POST_SHUTDOWN'     =>  1008,
        'SIG_PRE_RESTART'       =>  1009,
        'SIG_POST_RESTART'      =>  1010
    ];

    /**
     * @var array|callable[]
     */
    private $callbacks;

    /**
     * @var bool
     */
    private $continue;

    /**
     * @var int
     */
    private $parentProcessId;

    /**
     * @var int
     */
    private $parentSessionId;

    /**
     * @var int
     */
    private $processId;

    /**
     * User-Set Process Name (Or Auto Generated)
     *
     * @var string|null
     */
    private $processName;

    /**
     * @var bool
     */
    private $ready = false;

    /**
     * Windows Flag
     *
     * @var boolean
     */
    private $isWindows;

    /**
     * Number of uSeconds to wait between ticks
     *
     * @var int
     */
    private $tickCounter = 100;

    /**
     * @var int
     */
    private $maxJobs;

    /**
     * @var int
     */
    private $maxJobTime;

    /**
     * @var AbstractLogger
     */
    private $logger;

    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var array
     */
    private $jobs;

    /**
     * Signals the have been registered with the PCNTL dispatcher
     *
     * @var array
     */
    private $signalsRegistered;

    /**
     * @var SystemInfo
     */
    private $sysInfo;

    /**
     * @param null $maxJobs
     * @param int $maxJobTime
     * @throws \Exception
     */
    public function __construct($maxJobs = null, $maxJobTime = 300)
    {
        // Set Windows Flag
        // TODO: Remove Hard Code
        $this->isWindows = false;

        // Get the System Info
        $this->sysInfo = new SystemInfo();

        // If maxJobs wasn't passed, determine the correct count based on system settings
        if (null === $maxJobs) {
            $mMax = round(
                ($this->sysInfo->getMemoryInfo()->getMemory() / $this->sysInfo->getMemoryInfo()->getPhpMemoryLimit()),
                0,
                PHP_ROUND_HALF_DOWN
            );

            $cMax = $this->sysInfo->getCpuInfo()->logicalCores;
            $this->maxJobs = ($cMax < $mMax) ? $cMax : $mMax;
        }

        if (true === $this->isWindows) {
            throw new \Exception(
                'PowerProcess is currently not available on the Windows operating system'
            );
        } else {
            // Check for pcntl and posix extensions
            if (false === extension_loaded('posix') || false === extension_loaded('pcntl')) {
                throw new \Exception(
                    'PowerProcess requires the posix and pcntl extensions to operate'
                );
            }
        }

        // Check for OS X
        if ($this->sysInfo->getOs() == 'Darwin') {
            // Disable GC (Helps prevent memory heap corruption errors)
            gc_disable();
        }

        // Setup a null logger just in case
        $this->setLogger(new NullLogger());

        // Install signal constants
        Signals::installConstants();

        // Set limits
        $this->setMaxJobs($maxJobs)->setMaxJobTime($maxJobTime);

        // Install signal handler
        $this->installSignalHandler();

        // Set Ready Flag
        $this->ready = true;
    }

    /**
     * Destructor
     *
     * Runs final tick and cleans up memory before detaching logger
     */
    public function __destruct()
    {
        // Process any remaining signals
        $this->tick();

        // Free Up Some Memory
        unset($this->jobs);
        unset($this->callbacks);

        // Detach the logger
        $this->removeLogger();
    }

    /**
     * @return int
     */
    public function complete()
    {
        // Check if a callback or anything else has overridden the exit code
        if ($this->exitCode === -1) {
            $this->logger->info('Exit aborted!');
            $this->exitCode = null;
            return static::CALLBACK_REMOVE;
        }

        exit((null === $this->exitCode) ? 0 : $this->exitCode);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function daemonize()
    {
        if (false === $this->ready) {
            throw new \LogicException('Daemonization can only occur prior to start() or daemonize()');
        }

        $this->logger->debug('Attempting to daemonize');

        $processId = pcntl_fork();

        if ($processId !== 0) {
            exit(0);
        }

        // Setup new identification
        $this->parentSessionId = Identification::getSessionId();
        $this->parentProcessId = Identification::getProcessId();
        $this->processId = Identification::getProcessId();

        if ($this->parentSessionId > 0) {
            $this->logger->info(
                'Daemonization successful!'
            );
            $this->logger->debug(
                'Parent PID: ' . $this->parentProcessId
            );
            $this->logger->debug(
                'Parent SID: ' . $this->parentSessionId
            );

            return $this->start();
        }

        throw new \Exception('There was an error when attempting to daemonize');
    }

    /**
     * @param $process
     * @param null $arguments
     * @param null $environmentVariables
     */
    public function exec($process, $arguments = null, $environmentVariables = null)
    {
        $this->logger->info("Running Exec(): {$process}");

        if (null === $environmentVariables) {
            pcntl_exec($process, $arguments);
        } else {
            pcntl_exec($process, $arguments, $environmentVariables);
        }
    }

    /**
     * @return mixed
     */
    public function getNumberOfProcessors()
    {
        return $this->sysInfo->getCpuInfo()->logicalCores;
    }

    /**
     * @return mixed
     */
    public function getOperatingSystemInformation()
    {
        return $this->sysInfo->getOsVersion();
    }

    /**
     * @return int
     */
    public function getRunningJobCount()
    {
        return count($this->jobs);
    }

    /**
     * @return mixed
     */
    public function getRunningJobs()
    {
        $this->tick();

        return $this->jobs;
    }

    /**
     * @param $jobName
     * @return bool
     */
    public function getJobStatus($jobName)
    {
        $this->tick();

        if (array_key_exists($jobName, $this->jobs)) {
            return $this->jobs[$jobName];
        }

        return false;
    }

    /**
     * Get the currently installed logger
     *
     * @return AbstractLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $signal
     * @throws \Exception
     */
    public function handleSignal($signal)
    {
        // Get signal name
        $signalName = Signals::signalName($signal);

        // Debug Logging
        $this->logger->debug("Received signal {$signalName} ($signal)");

        // Check the stack for callbacks installed for this signal
        if (true === array_key_exists($signal, $this->callbacks) &&
            is_array($this->callbacks[$signal]) &&
            0 < count($this->callbacks[$signal])
        ) {
            // Assign the callback stack
            $callStack =& $this->callbacks[$signal];

            // Build the args if more than one
            $arguments = array();
            if (func_num_args() > 1) {
                for ($i = 1; $i < func_num_args(); ++$i) {
                    $arguments[] = func_get_arg($i);
                }
            }

            // Sort on priority (index[0])
            $priorityStack = array();
            foreach ($callStack as $index => &$callback) {
                $priorityStack[$index] = $callback[0];
            }
            arsort($priorityStack, SORT_NUMERIC);

            // Set Propogation Flag
            $propogate = self::CALLBACK_CONTINUE;

            // TODO: Actually use $priority and prioritize
            foreach ($priorityStack as $callIndex => $priority) {
                if (self::CALLBACK_CONTINUE !== $propogate) {
                    $this->logger->debug('Propogation has been stopped for remaining callbacks for ' . $signalName);
                    break;
                }

                $callback =& $callStack[$callIndex][1];
                $callbackName =& $callStack[$callIndex][2];

                if (true === is_callable($callback)) {
                    $status = call_user_func_array($callback, $arguments);

                    switch ($status) {
                        case self::CALLBACK_IGNORE:
                            $this->logger->debug('Callback ' . $callbackName . ' returned IGNORE status');
                            // IGNORE will fall-through to CONTINUE but write to the log
                        case self::CALLBACK_CONTINUE:
                            break;
                        case self::CALLBACK_REMOVE:
                            $this->logger->debug('Removing callback ' . $callbackName . ' from stack');
                            $this->removeCallback($signal, $callIndex);
                            break;
                        case self::CALLBACK_STOP_PROPOGATION:
                            $this->logger->debug('Callback ' . $callbackName . ' has requested halting of propogation');
                            $propogate = self::CALLBACK_STOP_PROPOGATION;
                            break;
                        case self::CALLBACK_RESTART:
                            $this->logger->debug('Callback ' . $callbackName . ' has requested a restart');
                            $propogate = self::CALLBACK_RESTART;
                            break;
                        case self::CALLBACK_SHUTDOWN:
                            $this->logger->debug('Callback ' . $callbackName . ' has requested a shutdown');
                            $propogate = self::CALLBACK_SHUTDOWN;
                            break;
                        default:
                            $this->logger->error('Callback ' . $callbackName . ' did not return a status constant');
                            break;
                    }
                }
            }

            // Check for restart or shutdown
            if (self::CALLBACK_RESTART === $propogate) {
                $this->logger->debug('Triggering RESTART');
                $this->restart();
            } elseif (self::CALLBACK_SHUTDOWN === $propogate) {
                $this->logger->debug('Triggering SHUTDOWN');
                $this->shutdown();
            }
        }
    }

    /**
     * @return bool
     */
    public function isDaemon()
    {
        return $this->parentSessionId !== false;
    }

    /**
     * @return bool
     */
    public function isJobProcess()
    {
        return $this->parentProcessId !== $this->processId;
    }

    /**
     * @return bool
     */
    public function isParentProcess()
    {
        return $this->parentProcessId === $this->processId;
    }

    /**
     * @param $processId
     * @param null $status
     * @return bool
     */
    public function isProcessRunning($processId, &$status = null)
    {
        return (0 === pcntl_waitpid($processId, $status, WUNTRACED | WNOHANG));
    }

    /**
     * @return bool
     */
    public function isReadyToSpawn()
    {
        $this->tick();
        return ($this->getRunningJobCount() < $this->maxJobs);
    }

    /**
     *
     */
    public function log()
    {
        if (false === $this->logger instanceof AbstractLogger) {
            return;
        }

        for ($i = 0; $i < func_num_args(); ++$i) {
            $this->logger->info(func_get_arg($i));
            $this->logger->warning('Deprecated call to PowerProcess::log() -- User PowerProcess::getLogger() instead');
        }
    }

    /**
     * @param $signal
     * @param callable $callback
     * @param null $name
     * @param int $priority
     * @return PowerProcess
     * @throws \Exception
     */
    public function registerCallback($signal, callable $callback, $name = null, $priority = 0)
    {
        if (true === $this->ready) {
            if ($priority > 255) {
                $priority = 255;
            }

            if ($priority < -255) {
                $priority = -255;
            }
        }

        if (true === is_string($signal) && true === array_key_exists($signal, $this->aliasSignals)) {
            $signal = $this->aliasSignals[$signal];
        }

        if (false === Signals::isValidSignal($signal)) {
            throw new \Exception('Invalid signal: ' . $signal);
        }

        return $this->addCallbackToStack($signal, $callback, $name, $priority);
    }

    /**
     * @param $signal
     * @param $callIndex
     * @throws \Exception
     */
    public function removeCallback($signal, $callIndex)
    {
        // Check to make sure there are callbacks registered
        if (false === array_key_exists($signal, $this->callbacks) || false === is_array($this->callbacks[$signal])) {
            throw new \Exception('Attempted to remove callback, but none registered!');
        }

        // Check to make sure that this callback is registered
        if (false === array_key_exists($callIndex, $this->callbacks[$signal])) {
            throw new \Exception('The requested callback can not be removed because it has already been removed');
        }

        // It is safe to remove
        unset($this->callbacks[$signal][$callIndex]);

        // Check if the signal needs to be blocked again
        if ($signal < 1000 && count($this->callbacks[$signal]) < 1) {
            $this->logger->debug('Blocking signal ' . Signals::signalName($signal));

            // Block the signal
            pcntl_sigprocmask(SIG_BLOCK, array($signal));
        }
    }

    /**
     * @return int
     */
    public function restart()
    {
        $this->logger->debug('Initializing restart sequence');

        if (true === array_key_exists('_', $_SERVER)) {
            $command = $_SERVER['_'];
            if (true === array_key_exists('argv', $_SERVER) && count($_SERVER['argv']) > 0) {
                if ($command === $_SERVER['argv'][0]) {
                    unset($_SERVER['argv'][0]);
                }
            }
        } elseif (true === array_key_exists('argv', $_SERVER) && count($_SERVER['argv']) > 0) {
            $command = $_SERVER['argv'][0];
        } else {
            $this->logger->debug('Restart not possible, resoring to shutdown instead');
            return $this->shutdown();
        }

        while ($this->getRunningJobCount() > 0) {
            $this->tick();
        }

        $this->logger->debug('Preparing restart using command: ' . $command);
        $this->logger->debug('Command arguments follow as print_r dump', print_r($_SERVER['argv'], true));

        $this->exec($command, $_SERVER['argv']);
        $this->shutdown();
        exit(0);
    }

    /**
     * @return bool
     */
    public function runLoop()
    {
        // Tick
        $this->tick();

        // Return
        return (true === $this->continue && $this->isParentProcess());
    }

    /**
     * @param $seconds
     */
    public function safeSleep($seconds)
    {
        $stop = strtotime('+' . $seconds . ' seconds');
        $resetTick = $this->tickCounter;
        $this->tickCounter = 500;

        while (time() < $stop) {
            $this->tick();
        }

        $this->tickCounter = $resetTick;
    }

    /**
     * @param int $signal
     * @param null $processId
     * @return bool
     */
    public function sendSignal($signal = SIGUSR1, $processId = null)
    {
        if ($signal > 1000) {
            return true;
        }

        return Signals::sendSignal($signal, $processId);
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->warning('Another logger is being installed without this one being removed!');
            $this->removeLogger();
        }

        $this->logger = $logger;

        if ($logger instanceof AbstractLogger) {
            $this->logger->setJobName($this->processName === null ? 'CONTROL' : $this->processName);
        }

        $this->logger->debug('Logger Installed');

        return $this;
    }

    /**
     * @param int $maxJobs
     * @return $this
     */
    public function setMaxJobs($maxJobs = 10)
    {
        $this->logger->debug('Setting max jobs to: ' . $maxJobs);
        $this->maxJobs = $maxJobs;
        return $this;
    }

    /**
     * @param int $maxJobTime
     * @return $this
     */
    public function setMaxJobTime($maxJobTime = 300)
    {
        $this->logger->debug('Setting max job time to: ' . $maxJobTime);
        $this->maxJobTime = $maxJobTime;
        return $this;
    }

    /**
     * @param null $exitCode
     * @return int
     */
    public function shutdown($exitCode = null)
    {
        if ($this->isParentProcess()) {
            $this->logger->debug('Starting shutdown sequence');
            $this->handleSignal($this->aliasSignals['SIG_PRE_SHUTDOWN']);

            if (true === is_int($exitCode)) {
                $this->exitCode = $exitCode;
                $this->addCallbackToStack(
                    $this->aliasSignals['SIG_POST_SHUTDOWN'],
                    array($this, 'complete'),
                    'Shutdown Exit Handler',
                    -1024
                );
            }

            while ($this->getRunningJobCount() > 0) {
                $this->tick();
            }
        } else {
            exit((null === $exitCode) ? 0 : $exitCode);
        }

        // Log and handle signals
        $this->logger->info('Shutdown sequence complete');
        $this->handleSignal($this->aliasSignals['SIG_POST_SHUTDOWN']);

        // Reset Flags
        $this->continue = false;
        $this->ready = true;

        return self::CALLBACK_IGNORE;
    }

    /**
     * @param null $name
     * @param null $processId
     * @return bool
     * @throws \Exception
     */
    public function spawnJob($name = null, &$processId = null)
    {
        // Make sure we are ready to spawn
        if (false === $this->isReadyToSpawn()) {
            $this->logger->debug("Can't spawn new job, already at limit of {$this->maxJobs}");
            return false;
        }

        // Make sure we don't already have a job with this name running
        if ($name !== null && true === array_key_exists($name, $this->jobs)) {
            $this->logger->debug("A process with the name '{$name}' it already running!");
            return false;
        }

        // Init job now so IDs match between Job and Control
        $job = new Job(null, $name);

        // Run Pre-Fork Callback
        $this->handleSignal($this->aliasSignals['SIG_PRE_FORK']);

        // Fork
        $processId = pcntl_fork();

        // Check for error
        if ($processId === -1) {
            // Error in forking (We are the Control process)
            throw new ProcessForkException(
                'Attempt to fork process failed while spawning job: ' . $job->getJobName()
            );
        }

        if ($processId > 0) {
            // We are the Control process and the forking completed successfully :: Add new JOB to stack
            $job->setProcessId($processId);
            $this->jobs[$job->getJobName()] = $job;
            $this->logger->debug("Job {$job} spawned successfully");

            // Run POST-FORK Callback
            $this->handleSignal($this->aliasSignals['SIG_POST_FORK']);
        } else {
            // We are the new Job process
            $this->processId = Identification::getProcessId();
            $this->processName = $job->getJobName();
            $this->logger->setJobName($this->processName);

            // Run POST-FORK Callback
            $this->handleSignal($this->aliasSignals['SIG_POST_FORK']);

            // Remove callbacks
            $this->jobs = array();
        }

        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function start()
    {
        if (true === $this->ready) {
            $this->startup();
            return true;
        }

        throw new \Exception('An error has occurred which prevents PowerProcess from starting');
    }

    /**
     * @return mixed
     */
    public function whoAmI()
    {
        return $this->processName;
    }

    /**
     *
     * @param int|string $signal
     * @param callable $callback
     * @param string $name
     * @param integer $priority
     * @return \BauerBox\PowerProcess\PowerProcess
     */
    protected function addCallbackToStack($signal, callable $callback, $name = null, $priority = 0)
    {
        if (false === array_key_exists($signal, $this->callbacks) || false === is_array($this->callbacks[$signal])) {
            $this->callbacks[$signal] = array();
        }

        if (null === $name) {
            $name = Signals::signalName($signal) . '-' . ++static::$callbackCounter;
        }

        $this->logger->debug(
            'Registering callback for signal ' .
            Signals::signalName($signal) .
            ' with name "' . $name .
            '" and priority ' . $priority
        );

        $this->callbacks[$signal][] = array($priority, $callback, $name);

        // Register signal with our signal dispatcher if it's not a custom signal
        if ($signal < 1000) {
            if (false === array_key_exists($signal, $this->signalsRegistered)) {
                $this->logger->debug('Registering signal ' . Signals::signalName($signal) . ' with dispatcher');
                pcntl_signal($signal, array($this, 'handleSignal'));
                $this->signalsRegistered[$signal] = true;
            }

            // Unblock the signal
            pcntl_sigprocmask(SIG_UNBLOCK, array($signal));
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function checkRunningJobs()
    {
        // Make sure jobs is an array
        if (false === is_array($this->jobs)) {
            return;
        }

        // Loop through and check the jobs
        foreach ($this->jobs as $jobId => $job) {
            if ($job instanceof Job) {
                // Check if the job is still running
                if (false === $this->isProcessRunning($job->getJobProcessId(), $status)) {
                    // The job process has exited
                    $job->setComplete();

                    // Evaluate the status
                    $job->setStatus($status);

                    // Send Custom Signal
                    $this->handleSignal($this->aliasSignals['SIG_JOB_COMPLETE'], $job);

                    // Remove from the stack
                    unset($this->jobs[$jobId]);

                    // Continue Checks
                    continue;
                }

                // Check that the process has not exceeded the time limit
                if ($this->maxJobTime < $job->getRunningTime()) {
                    // The job has exceeded it's time limit
                    $this->logger->debug(
                        "Job {$job} has exceeded the execution time limit of {$this->maxJobTime} seconds"
                    );

                    // Terminate the job
                    $job->terminate(true);

                    // Send Custom Signal
                    $this->handleSignal($this->aliasSignals['SIG_JOB_TIME_OVER'], $job);

                    // Continue checks
                    continue;
                }
            }
        }
    }

    /**
     *
     */
    protected function installSignalHandler()
    {
        // Setup callback array
        $this->callbacks = array();

        // Init registered signals array
        $this->signalsRegistered = array();

        // Set the static counter
        static::$callbackCounter = 0;

        // Install Signal Aliases
        foreach ($this->aliasSignals as $signalName => $signal) {
            $this->logger->debug("Installing alias signal '{$signalName}' ({$signal})");
            Signals::setSignalAlias($signal, $signalName);
        }

        // Install Default Signal Handlers
        $this
            ->addCallbackToStack(SIGTERM, array($this, 'shutdown'), 'Power Process Shutdown', -1024)
            ->addCallbackToStack(SIGHUP, array($this, 'restart'), 'Power Process Restart', -1024)
            ->addCallbackToStack(SIGCHLD, array($this, 'checkRunningJobs'), 'Power Process Refresh', 1024);

    }

    /**
     *
     */
    public function removeLogger()
    {
        if ($this->isParentProcess()) {
            $this->logger->debug('Removing logger');
        }

        $this->logger = null;
    }

    /**
     * @throws \Exception
     */
    protected function startup()
    {
        // Double check for flags
        if (false === $this->ready || true === $this->continue) {
            throw new \Exception('PowerProcess has already started');
        }

        // Set Process Identification
        $this->processId = Identification::getProcessId();
        $this->parentProcessId = Identification::getProcessId();
        $this->parentSessionId = false;
        $this->processName = 'CONTROL';

        // Initialize Job Queue
        $this->jobs = [];

        // Check for logger
        if (false === $this->logger instanceof AbstractLogger) {
            $this->setLogger(new NullLogger);
        }

        // Swap out flags
        $this->ready = false;
        $this->continue = true;
    }

    /**
     * @param $processId
     * @param bool $forceful
     * @return bool
     */
    protected function terminateProcess($processId, $forceful = false)
    {
        if (true === $forceful) {
            return $this->sendSignal(SIGKILL, $processId);
        }

        return $this->sendSignal(SIGTERM, $processId);
    }

    /**
     * The tick procedure
     *
     * Handles sending queued signals and checks running jobs
     */
    protected function tick()
    {
        // Dispatch Pending Signals
        pcntl_signal_dispatch();

        // Check Running Jobs
        if (true === $this->isParentProcess()) {
            $this->checkRunningJobs();
        }

        // Tick
        usleep($this->tickCounter);
    }
}
