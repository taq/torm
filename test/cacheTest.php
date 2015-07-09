<?php
/**
 * Cache class tests
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
require_once "../vendor/autoload.php";

/**
 * Cache test main class 
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class CacheTest extends PHPUnit_Framework_TestCase
{
    protected static $con  = null;
    protected static $user = null;

    /**
     * Run when initializing
     *
     * @return null
     */
    public static function setUpBeforeClass()
    {
        $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
        self::$con  = new PDO("sqlite:$file");
        // self::$con  = new PDO('mysql:host=localhost;dbname=torm',"torm","torm");

        TORM\Connection::setConnection(self::$con, "test");
        TORM\Connection::setEncoding("UTF-8");
        TORM\Connection::setDriver("sqlite");
        TORM\Factory::setFactoriesPath("./factories");
        TORM\Log::enable(false);
        TORM\Cache::getInstance()->setTimeout(300);
    }

    /**
     * Test cache method
     *
     * @return null
     */
    public function testCache() 
    {
        TORM\Cache::getInstance()->clear();
        $sql = "select * from Users where id=?";
        $this->assertNull(TORM\Cache::getInstance()->get($sql));
        TORM\Cache::getInstance()->put($sql);
        $this->assertNotNull(TORM\Cache::getInstance()->get($sql));
    }

    /**
     * Test cache timeout
     *
     * @return null
     */
    public function testCacheTimeout()
    {
        TORM\Cache::getInstance()->clear();
        TORM\Cache::getInstance()->setTimeout(1);

        $sql = "select * from Users where id=?";
        $this->assertNull(TORM\Cache::getInstance()->get($sql));
        TORM\Cache::getInstance()->put($sql);
        $size = TORM\Cache::getInstance()->size() > 0;
        $this->assertTrue($size > 0);
        sleep(2);
        $this->assertNull(TORM\Cache::getInstance()->get($sql));
        $this->assertEquals($size - 1, TORM\Cache::getInstance()->size());
    }

    /**
     * Test cache expiration function
     *
     * @return null
     */
    public function testCacheExpiration()
    {
        TORM\Cache::getInstance()->setTimeout(3);
        TORM\Cache::getInstance()->clear();

        $this->assertFalse(TORM\Cache::getInstance()->expireCache());
        sleep(1);
        $this->assertFalse(TORM\Cache::getInstance()->expireCache());
        sleep(2);
        $this->assertTrue(TORM\Cache::getInstance()->expireCache());
        sleep(1);
        $this->assertFalse(TORM\Cache::getInstance()->expireCache());
    }

    /**
     * Test cache size
     *
     * @return null
     */
    public function testCacheSize()
    {
        $sql = "select * from Users where id=?";
        TORM\Cache::getInstance()->put($sql);
        $this->assertTrue(TORM\Cache::getInstance()->size() > 0);
    }

    /**
     * Test clear cache
     *
     * @return null
     */
    public function testClearCache()
    {
        $sql = "select * from Users where id=?";
        TORM\Cache::getInstance()->put($sql);
        $this->assertTrue(TORM\Cache::getInstance()->size() > 0);
        TORM\Cache::getInstance()->clear();
        $this->assertEquals(0, TORM\Cache::getInstance()->size());
    }
}
?>
