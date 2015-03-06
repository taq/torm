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
     *
     * @return object prepared statement
     */
    public function put($sql)
    {
        $hash = self::_sqlHash($sql);

        if (array_key_exists($hash, $this->_prepared_cache)) {
            Log::log("already prepared: $sql");
            return $this->_prepared_cache[$hash];
        } else {
            Log::log("inserting on cache: $sql");
        }
        $prepared = Model::resolveConnection()->prepare($sql);
        $this->_prepared_cache[$hash] = $prepared;
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
        $hash = self::_sqlHash($sql);
        if (!array_key_exists($hash, $this->_prepared_cache)) {
            return null;
        }
        return $this->_prepared_cache[$hash];
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
        $this->_prepared_cache = array();
    }
}
?>
