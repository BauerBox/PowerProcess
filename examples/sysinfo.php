<?php
/**
 * Created by PhpStorm.
 * User: bauerd
 * Date: 2/25/15
 * Time: 1:02 PM
 */

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../lib/BauerBox/PowerProcess/Autoloader.php';

BauerBox_PowerProcess_Autoloader::register();

$info = new \BauerBox\PowerProcess\Util\SystemInfo();

echo
    "OS:      " . $info->getOs() . PHP_EOL .
    "Release: " . $info->getOsRelease() . PHP_EOL .
    "Version: " . $info->getOsVersion() . PHP_EOL .
    "OS Arch: " . $info->getOsArch() . PHP_EOL . PHP_EOL;

$cpu = $info->getCpuInfo();
echo
    "CPU Count:     " . $cpu->count . PHP_EOL .
    "Cores Per CPU: " . $cpu->cores . PHP_EOL .
    "L Cores Per:   " . $cpu->logicalCores . PHP_EOL . PHP_EOL
;

$mem = $info->getMemoryInfo();
echo
    "Mem (Bytes):   " . $mem->memory . PHP_EOL .
    "Mem (KB):      " . $mem->getMemory(\BauerBox\PowerProcess\Util\MemoryInfo::KB) . PHP_EOL .
    "Mem (MB):      " . $mem->getMemory(\BauerBox\PowerProcess\Util\MemoryInfo::MB) . PHP_EOL .
    "Mem (GB):      " . $mem->getMemory(\BauerBox\PowerProcess\Util\MemoryInfo::GB) . PHP_EOL .
    "Mem (TB):      " . $mem->getMemory(\BauerBox\PowerProcess\Util\MemoryInfo::TB) . PHP_EOL . PHP_EOL
;

echo
    "PHP Memory Limit: " . ini_get('memory_limit') . PHP_EOL;

