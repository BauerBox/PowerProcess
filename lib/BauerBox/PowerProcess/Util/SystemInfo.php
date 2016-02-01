<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bauerd
 * Date: 4/5/13
 * Time: 5:21 PM
 * To change this template use File | Settings | File Templates.
 */

namespace BauerBox\PowerProcess\Util;

/**
 * Utility for determining system information
 *
 * @package BauerBox\PowerProcess\Util
 */
class SystemInfo
{
    /* Enum Constants for Info Keys */
    const INFO_HOSTNAME     =   0;
    const INFO_OS           =   1;
    const INFO_OS_RELEASE   =   2;
    const INFO_OS_VERSION   =   3;
    const INFO_OS_ARCH      =   4;

    /* Enum Constants for OS Types */
    const OS_UNKNOWN        =   0;
    const OS_WINDOWS        =   1;
    const OS_LINUX          =   2;
    const OS_MAC_OSX        =   4;

    /* Enum Constants for CPU Architecture */
    const ARCH_UNKNOWN      =   0;
    const ARCH_32_BIT       =   1;
    const ARCH_64_BIT       =   2;


    /**
     * Information about the platform that PowerProcess is running on
     *
     * @var array
     */
    protected $platform;

    /**
     * @var CpuInfo
     */
    protected $cpu;

    /**
     * @var MemoryInfo
     */
    protected $mem;

    /**
     * @var array
     */
    protected $unameArch = [
        'ia64',
        'x86_64',
        'i686',
        'i386',
        'armv7l',
        'armv61',
        'amd64',
        'ppc64',
        'ppc',
        'sparc64',
        'sparc',
        'sun4u',
        'mips',
        'i686-64'
    ];

    /**
     * @var array
     */
    protected $unameOS = [
        '@^CYGWIN@',    // Cygwin
        '@^MINGW@',     // MSYS
        '@^UWIN@',      // UWIN
        '@^FreeBSD@',   // FreeBSD
        '@^IRIX@',      // Irix
        '@^SunOS@',     // Solaris
        '@^Linux@',     // Linux
        '@^Darwin@',    // Mac OSX
        '@^Haiku@',     // Haiku
        '@^AIX@',       // AIX
        '@^Minix@',     // Minix
        '@^DragonFly@', // Dragonfly BSD
        '@^HP-UX@'      // HP-UX
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Get the basics
        $this->sniffPlatform();

        // Get CPU Info
        $this->sniffCpu();

        // Get Memory Info
        $this->sniffMemory();
    }

    /**
     * Load Memory Information
     */
    protected function sniffMemory()
    {
        $this->mem = new MemoryInfo($this->getOs());
    }

    /**
     * Load CPU Info
     */
    protected function sniffCpu()
    {
        $this->cpu = new CpuInfo($this->getOs());
    }

    /**
     * Load Platform (OS) info
     */
    protected function sniffPlatform()
    {
        // Set the basic platform information
        $this->platform = array(
            static::INFO_OS           =>  php_uname('s'),
            static::INFO_HOSTNAME     =>  php_uname('n'),
            static::INFO_OS_RELEASE   =>  php_uname('r'),
            static::INFO_OS_VERSION   =>  php_uname('v'),
            static::INFO_OS_ARCH      =>  php_uname('m')
        );
    }

    /**
     * Get the OS (Linux, Darwin, etc...)
     *
     * @return string
     */
    public function getOs()
    {
        return $this->platform[static::INFO_OS];
    }

    /**
     * Get the Hostname
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->platform[static::INFO_HOSTNAME];
    }

    /**
     * Get the OS Release (Version number usually)
     *
     * @return string
     */
    public function getOsRelease()
    {
        return $this->platform[static::INFO_OS_RELEASE];
    }

    /**
     * Get the OS Architecture
     *
     * @return string
     */
    public function getOsArch()
    {
        return $this->platform[static::INFO_OS_ARCH];
    }

    /**
     * Get the OS Version String
     *
     * @return string
     */
    public function getOsVersion()
    {
        return $this->platform[static::INFO_OS_VERSION];
    }

    /**
     * Get the CPU Info
     *
     * @return CpuInfo
     */
    public function getCpuInfo()
    {
        return $this->cpu;
    }

    /**
     * Get the Memory Info
     *
     * @return MemoryInfo
     */
    public function getMemoryInfo()
    {
        return $this->mem;
    }
}
