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

use Psr\Log\AbstractLogger as BaseAbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Class AbstractLogger
 * @package BauerBox\PowerProcess\Log
 */
abstract class AbstractLogger extends BaseAbstractLogger implements LoggerInterface
{
    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function alert($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function critical($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function debug($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function emergency($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function error($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function info($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::INFO, $message, $context);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function log($level, $message, array $context = array())
    {
        $this->validateLogLevel($level);
        return $this->handleMessage($level, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function notice($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return mixed
     */
    public function warning($message, array $context = array())
    {
        return $this->handleMessage(LogLevel::WARNING, $message, $context);
    }

    /**
     * @param $jobName
     *
     * @return mixed
     */
    abstract public function setJobName($jobName);

    /**
     * @param $level
     * @param $message
     * @param array $context
     *
     * @return mixed
     */
    abstract protected function handleMessage($level, $message, $context = array());

    /**
     * @param $message
     * @param array $context
     *
     * @return string
     */
    protected function interpolate($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param $level
     *
     * @return bool
     */
    protected function validateLogLevel($level)
    {
        switch ($level) {
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::DEBUG:
            case LogLevel::EMERGENCY:
            case LogLevel::ERROR:
            case LogLevel::INFO:
            case LogLevel::NOTICE:
            case LogLevel::WARNING:
                return true;
        }

        throw new InvalidArgumentException('Invalid log log level: ' . $level);
    }
}
