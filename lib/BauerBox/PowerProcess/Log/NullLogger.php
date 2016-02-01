<?php

namespace BauerBox\PowerProcess\Log;

/**
 * Class NullLogger
 *
 * @package BauerBox\PowerProcess\Log
 */
class NullLogger extends AbstractLogger
{
    /**
     * @param $level
     * @param $message
     * @param array $context
     *
     * @return $this
     */
    protected function handleMessage($level, $message, $context = array())
    {
        return $this;
    }

    /**
     * @param $jobName
     *
     * @return $this
     */
    public function setJobName($jobName)
    {
        return $this;
    }
}
