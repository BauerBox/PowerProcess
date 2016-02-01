<?php

namespace BauerBox\PowerProcess\Log;

/**
 * Class EchoLogger
 *
 * @package BauerBox\PowerProcess\Log
 */
class EchoLogger extends AbstractLogger
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
     * EchoLogger constructor.
     * @param null $messageFormat
     * @param bool $relativeTime
     */
    public function __construct($messageFormat = null, $relativeTime = false)
    {
        $this->context = array();
        $this->relativeTime = $relativeTime;

        if (null === $this->messageFormat) {
            $this->setMessageFormat('[{LEVEL}][{TIMESTAMP}][{JOB}] {MESSAGE}');
        } else {
            $this->setMessageFormat($messageFormat);
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
