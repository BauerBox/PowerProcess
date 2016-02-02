<?php
/**
 * This file is a part of the PowerProcess package for PHP by BauerBox Labs
 *
 * @copyright
 * Copyright (c) 2013-2016 Don Bauer <lordgnu@me.com> BauerBox Labs
 *
 * @license https://github.com/BauerBox/PowerProcess/blob/master/LICENSE MIT License
 */

namespace BauerBox\PowerProcess\Log;

/**
 * Class FileLogger
 * @package BauerBox\PowerProcess\Log
 */
class FileLogger extends AbstractLogger
{
    /**
     * @var string
     */
    protected $messageFormat;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var bool
     */
    protected $relativeTime;

    /**
     * @var null
     */
    private $logFile;

    /**
     * FileLogger constructor.
     * @param null $messageFormat
     * @param bool $relativeTime
     * @param null $file
     */
    public function __construct($messageFormat = null, $relativeTime = false, $file = null)
    {
        $this->logFile = $file;
        $this->context = array();
        $this->relativeTime = $relativeTime;

        if (null === $this->messageFormat) {
            $this->setMessageFormat('[{LEVEL}][{TIMESTAMP}][{JOB}] {MESSAGE}');
        } else {
            $this->setMessageFormat($messageFormat);
        }
    }

    /**
     * @param $logFile
     * @throws \Exception
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;

        if (false === file_exists($logFile)) {
            if (false === file_exists(dirname($logFile))) {
                if (false === mkdir(dirname($logFile), 0777, true)) {
                    throw new \Exception("Could not create directory for log file: {$logFile}");
                }
            }

            if (false === touch($logFile)) {
                throw new \Exception("Could not create log file: {$logFile}");
            }
        }
    }

    /**
     * @param $jobName
     *
     * @return $this
     */
    public function setJobName($jobName)
    {
        $this->setContextItem(
            'JOB',
            sprintf('%-10s', $jobName)
        );
        return $this;
    }

    /**
     * @param $item
     * @param $value
     *
     * @return $this
     */
    public function setContextItem($item, $value)
    {
        $this->context[$item] = $value;
        return $this;
    }

    /**
     * @param $messageFormat
     *
     * @return $this
     */
    public function setMessageFormat($messageFormat)
    {
        $this->messageFormat = $messageFormat;
        return $this;
    }

    /**
     *
     * @return bool|string
     */
    protected function getTime()
    {
        if (false === $this->relativeTime) {
            return date('Y-m-d H:i:s');
        }

        if (false === is_float($this->relativeTime)) {
            $this->relativeTime = microtime(true);
        }

        return sprintf('%08.4f', (microtime(true) - $this->relativeTime));
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     *
     * @return $this
     */
    protected function handleMessage($level, $message, $context = array())
    {
        if (count($context) > 0) {
            $context = array_merge($context, $this->context);
        } else {
            $context = $this->context;
        }

        // Set Variable Context Items
        $context['LEVEL']       = sprintf('%-10s', strtoupper($level));
        $context['TIMESTAMP']   = $this->getTime();
        $context['MESSAGE']     = $this->interpolate($message, $context);

        echo $this->interpolate($this->messageFormat, $context) . PHP_EOL;

        return $this;
    }
}
