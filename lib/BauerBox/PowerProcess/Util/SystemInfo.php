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

    /**
     * @var array
     */
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

    /**
     *
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

    protected function sniffMemory()
    {
        // Linux
        // cat /proc/meminfo
        /*
         MemTotal:        7629440 kB
MemFree:          796940 kB
Buffers:          434464 kB
Cached:          5287412 kB
SwapCached:            0 kB
Active:          3291596 kB
Inactive:        3064992 kB
Active(anon):     705460 kB
Inactive(anon):    81940 kB
Active(file):    2586136 kB
Inactive(file):  2983052 kB
Unevictable:           0 kB
Mlocked:               0 kB
SwapTotal:             0 kB
SwapFree:              0 kB
Dirty:                84 kB
Writeback:             0 kB
AnonPages:        634728 kB
Mapped:           146836 kB
Shmem:            152688 kB
Slab:             370736 kB
SReclaimable:     353976 kB
SUnreclaim:        16760 kB
KernelStack:        1224 kB
PageTables:        11900 kB
NFS_Unstable:          0 kB
Bounce:                0 kB
WritebackTmp:          0 kB
CommitLimit:     3814720 kB
Committed_AS:    1333984 kB
VmallocTotal:   34359738367 kB
VmallocUsed:       26116 kB
VmallocChunk:   34359710188 kB
HardwareCorrupted:     0 kB
AnonHugePages:         0 kB
HugePages_Total:       0
HugePages_Free:        0
HugePages_Rsvd:        0
HugePages_Surp:        0
Hugepagesize:       2048 kB
DirectMap4k:     7872512 kB
DirectMap2M:           0 kB
        */

        // OS-X
        // hwprefs memory_size

        // sysctl hw.memsize (returns bytes)

        $this->mem = new MemoryInfo($this->getOs());
    }

    protected function sniffCpu()
    {
        $this->cpu = new CpuInfo($this->getOs());

        // Linux (http://www.binarytides.com/linux-cpu-information/)
        // lscpu
/*Architecture:          x86_64
CPU op-mode(s):        32-bit, 64-bit
Byte Order:            Little Endian
CPU(s):                2
On-line CPU(s) list:   0,1
Thread(s) per core:    2
Core(s) per socket:    1
Socket(s):             1
NUMA node(s):          1
Vendor ID:             GenuineIntel
CPU family:            6
Model:                 62
Stepping:              4
CPU MHz:               2500.078
BogoMIPS:              5000.15
Hypervisor vendor:     Xen
Virtualization type:   full
L1d cache:             32K
L1i cache:             32K
L2 cache:              256K
L3 cache:              25600K
NUMA node0 CPU(s):     0,1*/

        // exec("cat /proc/cpuinfo | grep processor | wc -l",$processors);
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

        // sysctl -n machdep.cpu.brand_string
        Intel(R) Core(TM) i7-2820QM CPU @ 2.30GHz

        // sysctl machdep.cpu
        machdep.cpu.max_basic: 13
        machdep.cpu.max_ext: 2147483656
        machdep.cpu.vendor: GenuineIntel
        machdep.cpu.brand_string: Intel(R) Core(TM) i7-2820QM CPU @ 2.30GHz
        machdep.cpu.family: 6
        machdep.cpu.model: 42
        machdep.cpu.extmodel: 2
        machdep.cpu.extfamily: 0
        machdep.cpu.stepping: 7
        machdep.cpu.feature_bits: 2286390448420027391
        machdep.cpu.extfeature_bits: 4967106816
        machdep.cpu.signature: 132775
        machdep.cpu.brand: 0
        machdep.cpu.features: FPU VME DE PSE TSC MSR PAE MCE CX8 APIC SEP MTRR PGE MCA CMOV PAT PSE36 CLFSH DS ACPI MMX FXSR SSE SSE2 SS HTT TM PBE SSE3 PCLMULQDQ DTES64 MON DSCPL VMX SMX EST TM2 SSSE3 CX16 TPR PDCM SSE4.1 SSE4.2 x2APIC POPCNT AES PCID XSAVE OSXSAVE TSCTMR AVX1.0
        machdep.cpu.extfeatures: SYSCALL XD EM64T LAHF RDTSCP TSCI
        machdep.cpu.logical_per_package: 16
        machdep.cpu.cores_per_package: 8
        machdep.cpu.microcode_version: 40
        machdep.cpu.processor_flag: 4
        machdep.cpu.mwait.linesize_min: 64
        machdep.cpu.mwait.linesize_max: 64
        machdep.cpu.mwait.extensions: 3
        machdep.cpu.mwait.sub_Cstates: 135456
        machdep.cpu.thermal.sensor: 1
        machdep.cpu.thermal.dynamic_acceleration: 1
        machdep.cpu.thermal.invariant_APIC_timer: 1
        machdep.cpu.thermal.thresholds: 2
        machdep.cpu.thermal.ACNT_MCNT: 1
        machdep.cpu.thermal.core_power_limits: 1
        machdep.cpu.thermal.fine_grain_clock_mod: 1
        machdep.cpu.thermal.package_thermal_intr: 1
        machdep.cpu.thermal.hardware_feedback: 0
        machdep.cpu.thermal.energy_policy: 1
        machdep.cpu.xsave.extended_state: 7 832 832 0
        machdep.cpu.arch_perf.version: 3
        machdep.cpu.arch_perf.number: 4
        machdep.cpu.arch_perf.width: 48
        machdep.cpu.arch_perf.events_number: 7
        machdep.cpu.arch_perf.events: 0
        machdep.cpu.arch_perf.fixed_number: 3
        machdep.cpu.arch_perf.fixed_width: 48
        machdep.cpu.cache.linesize: 64
        machdep.cpu.cache.L2_associativity: 8
        machdep.cpu.cache.size: 256
        machdep.cpu.tlb.inst.small: 64
        machdep.cpu.tlb.inst.large: 8
        machdep.cpu.tlb.data.small: 64
        machdep.cpu.tlb.data.large: 32
        machdep.cpu.tlb.shared: 512
        machdep.cpu.address_bits.physical: 36
        machdep.cpu.address_bits.virtual: 48
        machdep.cpu.core_count: 4
        machdep.cpu.thread_count: 8
        */

    }

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

    public function getOs()
    {
        return $this->platform[static::INFO_OS];
    }

    public function getHostname()
    {
        return $this->platform[static::INFO_HOSTNAME];
    }

    public function getOsRelease()
    {
        return $this->platform[static::INFO_OS_RELEASE];
    }

    public function getOsArch()
    {
        return $this->platform[static::INFO_OS_ARCH];
    }

    public function getOsVersion()
    {
        return $this->platform[static::INFO_OS_VERSION];
    }

    public function getCpuInfo()
    {
        return $this->cpu;
    }

    public function getMemoryInfo()
    {
        return $this->mem;
    }
}
