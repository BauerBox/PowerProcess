<?php

/*
 * This file is part of the RevMatic package.
 *
 * (c) RevCo <http://www.gorevco.com>
 *
 * Unless otherwise specified, this source is proprietary
 * property of RevCo and is not to be redistributed in any
 * form without the expressed written permission of RevCo
 */

namespace BauerBox\PowerProcess\Log;

use BauerBox\PowerProcess\Log\AbstractLogger;

/**
 * Description of FileLogger
 *
 * @author Don Bauer <don.bauer@gorevco.com>
 */
class FileLogger extends AbstractLogger
{
    protected $timestampFormat;
    protected $messageFormat;
    protected $autoNewline;

    protected $compiledFormat;
    protected $context;
    protected $relativeTime;

    protected $logFile;

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

    public function setJobName($jobName)
    {
        $this->setContextItem(
            'JOB',
            sprintf('%-10s', $jobName)
        );
        return $this;
    }

    public function setContextItem($item, $value)
    {
        $this->context[$item] = $value;
        return $this;
    }

    public function setMessageFormat($messageFormat)
    {
        $this->messageFormat = $messageFormat;
        return $this;
    }

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
