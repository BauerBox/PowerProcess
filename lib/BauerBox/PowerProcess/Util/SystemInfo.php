<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bauerd
 * Date: 4/5/13
 * Time: 5:21 PM
 * To change this template use File | Settings | File Templates.
 */

namespace BauerBox\PowerProcess\Util;


class SystemInfo
{
    const INFO_HOSTNAME     =   0;
    const INFO_OS           =   1;
    const INFO_OS_RELEASE   =   2;
    const INFO_OS_VERSION   =   3;
    const INFO_OS_ARCH      =   4;

    const OS_UNKNOWN        =   0;
    const OS_WINDOWS        =   1;
    const OS_LINUX          =   2;
    const OS_MAC_OSX        =   4;

    const ARCH_UNKNOWN      =   0;
    const ARCH_32_BIT       =   1;
    const ARCH_64_BIT       =   2;


    /**
     * Information about the platform that PowerProcess is running on
     *
     * @var array
     */
    protected $platform;

    protected $unameArch = array(
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
    );

    protected $unameOS = array(
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
    );

    public function __construct()
    {
        // Get the basics
        $this->sniffPlatform();
    }

    protected function sniffCpu()
    {
        // Linux
        // exec(“cat /proc/cpuinfo | grep processor | wc -l”,$processors);
        // Full CPU INFO output:
        /*
        www-data@rc-dev:~/RevMatic$ cat /proc/cpuinfo
        processor       : 0
        vendor_id       : AuthenticAMD
        cpu family      : 16
        model           : 8
        model name      : AMD Opteron(tm) Processor 4170 HE
        stepping        : 1
        microcode       : 0x10000d9
        cpu MHz         : 2094.748
        cache size      : 512 KB
        fpu             : yes
        fpu_exception   : yes
        cpuid level     : 5
        wp              : yes
        flags           : fpu de tsc msr pae cx8 cmov pat clflush mmx fxsr sse sse2 ht syscall nx mmxext fxsr_opt lm 3dnowext 3dnow rep_good nopl pni cx16 popcnt hypervisor lahf_lm cmp_legacy extapic cr8_legacy abm sse4a misalignsse 3dnowprefetch
        bogomips        : 4189.49
        TLB size        : 1024 4K pages
        clflush size    : 64
        cache_alignment : 64
        address sizes   : 48 bits physical, 48 bits virtual
        power management: ts ttp tm stc 100mhzsteps hwpstate

        processor       : 1
        vendor_id       : AuthenticAMD
        cpu family      : 16
        model           : 8
        model name      : AMD Opteron(tm) Processor 4170 HE
        stepping        : 1
        microcode       : 0x10000d9
        cpu MHz         : 2094.748
        cache size      : 512 KB
        fpu             : yes
        fpu_exception   : yes
        cpuid level     : 5
        wp              : yes
        flags           : fpu de tsc msr pae cx8 cmov pat clflush mmx fxsr sse sse2 ht syscall nx mmxext fxsr_opt lm 3dnowext 3dnow rep_good nopl pni cx16 popcnt hypervisor lahf_lm cmp_legacy extapic cr8_legacy abm sse4a misalignsse 3dnowprefetch
        bogomips        : 4189.49
        TLB size        : 1024 4K pages
        clflush size    : 64
        cache_alignment : 64
        address sizes   : 48 bits physical, 48 bits virtual
        power management: ts ttp tm stc 100mhzsteps hwpstate
        */

        // Mac
        // /usr/sbin/system_profiler SPHardwareDataType
        // Output:
        /*
        Hardware:

        Hardware Overview:

          Model Name: MacBook Pro
          Model Identifier: MacBookPro8,2
          Processor Name: Intel Core i7
          Processor Speed: 2.4 GHz
          Number of Processors: 1
          Total Number of Cores: 4
          L2 Cache (per Core): 256 KB
          L3 Cache: 6 MB
          Memory: 8 GB
          Boot ROM Version: MBP81.0047.B27
          SMC Version (system): 1.69f4
          Serial Number (system): C02HN165DV7M
          Hardware UUID: 75EB8A7E-9837-5792-8E0E-10BDD0C971C7
          Sudden Motion Sensor:
              State: Enabled
        */

    }

    protected function sniffPlatform()
    {
        // Set the basic platform information
        $this->platform = array(
            self::INFO_OS           =>  php_uname('s'),
            self::INFO_HOSTNAME     =>  php_uname('n'),
            self::INFO_OS_RELEASE   =>  php_uname('r'),
            self::INFO_OS_VERSION   =>  php_uname('v'),
            self::INFO_OS_ARCH      =>  php_uname('m')
        );
    }
}
