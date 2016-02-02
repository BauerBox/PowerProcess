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
