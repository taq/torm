<?php
/**
 * Log class tests
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
 * Main class
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class LogTest extends PHPUnit_Framework_TestCase
{
    protected static $log_file = '/tmp/torm.log';

    /**
     * Run before each test
     *
     * @return null
     */
    public function setUp()
    {
        TORM\Log::enable(true);
    }

    /**
     * Run after each test
     *
     * @return null
     */
    public function tearDown()
    {
        TORM\Log::enable(false);
    }

    /**
     * Delete log file
     *
     * @param string $file if null, open default file
     *
     * @return null
     */
    private function _deleteLog($file = null)
    {
        $file = is_null($file) ? self::$log_file : $file;

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Test echo
     *
     * @return null
     */
    public function testEcho()
    {
        $this->_deleteLog();
        TORM\Log::log('hello');
        $this->assertFalse(file_exists(self::$log_file));
    }

    /**
     * Test file
     *
     * @return null
     */
    public function testFile()
    {
        $custom = "/tmp/torm-test.log";
        $this->_deleteLog($custom);

        TORM\Log::file($custom);
        TORM\Log::log('hello');
        TORM\Log::log('world');

        $this->assertTrue(file_exists($custom));
        $this->assertEquals("hello\nworld\n", file_get_contents($custom));
        $this->_deleteLog($custom);
    }
}
