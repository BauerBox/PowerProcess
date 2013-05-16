<?php

namespace BauerBox\PowerProcess\Job;

use BauerBox\PowerProcess\Posix\Signals;

/**
 * Class Job
 * @package BauerBox\PowerProcess\Job
 */
class Job
{
    /**
     * @var string
     */
    public static $autoJobNamePrefix = 'JOB';

    /**
     * @var int
     */
    protected static $jobCount = 0;

    /**
     * @var int
     */
    protected $jobId;

    /**
     * @var null|string
     */
    protected $jobName;

    /**
     * @var null
     */
    protected $jobProcessId;

    /**
     * @var float
     */
    protected $jobStart;

    /**
     * @var float
     */
    protected $jobStop;

    /**
     * @var int|null
     */
    protected $jobStatus;

    /**
     * @var bool
     */
    protected $terminateRequested = false;

    /**
     * @param int $processId
     * @param string|null $jobName
     */
    public function __construct($processId = null, $jobName = null)
    {
        $this->jobId = static::$jobCount;
        ++static::$jobCount;

        if (null === $jobName) {
            $this->jobName = sprintf('%s-%d', static::$autoJobNamePrefix, $this->jobId);
        } else {
            $this->jobName = $jobName;
        }

        $this->jobProcessId = $processId;
        $this->jobStart = microtime(true);
    }

    /**
     * @return null|string
     */
    public function __toString()
    {
        return $this->getJobName();
    }

    /**
     * @return float
     */
    public function getRunningTime()
    {
        if (null === $this->jobStop) {
            return (microtime(true) - $this->jobStart);
        }

        return $this->jobStop - $this->jobStart;
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @return null|string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @return null
     */
    public function getJobProcessId()
    {
        return $this->jobProcessId;
    }

    /**
     * @return mixed
     */
    public function getJobStatus()
    {
        return $this->jobStatus;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function setComplete()
    {
        if (null === $this->jobStop) {
            $this->jobStop = microtime(true);
            return $this;
        }

        throw new \Exception('Job status can not be changed after the job has completed!');
    }

    /**
     * @param $processId
     *
     * @return $this
     * @throws \Exception
     */
    public function setProcessId($processId)
    {
        if (null === $this->jobProcessId) {
            $this->jobProcessId = $processId;
            return $this;
        }

        throw new \Exception('Can not change process ID of a job once it has been assigned!');
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        // TODO: Still need status evaluation features
        $this->jobStatus = $status;
        return $this;
    }

    /**
     * @param bool $force
     * @return bool
     */
    public function terminate($force = false)
    {
        if (true === $force) {
            return Signals::sendSignal(SIGKILL, $this->getJobProcessId());
        }

        return Signals::sendSignal(SIGTERM, $this->getJobProcessId());
    }
}
