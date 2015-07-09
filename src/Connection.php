<?php
/**
 * Connection class
 *
 * PHP version 5.5
 *
 * @category Connection
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Connection main class
 *
 * PHP version 5.5
 *
 * @category Connection
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Connection
{
    private static $_driver = array(
        "development"=> "sqlite",
        "test"       => "sqlite",
        "production" => "sqlite");

    private static $_connection = array(
        "development"=> null,
        "test"       => null,
        "production" => null);

    private static $_encoding = null;

    /**
     * Set the connection handle
     *
     * @param mixed  $con connection handle
     * @param string $env environment
     *
     * @return null
     */
    public static function setConnection($con, $env = null) 
    {
        $env = self::selectEnvironment($env);
        self::$_connection[$env] = $con;

        // just send an exception when not on production mode
        if (in_array($env, array("development","test"))) {
            self::setErrorHandling(\PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * Return the connection handle
     *
     * @param string $env environment
     *
     * @return mixed connection handle
     */
    public static function getConnection($env = null)
    {
        return self::$_connection[self::selectEnvironment($env)];
    }

    /**
     * Return the current environment
     *
     * @param string $env enviroment to be forced
     *
     * @return string environment
     */
    public static function selectEnvironment($env = null) 
    {
        if (strlen($env) < 1) {
            $getenv = self::_getEnvironment();
            if (strlen($getenv) > 0) {
                $env = $getenv;
            } else {
                $env = "development";
            }
        }
        return $env;
    }

    /**
     * Return environment from the environment variable
     *
     * @return string environment
     */
    private static function _getEnvironment()
    {
        return getenv("TORM_ENV");
    }

    /**
     * Set the connection database driver
     *
     * @param string $driver database driver
     * @param string $env    environment
     *
     * @return driver file
     */
    public static function setDriver($driver, $env = null)
    {
        $file = realpath(dirname(__FILE__)."/../drivers/$driver.php");
        if (!file_exists($file)) {
            Log::log("ERROR: Driver file $file does not exists");
            return null;
        }
        self::$_driver[self::selectEnvironment($env)] = $driver;
        include_once $file;
    }

    /**
     * Return the database driver
     *
     * @param string $env environment to check
     *
     * @return string driver
     */
    public static function getDriver($env = null)
    {
        return self::$_driver[self::selectEnvironment($env)];
    }

    /**
     * Set the error handling strategy
     *
     * @param mixed $strategy strategy
     *
     * @return null
     */
    public static function setErrorHandling($strategy) 
    {
        self::getConnection()->setAttribute(\PDO::ATTR_ERRMODE, $strategy);
    }

    /**
     * Set encoding
     *
     * @param string $encoding encoding
     *
     * @return null
     */
    public static function setEncoding($encoding)
    {
        self::$_encoding = $encoding;
    }

    /**
     * Return encoding
     *
     * @return string encoding
     */
    public static function getEncoding()
    {
        return self::$_encoding;
    }

    /**
     * Convert a string to the specified encoding
     * Paranoid checking
     *
     * @param string $mixed string to encode
     *
     * @return string encoded
     */
    public static function convertToEncoding($mixed)
    {
        if (is_null(self::$_encoding)
            || is_numeric($mixed)
            || is_bool($mixed)
            || is_object($mixed)
            || is_array($mixed)
            || !is_string($mixed)
        ) {
            return $mixed;  
        }
        return mb_convert_encoding($mixed, self::$_encoding);
    }
}
?>
