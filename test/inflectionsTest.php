<?php
/**
 * Inflection class tests
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
require_once "../models/ticket.php";
require_once "../models/account.php";

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
class InflectionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Run before each test
     *
     * @return null
     */
    public function setUp()
    {
        TORM\Inflections::push(TORM\Inflections::IRREGULAR, "person",  "people");
        TORM\Inflections::push(TORM\Inflections::SINGULAR,  '/ao$/i',  "oes");
        TORM\Inflections::push(TORM\Inflections::PLURAL,    '/oes$/i', "ao");
    }

    /**
     * Test pluralization
     *
     * @return null
     */
    public function testPluralize()
    {
        $this->assertEquals("people", TORM\Inflections::pluralize("person"));
    }

    /**
     * Test singularization
     *
     * @return null
     */
    public function testSingularize()
    {
        $this->assertEquals("person", TORM\Inflections::singularize("people"));
    }

    /**
     * Test default pluralization
     *
     * @return null
     */
    public function testDefaultPluralize()
    {
        $this->assertEquals("books", TORM\Inflections::pluralize("book"));
    }

    /**
     * Test default singularization
     *
     * @return null
     */
    public function testDefaultSingularize()
    {
        $this->assertEquals("book", TORM\Inflections::singularize("books"));
    }

    /**
     * Test pluralization with regex
     *
     * @return null
     */
    public function testPluralizeWithRegex()
    {
        $this->assertEquals("acoes", TORM\Inflections::pluralize("acao"));
    }

    /**
     * Test singularization with regex
     *
     * @return null
     */
    public function testSingularizeWithRegex()
    {
        $this->assertEquals("acao", TORM\Inflections::singularize("acoes"));
    }
}
?>
