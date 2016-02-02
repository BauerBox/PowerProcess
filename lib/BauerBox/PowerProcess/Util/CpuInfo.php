<?php
/**
 * Created by PhpStorm.
 * User: bauerd
 * Date: 2/25/15
 * Time: 1:29 PM
 */

namespace BauerBox\PowerProcess\Util;

/**
 * Class CpuInfo
 *
 * @package BauerBox\PowerProcess\Util
 */
class CpuInfo
{
    /**
     * Total physical CPU count
     *
     * @var int
     */
    public $count = 1;

    /**
     * Total core count
     *
     * @var int
     */
    public $cores = 1;

    /**
     * Total logical core count
     *
     * @var int
     */
    public $logicalCores = 1;

    /**
     * @param null $os
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
                throw new \RuntimeException('Unsupported operating system "%s"', $os);
        }
    }

    /**
     * Load and Parse information on OS-X systems
     */
    protected function loadMacInfo()
    {
        // Try sysctl machdep.cpu
        if (true === file_exists('/usr/sbin/sysctl')) {
            $this->count = (int) trim(shell_exec('sysctl -n hw.packages'));

            $output = \shell_exec('sysctl machdep.cpu');

            if (0 < preg_match('@machdep.cpu.core_count: (?P<cores>\d+)@i', $output, $match)) {
                $this->cores = (int) $match['cores'];
            }

            if (0 < preg_match('@machdep.cpu.thread_count: (?P<lcores>\d+)@i', $output, $match)) {
                $this->logicalCores = (int) $match['lcores'];
            }
        }
    }

    /**
     * Load and Parse information on Linux systems
     */
    protected function loadLinuxInfo()
    {
        // Try cpuinfo
        if (true === file_exists('/proc/cpuinfo')) {
            $output = preg_replace('@(\t| )+@', " ", shell_exec('cat /proc/cpuinfo'));
            $info = explode(PHP_EOL . PHP_EOL, $output);

            foreach ($info as &$record) {
                if (trim($record) == '') {
                    $record = null;
                    continue;
                }

                $record = explode(PHP_EOL, $record);
                $stats = [];

                foreach ($record as $data) {
                    $data = explode(':', $data);
                    $key = trim($data[0]);
                    $val = trim($data[1]);
                    $stats[$key] = $val;
                }

                $record = $stats;
            }

            $info = array_filter($info);
            $cpus = [];

            foreach ($info as $record) {
                $physical = $record['physical id'];

                if (false === array_key_exists($physical, $cpus)) {
                    $cpus[$physical] = [
                        'cores'     =>  [],
                        'logical'   =>  0
                    ];
                }

                $coreId = $record['core id'];
                if (false === array_key_exists($coreId, $cpus[$physical]['cores'])) {
                    $cpus[$physical]['cores'][$coreId] = $record;
                }

                ++$cpus[$physical]['logical'];
            }

            $this->count = count($cpus);
            $this->cores = 0;

            $this->logicalCores = 0;

            foreach ($cpus as $cpu) {
                $this->cores += count($cpu['cores']);
                $this->logicalCores += $cpu['logical'];
            }
        }
    }
}
