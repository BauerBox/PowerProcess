<?php
/**
 * This file is a part of the PowerProcess package for PHP by BauerBox Labs
 *
 * @copyright
 * Copyright (c) 2013-2016 Don Bauer <lordgnu@me.com> BauerBox Labs
 *
 * @license https://github.com/BauerBox/PowerProcess/blob/master/LICENSE MIT License
 */

namespace BauerBox\PowerProcess\Posix;

/**
 * A small utility class for getting various process-related identifiers
 *
 * @package BauerBox\PowerProcess\Posix
 */
class Identification
{
    /**
     * Get the PID
     *
     * @return int
     */
    public static function getProcessId()
    {
        return \posix_getpid();
    }

    /**
     * Get Parent PID
     *
     * @return int
     */
    public static function getParentProcessId()
    {
        return \posix_getppid();
    }

    /**
     * Get SID
     *
     * @return int
     */
    public static function getSessionId()
    {
        return \posix_getsid(0);
    }

    /**
     * Get SID for specific PID
     *
     * @param $processId
     *
     * @return int
     */
    public static function getSessionIdForProcessId($processId)
    {
        return \posix_getsid($processId);
    }

    /**
     * Get GID
     *
     * @return int
     */
    public static function getGroupId()
    {
        return \posix_getgid();
    }

    /**
     * Get Effective GID
     *
     * @return int
     */
    public static function getEffectiveGroupId()
    {
        return \posix_getegid();
    }

    /**
     * Get UID
     *
     * @return integer
     */
    public static function getUserId()
    {
        return \posix_getuid();
    }

    /**
     * Get Effective UID
     *
     * @return integer
     */
    public static function getEffectiveUserId()
    {
        return \posix_geteuid();
    }

    /**
     * Returns array of group set for current process ID (PID)
     *
     * @return array
     */
    public static function getGroupSetIds()
    {
        return \posix_getgroups();
    }

    /**
     * Returns group information array for the group ID (GID) supplied
     *
     * The array elements returned are:
     *
     * name (string)
     *   The name element contains the name of the group. This is a short,
     *   usually less than 16 character "handle" of the group, not the real, full name.
     * passwd (string)
     *   The passwd element contains the group's password in an encrypted format.
     *   Often, for example on a system employing "shadow" passwords, an asterisk is returned instead.
     * gid (integer)
     *   Group ID, should be the same as the gid parameter used when calling the function, and hence redundant.
     * members (array)
     *   This consists of an array of string's for all the members in the group.
     *
     * @param integer $groupId
     * @return array
     */
    public static function getGroupInfoForGroupId($groupId)
    {
        return \posix_getgrgid($groupId);
    }
}
