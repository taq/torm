<?php
/**
 * Caching
 *
 * PHP version 5.5
 *
 * @category Caching
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Cache class
 *
 * PHP version 5.5
 *
 * @category Caching
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Cache
{
    private static $_instance = null;
    private $_prepared_cache  = array();
    private $_timeout         = 300; // seconds
    private $_last_expired_at = null;

    /**
     * Private constructor
     *
     * @return null
     */
    private function __construct()
    {
        $this->_prepared_cache = array();
    }

    /**
     * Get cache instance
     *
     * @return mixed cache
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * Get the SQL hash
     *
     * @param string $sql sql query
     *
     * @return string
     */
    private function _sqlHash($sql)
    {
        return md5($sql);
    }

    /**
     * Put a prepared statement on cache, if not there.
     *
     * @param string $sql query
     * @param string $cls class to resolve connection
     *
     * @return object prepared statement
     */
    public function put($sql, $cls = null)
    {
        $this->expireCache();
        $hash = self::_sqlHash($sql);

        if (array_key_exists($hash, $this->_prepared_cache)) {
            Log::log("already prepared: $sql");
            return $this->_prepared_cache[$hash]["statement"];
        } else {
            Log::log("inserting on cache: $sql");
        }
        $cls      = $cls ? $cls : "TORM\Model";
        $con      = $cls::resolveConnection();
        $prepared = $con->prepare($sql);
        $this->_prepared_cache[$hash] = ["statement" => $prepared, "timestamp" => time()];
        return $prepared;
    }

    /**
     * Get a prepared statement from cache
     *
     * @param string $sql query
     *
     * @return object or null if not on cache
     */
    public function get($sql)
    {
        $this->expireCache();
        $hash = self::_sqlHash($sql);

        // if doesn't exists, return null
        if (!array_key_exists($hash, $this->_prepared_cache)) {
            return null;
        }
        return $this->_prepared_cache[$hash]["statement"];
    }

    /**
     * Expire cache
     *
     * I'd love to use threads to do that, but I'm not forcing 
     * compiling PHP to use that.
     *
     * @param boolean $verbose mode
     *
     * @return boolean
     */
    public function expireCache($verbose = false)
    {
        // if first run ...
        if (!$this->_last_expired_at) {
            $this->_last_expired_at = time();
            if ($verbose) {
                echo "First time running cache expiration\n";
            }
        }

        // if current time is lower than the last time ran plust timeout
        if (time() < $this->_last_expired_at + $this->_timeout) {
            if ($verbose) {
                echo "Not checking cache timeouts\n";
            }
            return false;
        }

        if ($verbose) {
            echo "Checking cache timeouts.\n";
        }
        foreach ($this->_prepared_cache as $key => $cache) {
            if (time() > intval($cache["timestamp"]) + $this->_timeout) {
                Model::closeCursor($cache["statement"]);
                unset($this->_prepared_cache[$key]);
            }
        }
        $this->_last_expired_at = time();
        return true;
    }

    /**
     * Return the size of the cache
     *
     * @return integer
     */
    public function size()
    {
        return sizeof($this->_prepared_cache);
    }

    /**
     * Clear cache
     *
     * @return null
     */
    public function clear()
    {
        $this->_prepared_cache  = array();
        $this->_last_expired_at = time();
    }

    /**
     * Set timeout
     *
     * @param int $timeout timeout in seconds
     *
     * @return null
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
    }

    /**
     * Get timeout
     *
     * @return int timeout
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }
}
?>
