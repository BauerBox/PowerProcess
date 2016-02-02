<?php
/**
 * This file is a part of the PowerProcess package for PHP by BauerBox Labs
 *
 * @copyright
 * Copyright (c) 2013-2016 Don Bauer <lordgnu@me.com> BauerBox Labs
 *
 * @license https://github.com/BauerBox/PowerProcess/blob/master/LICENSE MIT License
 */

/**
 * Class BauerBox_PowerProcess_Autoloader
 */
class BauerBox_PowerProcess_Autoloader
{
    /**
     * Base namespace
     *
     * @var string
     */
    public static $classBase = 'BauerBox\\PowerProcess\\';

    /**
     * Register the Autoloader
     */
    public static function register()
    {
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload($class)
    {
        if (0 !== strpos($class, static::$classBase)) {
            return;
        }

        $class = str_replace(array(static::$classBase, '\\'), array('', '/'), $class);

        if (is_file($file = dirname(__FILE__).'/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require $file;
        }
    }
}
