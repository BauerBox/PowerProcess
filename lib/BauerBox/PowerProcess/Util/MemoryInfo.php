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
 *
 * @package BauerBox\PowerProcess\Util
 */
class MemoryInfo
{
    /* Enum Constants */
    const BYTES = 0;
    const KB = 1;
    const MB = 2;
    const GB = 3;
    const TB = 4;

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
            if (false === array_key_exists('unit', $match)) {
                $this->phpMemoryLimit = (int) $match['mem'];
            } else {
                $this->phpMemoryLimit = $this->getBytesFromMatch($match);
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
                    $this->memory = $this->getBytesFromMatch($match);
                }
            }
        }
    }

    /**
     * @param int $unit
     *
     * @return float|int
     */
    public function getMemory($unit = self::BYTES)
    {
        return $this->bytesToUnit($this->memory, $unit);
    }

    /**
     * @param int $unit
     *
     * @return float|int
     */
    public function getPhpMemoryLimit($unit = self::BYTES)
    {
        return $this->bytesToUnit($this->phpMemoryLimit, $unit);
    }

    /**
     * @param $memory
     * @param int $unit
     *
     * @return float|int
     */
    private function bytesToUnit($memory, $unit)
    {
        switch ($unit) {
            case static::TB:
            case static::GB:
            case static::MB:
            case static::KB:
                return ($memory / (pow(1024, $unit)));
            case static::BYTES:
                return $memory;
            default:
                throw new \LogicException(sprintf('Unsupported unit value "%d"', $unit));
        }
    }

    /**
     * @param array $match
     *
     * @return int
     */
    private function getBytesFromMatch(array $match)
    {
        $mem = (int) $match['mem'];
        $unit = static::BYTES;

        switch (strtolower($match['unit'])) {
            case 'tb':
            case 't':
                $unit = static::TB;
                break;
            case 'gb':
            case 'g':
                $unit = static::GB;
                break;
            case 'mb':
            case 'm':
                $unit = static::MB;
                break;
            case 'kb':
            case 'k':
                $unit = static::KB;
                break;
            default:
                throw new \LogicException(sprintf('Unsupported unit string "%s"', $unit));
        }

        return ($unit === static::BYTES ? $mem : $mem * pow(1024, $unit));
    }
}
