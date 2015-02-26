<?php
/**
 * Cache
 *
 * PHP version 5.5
 *
 * @category Traits
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Cache
{
    /**
     * Put a prepared statement on cache, if not there.
     *
     * @param string $sql query
     *
     * @return object prepared statement
     */
    public static function putCache($sql)
    {
        $md5 = md5($sql);

        if (array_key_exists($md5, self::$_prepared_cache)) {
            Log::log("already prepared: $sql");
            return self::$_prepared_cache[$md5];
        } else {
            Log::log("inserting on cache: $sql");
        }
        $prepared = self::resolveConnection()->prepare($sql);
        self::$_prepared_cache[$md5] = $prepared;
        return $prepared;
    }

    /**
     * Get a prepared statement from cache
     *
     * @param string $sql query
     *
     * @return object or null if not on cache
     */
    public static function getCache($sql)
    {
        $md5 = md5($sql);
        if (!array_key_exists($md5, self::$_prepared_cache)) {
            return null;
        }
        return self::$_prepared_cache[$md5];
    }
}
?>
