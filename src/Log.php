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
        if (self::$_enabled) {
            echo "log: $msg\n";
        }
    }
}
