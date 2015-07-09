<?php
/**
 * Factory class tests
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
require_once "../models/user.php";

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
class FactoryTest extends PHPUnit_Framework_TestCase
{
    protected static $con  = null;

    /**
     * Run before each test
     *
     * @return null
     */
    public static function setUpBeforeClass()
    {
        $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
        self::$con  = new PDO("sqlite:$file");

        TORM\Connection::setConnection(self::$con, "test");
        TORM\Connection::setDriver("sqlite");
        TORM\Factory::setFactoriesPath("./factories");
        TORM\Log::enable(false);
    }

    /**
     * Test getting factories
     *
     * @return null
     */
    public function testGetFactories()
    {
        $this->assertEquals(6, TORM\Factory::factoriesCount());
    }

    /**
     * Test getting a specific factory
     *
     * @return null
     */
    public function testGetFactory()
    {
        $this->assertNotNull(TORM\Factory::get("user"));
    }

    /**
     * Test a factory with different class
     *
     * @return null
     */
    public function testFactoryWithDifferentClass()
    {
        $admin = TORM\Factory::build("admin");
        $this->assertNotNull($admin);
        $this->assertEquals("User", get_class($admin));
    }

    /**
     * Test building a factory
     *
     * @return null
     */
    public function testBuildFactory()
    {
        $user = TORM\Factory::build("user");
        $this->assertEquals("User", get_class($user));
        $this->assertEquals("Mary Doe", $user->name);
        $this->assertEquals("mary@doe.com", $user->email);
    }

    /**
     * Test factory attributes
     *
     * @return null
     */
    public function testAttributes()
    {
        $data = TORM\Factory::attributes_for("user");
        $this->assertNotNull($data);
        $this->assertTrue(is_array($data));
        $this->assertEquals("Mary Doe", $data["name"]);
        $this->assertEquals("mary@doe.com", $data["email"]);
    }

    /**
     * Test factory creation
     *
     * @return null
     */
    public function testCreateFactory()
    {
        $user = TORM\Factory::create("user");
        $this->assertEquals("User", get_class($user));
        $this->assertNotNull($user->id);
        $this->assertEquals("Mary Doe", $user->name);
        $this->assertEquals("mary@doe.com", $user->email);
        $this->assertNotNull(User::find($user->id));
        $this->assertTrue($user->destroy());
    }
}
?>
