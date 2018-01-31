<?php
/**
 * Log
 *
 * PHP version 5.5
 *
 * @category Log
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Log main class
 *
 * PHP version 5.5
 *
 * @category Log
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Log
{
    private static $_enabled = false;
    private static $_file    = null;

    /**
     * Return if log is enabled
     *
     * @return enabled or not
     */
    public static function isEnabled()
    {
        return self::$_enabled;
    }

    /**
     * Enable or disable log
     *
     * @param boolean $status enable (true) or disabled (false)
     *
     * @return null
     */
    public static function enable($status)
    {
        self::$_enabled = $status;
        if ($status) {
            register_shutdown_function(array('TORM\Log', 'destruct'));
        }
    }

    /**
     * Enable or disable file writing
     *
     * @param string $file or null
     *
     * @return null
     */
    public static function file($file = null)
    {
        self::$_file = $file;
    }

    /**
     * Send a message to log
     *
     * @param string $msg message
     *
     * @return null
     */
    public static function log($msg)
    {
        if (!self::$_enabled) {
            return;
        }

        if (is_null(self::$_file)) {
            echo "log: $msg\n";
            return;
        }

        return self::_logToFile($msg);
    }

    /**
     * Write a message on file
     *
     * @param string $msg message
     * 
     * @return true or false
     */
    private static function _logToFile($msg)
    {
        if (is_null(self::$_file) || (is_string(self::$_file) && !self::_openLogFile())) {
            return false;
        }
        return fwrite(self::$_file, "$msg\n");
    }

    /**
     * Open log file
     *
     * @return boolean
     */
    private static function _openLogFile()
    {
        try {
            self::$_file = fopen(self::$_file, "a");

            if (!self::$_file) {
                self::$_file = null;
                return false;
            }
        } catch (Exception $e) {
        }
        return true;
    }

    /**
     * Destructor
     *
     * Try to close the log file, if is there one
     *
     * @return true if there were an open file opened, false if not
     */
    public static function destruct()
    {
        if (is_null(self::$_file) || !is_resource(self::$_file)) {
            return false;
        }

        try {
            fclose(self::$_file);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
