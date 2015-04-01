<?php
/**
 * Created by PhpStorm.
 * User: bauerd
 * Date: 2/25/15
 * Time: 1:56 PM
 */

namespace BauerBox\PowerProcess\Util;

/**
 * Class MemoryInfo
 * @package BauerBox\PowerProcess\Util
 */
class MemoryInfo
{
    const BYTES = 0;
    const KB = 1;
    const MB = 2;
    const GB = 4;
    const TB = 8;

    /**
     * RAM in bytes
     *
     * @var int
     */
    public $memory;

    /**
     * @var int
     */
    public $phpMemoryLimit;

    /**
     * @param string $os
     */
    public function __construct($os = null)
    {
        if (null === $os) {
            $os = PHP_OS;
        }

        switch ($os) {
            case 'Darwin':
                $this->loadMacInfo();
                break;
            case 'Linux':
                $this->loadLinuxInfo();
                break;
            default:
                // Dunno...
                break;
        }

        $limit = ini_get('memory_limit');
        if (0 < preg_match('@^(?P<mem>[0-9]+)(?P<unit>[K|M|G|T])$@i', $limit, $match)) {
            var_dump($match);
            if (false === array_key_exists('unit', $match)) {
                $this->phpMemoryLimit = (int) $match['mem'];
            } else {
                $mem = (int) $match['mem'];

                switch (strtolower($match['unit'])) {
                    case 't':
                        $mem *= 1024;
                    case 'g':
                        $mem *= 1024;
                    case 'm':
                        $mem *= 1024;
                    case 'k':
                        $mem *= 1024;
                }

                $this->phpMemoryLimit = $mem;
            }
        }
    }

    /**
     * Helper function to get info for Mac-based machines
     */
    protected function loadMacInfo()
    {
        $this->memory = (int) shell_exec('sysctl -n hw.memsize');
    }

    /**
     * Helper to load info for Linux-based machines
     */
    protected function loadLinuxInfo()
    {
        if (true === file_exists('/proc/meminfo')) {
            $mem = shell_exec('cat /proc/meminfo | grep MemTotal');

            if (0 < preg_match('@(?P<mem>\d+)\s(?P<unit>(kb|mb|gb|tb))?@i', $mem, $match)) {
                if (false === array_key_exists('unit', $match)) {
                    $this->memory = (int) $match['mem'];
                } else {
                    $mem = (int) $match['mem'];

                    switch (strtolower($match['unit'])) {
                        case 'tb':
                            $mem *= 1024;
                        case 'gb':
                            $mem *= 1024;
                        case 'mb':
                            $mem *= 1024;
                        case 'kb':
                            $mem *= 1024;
                            break;
                    }

                    $this->memory = $mem;
                }
            }
        }
    }

    /**
     * @param null $unit
     *
     * @return float|int
     */
    public function getMemory($unit = null)
    {
        return $this->bytesToUnit($this->memory, $unit);
    }

    /**
     * @param null $unit
     *
     * @return float|int
     */
    public function getPhpMemoryLimit($unit = null)
    {
        return $this->bytesToUnit($this->phpMemoryLimit, $unit);
    }

    /**
     * @param $memory
     * @param null $unit
     *
     * @return float|int
     */
    private function bytesToUnit($memory, $unit = null)
    {
        switch ($unit) {
            case static::TB:
                return ($memory / (1024 * 1024 * 1024 * 1024));
                break;
            case static::GB:
                return ($memory / (1024 * 1024 * 1024));
                break;
            case static::MB:
                return ($memory / (1024 * 1024));
                break;
            case static::KB:
                return ($memory / 1024);
                break;
            case static::BYTES:
            default:
                return $memory;
        }
    }
}
