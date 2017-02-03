<?php
/**
 * Model class tests
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
require_once "../models/user_namespaced.php";
require_once "../models/another_user.php";
require_once "../models/ticket.php";
require_once "../models/account.php";
require_once "../models/bill.php";

/**
 * Class for belongsTo tests
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Tocket extends TORM\Model
{
    /**
     * PK override
     *
     * @return new id
     */
    public static function getNewPKValue()
    {
        return time() + mt_rand(1, 10000);
    }
}
Tocket::belongsTo("person",       ["class_name"  => "User", "foreign_key" => "user_id"]);
Tocket::belongsTo("other_person", ["class_name"  => "User", "foreign_key" => "user2_id"]);

/**
 * Model test main class 
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class ModelTest extends PHPUnit_Framework_TestCase
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
        $database = self::_database();
        $con      = self::_connection();

        TORM\Connection::setConnection($con, "test");
        TORM\Connection::setEncoding("UTF-8");
        TORM\Connection::setDriver($database);
        TORM\Factory::setFactoriesPath("./factories");
        TORM\Log::enable(false);

        self::$user             = new User();
        self::$user->id         = 1;
        self::$user->name       = "John Doe Jr.";
        self::$user->email      = "jr@doe.com";
        self::$user->code       = "12345";
        self::$user->user_level = 1;

        error_reporting(E_ALL);
    }

    /**
     * Find the test connection
     *
     * @return resource connection
     */
    private static function _connection()
    {
        $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");

        $database = self::_database();
        echo "Testing using $database\n";

        switch ($database) {
        case "sqlite":
            self::$con = new PDO("sqlite:$file");
            break;
        case "mysql":
            self::$con = new PDO('mysql:host=localhost;dbname=torm', "torm", "torm");
            break;
        case "postgresql":
            self::$con = new PDO('pgsql:host=localhost;dbname=torm', "torm", "torm");
            break;
        case "oracle":
            self::$con = new PDOOCI\PDO('docker', 'system', 'oracle');
            self::$con->query("alter session set NLS_DATE_FORMAT='YYYY-MM-DD'");
            self::$con->query("alter session set NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS'");
        }
        return self::$con;
    }

    /**
     * Find the test database
     *
     * @return String database
     */
    private static function _database()
    {
        $database = getenv("TORM_DATABASE_TEST");
        $database = strlen($database) > 0 ? $database : "sqlite";
        return $database;
    }

    /**
     * Setup data
     *
     * @return null
     */
    private static function _seed()
    {
        $database = self::_database();

        User::all()->destroy();
        Bill::all()->destroy();
        Ticket::all()->destroy();
        Tocket::all()->destroy();
        Account::all()->destroy();

        $user             = new User();
        $user->id         = 1;
        $user->name       = "Eustaquio Rangel";
        $user->email      = "eustaquiorangel@gmail.com";
        $user->code       = "12345";
        $user->user_level = 1;
        $user->save();

        $user             = new User();
        $user->id         = 2;
        $user->name       = "Rangel, Eustaquio";
        $user->email      = "taq@bluefish.com.br";
        $user->code       = "54321";
        $user->user_level = 2;
        $user->save();

        for ($i = 1; $i <= 10; $i++) {
            $bill              = new Bill();
            $bill->id          = $i;
            $bill->user_id     = 1;
            $bill->description = "Bill #$i";
            $bill->value       = $i;
            $bill->save();

            $ticket = new Ticket();
            $ticket->user_id = 1;
            $ticket->description = "Just another ticket";
            $ticket->save();
        }

        $account = new Account();
        $account->user_id        = 1;
        $account->account_number = "12345";
        $account->save();

        $account = new Account();
        $account->user_id        = 2;
        $account->account_number = "54321";
        $account->save();
    }

    /**
     * Set up tests
     *
     * @return null
     */
    public function setUp()
    {
        self::_seed();
    }

    /**
     * Test connection
     *
     * @return null
     */
    public function testConnection()
    {
        $this->assertNotNull(self::$con);
        $this->assertEquals(self::$con, TORM\Connection::getConnection("test"));
    }      

    /**
     * Test find method
     *
     * @return null
     */
    public function testFind()
    {
        $user = User::find(1);
        $this->assertEquals("Eustaquio Rangel", $user->name);
        $this->assertEquals("eustaquiorangel@gmail.com", $user->email);
    }

    /**
     * Test not found
     *
     * @return null
     */
    public function testNotFound()
    {
        $user = User::find(10);
        $this->assertNull($user);
    }

    /**
     * Test setting an attribute
    *
     * @return null
     */
    public function testSetAttribute()
    {
        $user = User::find(1);
        $user->name = "John Doe";
        $this->assertEquals("John Doe", $user->name);
    }

    /**
     * Test first method
     *
     * @return null
     */
    public function testFirst() 
    {
        $user = User::first();
        $this->assertEquals("Eustaquio Rangel", $user->name);
    }

    /**
     * Test first method, with condition
     *
     * @return null
     */
    public function testFirstWithCondition() 
    {
        $user = User::first(array("email"=>"eustaquiorangel@gmail.com"));
        $this->assertEquals("Eustaquio Rangel", $user->name);
    }

    /**
     * Test not found using first
     *
     * @return null
     */
    public function testFirstNotFound()
    {
        $user = User::first(array("email" => "yoda@gmail.com"));
        $this->assertNull($user);
    }

    /**
     * Test last method
     *
     * @return null
     */
    public function testLast()
    {
        $user = User::last();
        $this->assertEquals("Rangel, Eustaquio", $user->name);
    }

    /**
     * Test last with condition
     *
     * @return null
     */
    public function testLastWithCondition() 
    {
        $user = User::last(array("email" => "taq@bluefish.com.br"));
        $this->assertEquals("Rangel, Eustaquio", $user->name);
    }

    /**
     * Test last not found
     *
     * @return null
     */
    public function testLastNotFound() 
    {
        $user = User::last(array("email" => "yoda@gmail.com"));
        $this->assertNull($user);
    }

    /**
     * Test where method
     *
     * @return null
     */
    public function testWhere() 
    {
        $users = User::where(array("name" => "Eustaquio Rangel"));
        $user  = $users->next();
        $this->assertEquals("Eustaquio Rangel", $user->name);
    }

    /**
     * Test where method with string
     *
     * @return null
     */
    public function testWhereWithString() 
    {
        $users = User::where("name='Eustaquio Rangel'");
        $user  = $users->next();
        $this->assertEquals("Eustaquio Rangel", $user->name);
    }

    /**
     * Test all method
     *
     * @return null
     */
    public function testAll() 
    {
        $users = User::all();
        $user  = $users->next();
        $this->assertEquals("Eustaquio Rangel", $user->name);

        $user  = $users->next();
        $this->assertEquals("Rangel, Eustaquio", $user->name);

        echo "checking all users ...\n";
        $count = 0;

        foreach (User::all() as $user) {
            echo "user: ".$user->name."\n";
            $count ++;
        }
        $this->assertEquals(2, $count);

        $count = 0;
        $pos   = 1;
        foreach (Bill::all() as $bill) {
            $this->assertEquals("Bill #$pos", $bill->description);
            $this->assertEquals($pos, $bill->value);
            $pos ++;
            $count ++;
        }
        $this->assertEquals(10, $count);
    }

    /**
     * Test insert method
     *
     * @return null
     */
    public function testInsert()
    {
        $user = new User();
        $user->name       = "John Doe";
        $user->email      = "john@doe.com";
        $user->user_level = 1;
        $user->code       = "12345";
        $this->assertTrue($user->isValid());
        $this->assertNull($user->id);
        $this->assertTrue($user->save());
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->created_at);

        $new_user = User::find($user->id);
        $this->assertNotNull($new_user);
        $this->assertEquals($user->name,  $new_user->name);
        $this->assertEquals($user->email, $new_user->email);
        $this->assertEquals($user->user_level, $new_user->user_level);
        $this->assertEquals($user->code,  $new_user->code);
    }

    /**
     * Test no created at column
     *
     * @return null
     */
    public function testInsertNoCreatedAtColumn() 
    {
        $ticket              = new Ticket();
        $ticket->description = "A new ticket";
        $ticket->user_id     = 1;
        $this->assertTrue($ticket->save());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test update method
     *
     * @return null
     */
    public function testUpdate() 
    {
        $user = TORM\Factory::create("user");
        $user = User::where("email='".$user->email."'")->next();
        $id   = $user->id;
        $user->name = "Doe, John";
        $user->save();

        $this->assertEquals("Doe, John", User::find($id)->name);
        $this->assertTrue($user->isValid());
        $user->save();
    }

    /**
     * Test updated at column
     *
     * @return null
     */
    public function testUpdateNoUpdatedAtColumn() 
    {
        $ticket = Ticket::first();
        $old_desc = $ticket->description;
        $ticket->description = "New description";
        $this->assertTrue($ticket->save());
        $ticket->description = $old_desc;
        $this->assertTrue($ticket->save());
    }

    /**
     * Test destroy method
     *
     * @return null
     */
    public function testDestroy() 
    {
        $user = TORM\Factory::create("user");
        $user = User::where("email='".$user->email."'")->next();
        $this->assertTrue($user->destroy());
    }

    /**
     * Test invalid presence
     *
     * @return null
     */
    public function testInvalidPresence() 
    {
        self::$user->name = null;
        $this->assertFalse(self::$user->isValid());
    }

    /**
     * Test valid presence
     *
     * @return null
     */
    public function testValidPresence() 
    {
        self::$user->name = "John Doe Jr.";
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test invalid format
     *
     * @return null
     */
    public function testInvalidFormat() 
    {
        self::$user->email = "yadda@yadda";
        $this->assertFalse(self::$user->isValid());
    }

    /**
     * Test valid format
     *
     * @return null
     */
    public function testValidFormat()
    {
        self::$user->email = "jr@doe.com";
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test empty format
     *
     * @return null
     */
    public function testEmptyFormat() 
    {
        self::$user->code = "";
        $this->assertFalse(self::$user->isValid());
    }

    /**
     * Test null format
     *
     * @return null
     */
    public function testNullFormat() 
    {
        self::$user->code = null;
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test uniqueness
     *
     * @return null
     */
    public function testUniqueness() 
    {
        $old_user = User::find(1);
        $new_user = new User();
        $new_user->name  = $old_user->name;
        $new_user->email = $old_user->email;
        $this->assertFalse($new_user->isValid());
        $this->assertEquals(TORM\Validation::VALIDATION_UNIQUENESS, $new_user->errors["email"][0]);
    }

    /**
     * Test not numeric value
     *
     * @return null
     */
    public function testNotANumber() 
        {
        self::$user->user_level = "one";
        $this->assertFalse(self::$user->isValid());
    }

    /**
     * Test not numeric value (using special chars)
     *
     * @return null
     */
    public function testNotANumberWithSpecialChars() 
    {
        self::$user->user_level = "$%@";
        $this->assertFalse(self::$user->isValid());
    }

    /**
     * Test positive number
     *
     * @return null
     */
    public function testAPositiveNumber() 
    {
        self::$user->user_level = 1;
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test negative number
     *
     * @return null
     */
    public function testANegativeNumber() 
    {
        self::$user->user_level = -1;
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test floating point number
     *
     * @return null
     */
    public function testAFloatingPointNumber()
    {
        self::$user->user_level = 1.23;
        $this->assertTrue(self::$user->isValid());
    }

    /**
     * Test cant save invalid object
     *
     * @return null
     */
    public function testCantSaveInvalidObject() 
    {
        $user = new User();
        $this->assertFalse($user->save());
    }

    /**
     * Test limit
     *
     * @return null
     */
    public function testLimit() 
    {
        $users = User::where(array("name" => "Eustaquio Rangel"))->limit(1);
        $this->assertNotNull($users->next());
        $this->assertNull($users->next());
    }

    /**
     * Test order
     *
     * @return null
     */
    public function testOrder() 
    {
        $users = User::where("name like '%Rangel%'")->order("email desc")->limit(1);
        $user  = $users->next();
        $this->assertNotNull($user);
        $this->assertEquals("taq@bluefish.com.br", $user->email);
    }

    /**
     * Test has many
     *
     * @return null
     */
    public function testHasMany() 
    {
        $user    = User::find(1);
        $tickets = $user->tickets;
        $this->assertNotNull($tickets);
        echo "\ntickets:\n";

        $ids   = array();
        $count = 0;
        foreach ($tickets as $ticket) {
            echo "ticket: ".$ticket->id." ".$ticket->description."\n";
            array_push($ids, $ticket->id);
            $count ++;
        }

        $this->assertNotNull($user->ticket_ids);
        $this->assertEquals(sizeof($ids), sizeof($user->ticket_ids));
        $this->assertEquals(10, $count);

        foreach ($ids as $id) {
            $this->assertTrue(in_array($id, $user->ticket_ids));
        }
    }

    /**
     * Test has many ids
     *
     * @return null
     */
    public function testHasManyUpdateIds() 
    {
        $user    = User::find(1);
        $ticket  = TORM\Factory::build("ticket");
        $ticket->user_id = $user->id;
        $this->assertTrue($ticket->save());
        $this->assertEquals(11, $user->tickets->count());

        $t1 = Ticket::first();
        $t2 = Ticket::last();

        $user->ticket_ids = [$t1->id, $t2->id];
        $this->assertEquals(2, $user->tickets->count());
        $this->assertNotNull(Ticket::find($ticket->id));
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test belongs
     *
     * @return null
     */
    public function testBelongs()
    {
        $ticket = Ticket::first();
        $this->assertNotNull($ticket);

        $user1 = $ticket->user;
        $u1h   = spl_object_hash($user1);
        $this->assertNotNull($user1);

        $user2 = $ticket->user;
        $u2h   = spl_object_hash($user2);
        $this->assertNotNull($user2);
        $this->assertEquals($u1h, $u2h);
    }

    /**
     * Belongs benchmark
     *
     * @return null
     */
    public function testBelongsBenchmark()
    {
        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $limit = 10000;
        $m1 = microtime(true);
        for ($i = 0; $i < $limit; $i++) {
            $user = $ticket->user;
        }
        $m2 = microtime(true);
        echo "time to retrieve $limit users: ".($m2 - $m1);
    }

    /**
     * Belongs attribution
     *
     * @return null
     */
    public function testBelongsAttribution()
    {
        $user             = new User();
        $user->name       = "Belongs attribution";
        $user->email      = "belongs@torm.com";
        $user->code       = "01010";
        $user->user_level = 1;
        $this->assertTrue($user->save());

        $ticket              = new Ticket();
        $ticket->user        = $user;
        $ticket->description = "Test";
        $this->assertTrue($ticket->save());

        $this->assertNotEquals(self::$user->id, $user->id);
        $this->assertNotNull($user->id);
        $this->assertEquals($ticket->user_id, $user->id);
        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Belongs attribution, from where
     *
     * Made for issue 12: https://github.com/taq/torm/issues/12
     *
     * @return null
     */
    public function testBelongsAttributionFromWhere()
    {
        $user             = new User();
        $user->name       = "Belongs attribution from where";
        $user->email      = "belongswhere@torm.com";
        $user->code       = "01010";
        $user->user_level = 1;
        $this->assertTrue($user->save());

        Ticket::belongsTo("person", ["class_name"  => "User", "foreign_key" => "user_id"]);

        $users               = [$user->code];
        $ticket              = new Ticket();
        $ticket->description = "Test";
        $ticket->person      = User::where(["code" => $users[0]])->current();
        $this->assertEquals($user->name, $ticket->person->name);
        $this->assertTrue($ticket->save());

        $this->assertNotEquals(self::$user->id, $user->id);
        $this->assertNotNull($user->id);
        $this->assertEquals($ticket->person->id, $user->id);

        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Belongs attribution, from where, with different class
     *
     * Made for issue 12: https://github.com/taq/torm/issues/12
     *
     * @return null
     */
    public function testBelongsAttributionFromWhereOtherClass()
    {
        $user1             = new User();
        $user1->name       = "Belongs attribution from where and other class (1)";
        $user1->email      = "belongswhere@torm.com";
        $user1->code       = "01011";
        $user1->user_level = 1;
        $this->assertTrue($user1->save());

        $user2             = new User();
        $user2->name       = "Belongs attribution from where and other class (2)";
        $user2->email      = "belongswhere2@torm.com";
        $user2->code       = "01012";
        $user2->user_level = 1;
        $this->assertTrue($user2->save());

        $uid1   = $user1->id;
        $uname1 = $user1->name;

        $uid2   = $user2->id;
        $uname2 = $user2->name;

        $users                = [$user1->code, $user2->code];
        $ticket               = new Tocket();
        $ticket->description  = "Test";
        $ticket->person       = User::where(["code" => $users[0]])->current();
        $ticket->other_person = User::where(["code" => $users[1]])->current();

        $this->assertEquals($uname1, $ticket->person->name);
        $this->assertEquals($uname2, $ticket->other_person->name);

        $this->assertTrue($user1->destroy());
        $this->assertTrue($user2->destroy());

        $this->assertTrue($ticket->save());

        $this->assertNotEquals(self::$user->id, $uid1);
        $this->assertNotEquals(self::$user->id, $uid2);

        $this->assertNotNull($uid1);
        $this->assertNotNull($uid2);

        $this->assertEquals($ticket->person->id, $uid1);
        $this->assertEquals($ticket->other_person->id, $uid2);

        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test empty sequence
     *
     * @return null
     */
    public function testCheckEmptySequence() 
    {
        $name = null;
        switch (TORM\Driver::$name) {
        case "oracle":
            $name = "users_sequence";
            break;
        case "postgresql":
            $name = "users_id_seq";
            break;
        }
        $this->assertEquals($name, User::resolveSequenceName());
    }

    /**
     * Test default sequence
     *
     * @return null
     */
    public function testDefaultSequence() 
    {
        $name = null;
        switch (TORM\Driver::$name) {
        case "oracle":
            $name = "users_sequence";
            break;
        case "postgresql":
            $name = "users_id_seq";
            break;
        }

        $old = TORM\Driver::$primary_key_behaviour;
        TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
        $name = User::resolveSequenceName();
        $this->assertEquals($name, User::resolveSequenceName());
        TORM\Driver::$primary_key_behaviour = $old;
    }

    /**
     * Test named sequence
     *
     * @return null
     */
    public function testNamedSequence() 
    {
        $old = TORM\Driver::$primary_key_behaviour;
        TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
        $test = "yadda_sequence";
        User::setSequenceName($test);
        $name = User::resolveSequenceName();
        $this->assertEquals($test, User::resolveSequenceName());
        TORM\Driver::$primary_key_behaviour = $old;
    }

    /**
     * Test if can't change primary key
     *
     * @return null
     */
    public function testCantChangeExistingPK() 
    {
        $user = User::find(1);
        $old  = $user->id;
        $user->id = 10;
        $this->assertEquals($old, $user->id);
    }

    /**
     * Test it can change empty primary key
     *
     * @return null
     */
    public function testCanChangeNewPK() 
    {
        $user = new User();
        $new  = 10;
        $user->id = $new;
        $this->assertEquals($new, $user->id);
    }

    /**
     * Test count method
     *
     * @return null
     */
    public function testCount() 
    {
        $this->assertEquals(2, User::all()->count());
    }

    /**
     * Test count method, with conditions
     *
     * @return null
     */
    public function testCountWithConditions() 
    {
        $this->assertEquals(1, User::all(array("email" => "eustaquiorangel@gmail.com"))->count());
    }

    /**
     * Test where and count methods
     *
     * @return null
     */
    public function testCountWithConditionsAndWhere() 
    {
        $this->assertEquals(1, User::where(array("email" => "eustaquiorangel@gmail.com"))->count());
    }

    /**
     * Test sum method
     *
     * @return null
     */
    public function testSum() 
    {
        $this->assertEquals(3, User::all()->sum("user_level"));
    }

    /**
     * Test sum method, with conditions
     *
     * @return null
     */
    public function testSumWithConditions() 
    {
        $this->assertEquals(2, User::all(array("email" => "taq@bluefish.com.br"))->sum("user_level"));
    }

    /**
     * Test sum method, with conditions and where
     *
     * @return null
     */
    public function testSumWithConditionsAndWhere() 
    {
        $this->assertEquals(1, User::where(array("email" => "eustaquiorangel@gmail.com"))->sum("user_level"));
    }

    /**
     * Test average method
     *
     * @return null
     */
    public function testAvg() 
    {
        $this->assertEquals(1.5, User::all()->avg("user_level"));
    }

    /**
     * Test average method, with conditions
     *
     * @return null
     */
    public function testAvgWithConditions() 
    {
        $this->assertEquals(2, User::all(array("email" => "taq@bluefish.com.br"))->avg("user_level"));
    }

    /**
     * Test average method, with conditions and where
     *
     * @return null
     */
    public function testAvgWithConditionsAndWhere() 
    {
        $this->assertEquals(1, User::where(array("email" => "eustaquiorangel@gmail.com"))->avg("user_level"));
    }

    /**
     * Test min method
     *
     * @return null
     */
    public function testMin() 
    {
        $this->assertEquals(1, User::all()->min("user_level"));
    }

    /**
     * Test min method, with conditions
     *
     * @return null
     */
    public function testMinWithConditions() 
    {
        $this->assertEquals(2, User::all(array("email"=>"taq@bluefish.com.br"))->min("user_level"));
    }

    /**
     * Test min method, with conditions and where
     *
     * @return null
     */
    public function testMinWithConditionsAndWhere() 
    {
        $this->assertEquals(1, User::where(array("email"=>"eustaquiorangel@gmail.com"))->min("user_level"));
    }

    /**
     * Test max method
     *
     * @return null
     */
    public function testMax() 
    {
        $this->assertEquals(2, User::all()->max("user_level"));
    }

    /**
     * Test max method, with conditions
     *
     * @return null
     */
    public function testMaxWithConditions() 
    {
        $this->assertEquals(2, User::all(array("email"=>"taq@bluefish.com.br"))->max("user_level"));
    }

    /**
     * Test max method, with conditions and where
     *
     * @return null
     */
    public function testMaxWithConditionsAndWhere() 
    {
        $this->assertEquals(1, User::where(array("email"=>"eustaquiorangel@gmail.com"))->max("user_level"));
    }

    /**
     * Test destroying the collection
     *
     * @return null
     */
    public function testDestroyCollection() 
    {
        $users = User::all();
        $user1 = $users->next();
        $user2 = $users->next();
        User::all()->destroy();

        $this->assertEquals(0, User::all()->count());
        $this->assertTrue($user1->save());
        $this->assertTrue($user2->save());
        $this->assertEquals(2, User::all()->count());
    }

    /**
     * Test destroying collection, with conditions
     *
     * @return null
     */
    public function testDestroyCollectionWithConditions() 
    {
        $cond  = array("email"=>"eustaquiorangel@gmail.com");
        $users = User::all($cond);
        $user1 = $users->next();
        User::all($cond)->destroy();

        $this->assertEquals(1, User::all()->count());
        $this->assertTrue($user1->save());
        $this->assertEquals(2, User::all()->count());
    }

    /**
     * Test update attributes
     *
     * @return null
     */
    public function testUpdateAttributes() 
    {
        $new_level = 3;
        $new_email = "iwishigottaq@gmail.com";

        $user = User::find(1);
        $old_level = $user->user_level;
        $old_email = $user->email;
        $user->updateAttributes(array("email"=>$new_email, "user_level"=>$new_level));

        $user = User::find(1);
        $this->assertEquals($new_level, $user->user_level);
        $this->assertEquals($new_email, $user->email);
        $user->updateAttributes(array("email"=>$old_email, "user_level"=>$old_level));

        $user = User::find(1);
        $this->assertEquals($old_level, $user->user_level);
        $this->assertEquals($old_email, $user->email);
    }

    /**
     * Test can't update primary key
     *
     * @return null
     */
    public function testCantUpdatePKAttributes() 
    {
        $account = TORM\Factory::create("account");
        $this->assertTrue($account->save());

        $account = Account::first();
        $old_id  = $account->id;
        $new_id  = 999;
        $new_num = "54321";

        $this->assertTrue($account->updateAttributes(array("id"=>$new_id, "account_number"=>$new_num)));
        $account = Account::find($old_id);
        $this->assertNotNull($account);
        $this->assertEquals($new_num, $account->account_number);
        $this->assertNull(Account::find($new_id));
    }

    /**
     * Test update attributes on collection
     *
     * @return null
     */
    public function testUpdateAttributesOnCollection() 
    {
        $users = User::all();
        $user1 = $users->next();
        $user2 = $users->next();

        User::all()->updateAttributes(array("email"=>"void@gmail.com", "user_level"=>0));
        $users = User::all();
        while ($user = $users->next()) {
            $this->assertEquals("void@gmail.com", $user->email);
            $this->assertEquals(0, $user->user_level);
        }

        $this->assertTrue($user1->save(true));
        $this->assertTrue($user2->save(true));
    }

    /**
     * Test update attributes on collection, with conditions
     *
     * @return null
     */
    public function testUpdateAttributesOnCollectionWithConditions() 
    {
        $cond  = array("email"=>"eustaquiorangel@gmail.com");
        $users = User::where($cond);
        $user1 = $users->next();
        $this->assertNotNull($user1);

        User::where($cond)->updateAttributes(array("email"=>"void@gmail.com", "user_level"=>0));
        $users = User::where($cond);

        while ($user = $users->next()) {
            $this->assertEquals("void@gmail.com", $user->email);
            $this->assertEquals(0, $user->user_level);
        }
        $this->assertTrue($user1->save(true));
    }

    /**
     * Test primary key method
     *
     * @return null
     */
    public function testPKMethod() 
    {
        $user   = User::first();
        $ticket = new Ticket();
        $ticket->user_id     = $user->id;
        $ticket->description = "pk value test";
        $this->assertTrue($ticket->save());

        $ticket = Ticket::last();
        $this->assertTrue($ticket->id>=time()-1000);
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test has one relation
     *
     * @return null
     */
    public function testHasOneRelation() 
    {
        $account = Account::first();

        $user = User::first();
        $acct = $user->account;
        $this->assertNotNull($acct);
        $this->assertEquals("Account", get_class($acct));
        $this->assertEquals($account->account_number, $acct->account_number);
    }

    /**
     * Has one benchmark
     *
     * @return null
     */
    public function testHasOneBenchmark()
    {
        $user    = User::first();
        $account = TORM\Factory::create("account");
        $limit   = 10000;
        $m1      = microtime(true);

        for ($i = 0; $i < $limit; $i++) {
            $acct = $user->account;
        }
        $m2 = microtime(true);
        echo "time to retrieve $limit accounts: ".($m2 - $m1);
    }

    /**
     * Test get method
     *
     * @return null
     */
    public function testGet() 
    {
        $user = TORM\Factory::build("user");
        $this->assertNotNull($user->get("name"));
    }

    /**
     * Test null get
     *
     * @return null
     */
    public function testNullGet() 
    {
        $user = TORM\Factory::build("user");
        $this->assertNull($user->get("yadda"));
    }

    /**
     * Test if created_at column exists
     *
     * @return null
     */
    public function testHasCreateColumn() 
    {
        $user = TORM\Factory::build("user");
        $this->assertTrue(array_key_exists("created_at", $user->getData()));
    }

    /**
     * Test if created_at column does not exists
     *
     * @return null
     */
    public function testHasNotCreateColumn() 
    {
        $account = TORM\Factory::build("account");
        $this->assertFalse(array_key_exists("created_at", $account->getData()));
    }


    /**
     * Test if updated_at column exists
     *
     * @return null
     */
    public function testHasUpdateColumn() 
    {
        $user = TORM\Factory::build("user");
        $this->assertTrue(array_key_exists("updated_at", $user->getData()));
    }


    /**
     * Test if updated_at column does not exists
     *
     * @return null
     */
    public function testHasNotUpdateColumn() 
    {
        $account = TORM\Factory::build("account");
        $this->assertFalse(array_key_exists("updated_at", $account->getData()));
    }

    /**
     * Test has many association
     *
     * @return null
     */
    public function testHasHasManyRelation() 
    {
        $account = TORM\Factory::build("account");
        $user    = TORM\Factory::build("user");

        $this->assertFalse(Account::hasHasMany("users"));
        $this->assertTrue(User::hasHasMany("tickets"));
    }

    /**
     * Test has many class
     *
     * @return null
     */
    public function testHasManyClass() 
    {
        $user = TORM\Factory::build("user");
        $this->assertEquals("Ticket", User::hasManyClass("tickets"));
    }

    /**
     * Test has many foreign key
     *
     * @return null
     */
    public function testHasManyForeignKey() 
    {
        $user = TORM\Factory::build("user");
        $this->assertEquals("user_id", User::hasManyForeignKey("tickets"));
    }

    /**
     * Test model table name
     *
     * @return null
     */
    public function testModelTableName() 
    {
        $this->assertEquals("users", User::getTableName());
        $this->assertEquals("users", Test\User::getTableName());
        Test\User::setTableName('mydata');
        $this->assertEquals("mydata", Test\User::getTableName());
    }

    /**
     * Test model table name, by string
     *
     * @return null
     */
    public function testModelTableNameByString() 
    {
        $this->assertEquals("tickets", TORM\Model::getTableName("Ticket"));
    }

    /**
     * Test pushing object with id
     *
     * @return null
     */
    public function testPushReceiverWithIdObjectWithId() 
    {
        $user    = TORM\Factory::create("user");
        $ticket  = TORM\Factory::create("ticket");

        $this->assertEquals(0, $user->tickets->count());
        $user->push($ticket);
        $this->assertEquals($user->id, $ticket->user_id);
        $this->assertEquals(1, $user->tickets->count());

        $ticket = Ticket::find($ticket->id);
        $this->assertEquals($user->id, $ticket->user->id);
        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test pushing object with no id
     *
     * @return null
     */
    public function testPushReceiverWithoutIdObjectWithId() 
    {
        $attrs       = TORM\Factory::attributes_for("user");
        $attrs["id"] = null;

        $user    = new User($attrs);
        $ticket  = TORM\Factory::create("ticket");

        $this->assertEquals(0, $user->tickets->count());
        $user->push($ticket);
        $this->assertTrue($user->save());
        $this->assertEquals(1, $user->tickets->count());

        $ticket = Ticket::find($ticket->id);
        $this->assertEquals($user->id, $ticket->user->id);
        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test pushing object with no id
     *
     * @return null
     */
    public function testPushReceiverWithIdObjectWithoutId() 
    {
        $user    = TORM\Factory::create("user");
        $ticket  = TORM\Factory::build("ticket");

        $this->assertEquals(0, $user->tickets->count());
        $user->push($ticket);
        $this->assertEquals(1, $user->tickets->count());

        $ticket = Ticket::find($ticket->id);
        $this->assertEquals($user->id, $ticket->user->id);
        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test pushing object without id
     *
     * @return null
     */
    public function testPushReceiverWithoutIdObjectWithoutId() 
    {
        $user    = TORM\Factory::build("user");
        $ticket  = TORM\Factory::build("ticket");

        $user->push($ticket);
        $this->assertTrue($user->save());

        $ticket = Ticket::find($ticket->id);
        $this->assertEquals($user->id, $ticket->user->id);
        $this->assertTrue($user->destroy());
        $this->assertTrue($ticket->destroy());
    }

    /**
     * Test many collection
     *
     * @return null
     */
    public function testManyCollection() 
    {
        $user = TORM\Factory::create("user");
        $t1   = TORM\Factory::create("ticket");
        $t2   = TORM\Factory::create("ticket");

        $this->assertEquals(0, $user->tickets->count());
        $user->tickets = array($t1, $t2);
        $this->assertEquals(2, $user->tickets->count());

        $this->assertTrue($user->destroy());
        $this->assertTrue($t1->destroy());
        $this->assertTrue($t2->destroy());
    }

    /**
     * Test callback file
     *
     * @param string $file file name
     *
     * @return null
     */
    private function _checkCallbackFile($file) 
    {
        $user = TORM\Factory::build("user");
        if (file_exists($file)) {
            unlink($file);
        }

        $this->assertFalse(file_exists($file));
        $this->assertTrue($user->save());
        $this->assertTrue($user->destroy());
        $this->assertTrue(file_exists($file));
    }

    /**
     * Test before save method
     *
     * @return null
     */
    public function testBeforeSave() 
    {
        $this->_checkCallbackFile("/tmp/torm-before-save.log");
    }

    /**
     * Test after save method
     *
     * @return null
     */
    public function testAfterSave() 
    {
        $this->_checkCallbackFile("/tmp/torm-after-save.log");
    }

    /**
     * Test before create method
     *
     * @return null
     */
    public function testBeforeCreate()
    {
        $this->_checkCallbackFile("/tmp/torm-before-create.log");
    }

    /**
     * Test after create method
     *
     * @return null
     */
    public function testAfterCreate() 
    {
        $this->_checkCallbackFile("/tmp/torm-after-create.log");
    }

    /**
     * Test before update method
     *
     * @return null
     */
    public function testBeforeUpdate()
    {
        $file = "/tmp/torm-before-update.log";
        if (file_exists($file)) {
            unlink($file);
        }

        $user = TORM\Factory::create("user");
        $this->assertFalse(file_exists($file));
        $this->assertTrue($user->save());
        $this->assertTrue(file_exists($file));
    }

    /**
     * Test after update method
     *
     * @return null
     */
    public function testAfterUpdate()
    {
        $user = TORM\Factory::build("user");
        $this->assertTrue($user->save());

        $file = "/tmp/torm-after-update.log";
        if (file_exists($file)) {
            unlink($file);
        }

        $this->assertFalse(file_exists($file));
        $this->assertTrue($user->save());
        $this->assertTrue(file_exists($file));
    }

    /**
     * Test before destroy method
     *
     * @return null
     */
    public function testBeforeDestroy() 
    {
        $this->_checkCallbackFile("/tmp/torm-before-destroy.log");
    }

    /**
     * Test after destroy method
     *
     * @return null
     */
    public function testAfterDestroy() 
    {
        $this->_checkCallbackFile("/tmp/torm-before-save.log");
    }

    /**
     * Test if can create scopes
     *
     * @return null
     */
    public function testMustRespondToAScopeDefinitionMethod() 
    {
        $this->assertTrue(method_exists("User", "scope"));
    }

    /**
     * Test if respond to scope as a method
     *
     * @return null
     */
    public function testMustRespondToAScopeAsAMethod() 
    {
        $this->assertEquals(1, User::first_level()->count());
    }

    /**
     * Test if respond to scope as a method, with parameters
     *
     * @return null
     */
    public function testMustRespondToAScopeAsAMethodWithParameters() 
    {
        $this->assertEquals(1, User::by_level(1)->count());
    }

    /**
     * Test if respond to scope as a method, with multiple parameters
     *
     * @return null
     */
    public function testMustRespondToAScopeAsAMethodWithMultipleParameters() 
    {
        $this->assertEquals(1, User::by_level_and_date(1, date("Y-m-d", strtotime("+2 days")))->count());
    }

    /**
     * Test doe mail user
     *
     * @return null
     */
    public function testDoeUser()
    {
        $user = TORM\Factory::create("user");
        $this->assertEquals(1, User::doe()->count());
        $this->assertTrue($user->destroy());
    }

    /**
     * Test first mail name scope
     *
     * @return null
     */
    public function testFirstMailNameScope()
    {
        $user = TORM\Factory::create("user");
        $this->assertEquals(1, User::email_first("mary")->count());
        $this->assertTrue($user->destroy());
    }

    /**
     * Test chained scopes
     *
     * @return null
     */
    public function testChainedScopes()
    {
        $this->assertEquals(0, User::by_level(1)->doe()->count());

        $user = TORM\Factory::create("user");
        $this->assertEquals(1, User::by_level(1)->doe()->count());
        $this->assertEquals(1, User::by_level(1)->doe()->email_first("mary")->count());

        $user->updateAttributes(array("email" => "marilyn@doe.com"));
        $this->assertEquals(0, User::by_level(1)->doe()->email_first("mary")->count());
        $this->assertEquals(1, User::by_level(1)->doe()->email_first("marilyn")->count());
        $this->assertTrue($user->destroy());
    }

    /**
     * Test validations with accented chars
     *
     * @return null
     */
    public function testAccentedCharsOnValidation() 
    {
        $user = TORM\Factory::build("user");
        $user->name = "Eustáquio Rangel";
        $this->assertTrue($user->isValid());
    }

    /**
     * Test if a collection is transformed in an array
     *
     * @return null
     */
    public function testCollectionToArray() 
    {
        $user       = User::first();
        $tickets    = $user->tickets->toArray();
        $this->assertEquals(10, sizeof($tickets));
    }

    /**
     * Test if a collection is transformed in an array, with limit
     *
     * @return null
     */
    public function testCollectionToArrayWithLimit() 
    {
        $user       = User::first();
        $tickets    = $user->tickets->toArray(1);
        $this->assertEquals(1, sizeof($tickets));
    }

    /**
     * Test full error messages
     *
     * @return null
     */
    public function testFullErrorMessages() 
    {
        Locale::setDefault("en-US");
        User::setYAMLFile("torm.yml");
        $user = User::first();
        $msgs = $user->fullMessages(
            array("name"       => array("presence"),
                  "user_level" => array("numericality"),
                  "email"      => array("uniqueness","format"))
        );

        $this->assertEquals("Name cannot be blank", $msgs[0]);
        $this->assertEquals("Level must be a number", $msgs[1]);
        $this->assertEquals("E-mail must be unique", $msgs[2]);
        $this->assertEquals("E-mail has invalid format", $msgs[3]);
    }

    /**
     * Test pagination
     *
     * @return null
     */
    public function testPagination() 
    {
        $user = User::first();
        $objs = array();

        for ($i=0; $i<21; $i++) {
            $ticket = TORM\Factory::build("ticket");
            $ticket->user_id = $user->id;
            $this->assertTrue($ticket->save());
            array_push($objs, $ticket);
        }

        $pages = array(
            1 => 5,
            2 => 5,
            3 => 5, 
            4 => 5,
            5 => 5, 
            6 => 5,
            7 => 1
        );
        $this->assertEquals(31, sizeof($user->tickets->toArray()));

        foreach ($pages as $page => $qty) {
            $tickets = $user->tickets->paginate($page, 5);
            $ar = $tickets->toArray();

            $this->assertEquals($page, $tickets->page);
            $this->assertEquals($qty, sizeof($ar));
        }

        foreach ($objs as $obj) {
            $this->assertTrue($obj->destroy());
        }
    }

    /**
     * Test old attribute
     *
     * @return null
     */
    public function testOldAttr() 
    {
        $user = TORM\Factory::build("user");
        $this->assertTrue($user->save());

        $old_name = $user->name;
        $new_name = "Dirty Objects";

        $user = User::find($user->id);
        $user->name = $new_name;
        $this->assertTrue($user->name_changed);
        $this->assertEquals($old_name, $user->name_was);
        $this->assertEquals(array($old_name, $new_name), $user->name_change);

        $this->assertEquals(0, sizeof(array_diff_assoc(array("name"), $user->changed())));

        $changes = $user->changes();
        $this->assertTrue(array_key_exists("name", $changes));
        $this->assertEquals(0, sizeof(array_diff_assoc(array($old_name,$new_name), $changes["name"])));
        $this->assertTrue($user->save());

        $newer_name = "Another dirty object test";
        $user->name = $newer_name;
        $this->assertTrue($user->name_changed);
        $this->assertEquals($new_name, $user->name_was);
        $this->assertEquals(array($new_name, $newer_name), $user->name_change);
        $this->assertTrue($user->destroy());
    }

    /**
     * Test exception
     *
     * Need to check the correct behaviour of PostgreSQL with:
     *
     * This: expectedExceptionMessage table users has no column named invalid_attr
     * And this: expectedExceptionCode HY000
     *
     * @expectedException PDOException
     *
     * @return null
     */
    public function testException()
    {
        $user = TORM\Factory::build("crazy_user");
        $this->assertFalse($user->save());
    }

    /**
     * Test column extraction
     *
     * @return null
     */
    public function testExtractColumns()
    {
        $escape   = TORM\Driver::$escape_char;
        $columns  = User::extractColumns();
        $expected = "{$escape}users{$escape}.{$escape}id{$escape},{$escape}users{$escape}.{$escape}name{$escape},{$escape}users{$escape}.{$escape}email{$escape},{$escape}users{$escape}.{$escape}user_level{$escape},{$escape}users{$escape}.{$escape}code{$escape},{$escape}users{$escape}.{$escape}created_at{$escape},{$escape}users{$escape}.{$escape}updated_at{$escape}";
        $this->assertEquals(strtolower($expected), strtolower($columns));
    }

    /**
     * This is a test to check if the before callbacks are called before 
     * writing the record, where is checked if is a valid record.
     * There's a before callback that remove invalid chars from the email 
     * attr, so the record will be saved only if the callback is called 
     * before saving.
     *
     * @return null
     */
    public function testBeforeInvalidChars() 
    {
        $user = User::first();
        $user->email = $user->email."#";
        $this->assertTrue($user->save());
    }

    /**
     * Object has a afterInitialize method
     *
     * @return null
     */
    public function testAfterInitialize() 
    {
        $user = TORM\Factory::build("unnamed_user");
        $this->assertTrue(method_exists($user, "afterInitialize"));
        $this->assertEquals("Unnamed User", $user->name);
    }

    /**
     * Other connection
     *
     * @return null
     */
    public function testOtherConnection()
    {
        if (self::_database() != "sqlite") {
            return;
        }

        $file = realpath(dirname(__FILE__)."/../database/another_test.sqlite3");
        $con  = new PDO("sqlite:$file");

        AnotherUser::setConnection($con, "test");
        $user = AnotherUser::first();
        $this->assertEquals("Walternate", $user->name);
        $this->assertEquals($con, AnotherUser::resolveConnection());

        $ouser = AnotherUser::find($user->id);
        $this->assertEquals($user, $ouser);
        $this->assertEquals($con, AnotherUser::resolveConnection());
    }

    /**
     * Test reload method
     *
     * @return null
     */
    public function testReload()
    {
        $ticket    = Ticket::first();
        $old_descr = $ticket->description;
        $ticket->description = "New description";

        $user1 = $ticket->user;
        $u1h   = spl_object_hash($user1);
        $this->assertNotNull($user1);

        $this->assertEquals("New description", $ticket->description);
        $ticket->reload();
        $this->assertEquals($old_descr, $ticket->description);

        $user2 = $ticket->user;
        $u2h   = spl_object_hash($user2);
        $this->assertNotNull($user2);

        $this->assertNotEquals($u1h, $u2h);
    }

    /**
     * Test where associative conditions
     *
     * @return nul
     */
    public function testExtractWhereConditionsAssociative()
    {
        $escape     = TORM\Driver::$escape_char;
        $expected   = "{$escape}users{$escape}.{$escape}id{$escape}=? and {$escape}users{$escape}.{$escape}name{$escape}=? and {$escape}users{$escape}.{$escape}user_level{$escape}=?";
        $conditions = User::extractWhereConditions(["id" => 1, "name" => "john", "user_level" => 3]);
        $this->assertEquals(strtolower($expected), strtolower($conditions));
    }

    /**
     * Test where regular conditions
     *
     * @return nul
     */
    public function testExtractWhereConditionsRegular()
    {
        $expected   = "users.id >= ?";
        $conditions = User::extractWhereConditions([$expected, 1]);
        $this->assertEquals($expected, $conditions);
    }

    /**
     * Test where associative values
     *
     * @return nul
     */
    public function testExtractWhereValuesAssociative()
    {
        $expected   = [1, "john", 2];
        $conditions = User::extractWhereValues(["id" => 1, "name" => "john", "user_level" => 2]);
        $this->assertEquals($expected, $conditions);
    }

    /**
     * Test where regular values
     *
     * @return nul
     */
    public function testExtractWhereValuesRegular()
    {
        $expected   = [1, "john", 2];
        $conditions = User::extractWhereValues(["id=? and name=? and user_level=?", 1, "john", 2]);
        $this->assertEquals($expected, $conditions);
    }

    /**
     * Test conditions with regular array
     *
     * @return null
     */
    public function testConditionsRegular()
    {
        $user = User::where(["name = ?", "Eustaquio Rangel"])->next();
        $this->assertEquals("Eustaquio Rangel", $user->name);

        $user = User::where(["user_level >= ?", 1])->next();
        $this->assertEquals("Eustaquio Rangel", $user->name);

        $user = User::where(["user_level >= ?", 2])->next();
        $this->assertEquals("Rangel, Eustaquio", $user->name);

        $user = User::where(["user_level >= ? and name = ?", 1, "Rangel, Eustaquio"])->next();
        $this->assertEquals("Rangel, Eustaquio", $user->name);
    }

    /**
     * Test on empty collection
     *
     * @return null
     */
    public function testEmptyConditions()
    {
        $count = 0;
        foreach (User::where(["code" => "xxx"]) as $user) {
            $count ++;
        }
        $this->assertEquals(0, $count);
    }

    /**
     * Test whether a second BelongsTO assignment does change the attribute
     * (Check if BelongsTo cache is properly updated)
     *
     * @return null
     */
    public function testBelongsDoubleAttribution()
    {
        $user1             = new User();
        $user1->name       = "Belongs first attribution from where and other class";
        $user1->email      = "belongswhere@torm.com";
        $user1->code       = "01011";
        $user1->user_level = 1;
        $this->assertTrue($user1->save());

        $user2             = new User();
        $user2->name       = "Belongs second attribution from where and other class";
        $user2->email      = "belongswhere2@torm.com";
        $user2->code       = "01012";
        $user2->user_level = 1;
        $this->assertTrue($user2->save());

        $uid1   = $user1->id;
        $uname1 = $user1->name;

        $uid2   = $user2->id;
        $uname2 = $user2->name;

        $users                = [$user1->code, $user2->code];
        $ticket               = new Ticket();
        $ticket->description  = "Test";
        $ticket->user         = $user1;

        $this->assertEquals($uname1, $ticket->user->name);
        $ticket->user         = $user2;
        $this->assertEquals($uname2, $ticket->user->name);

        $this->assertTrue($ticket->save());

        $this->assertNotEquals(self::$user->id, $uid1);
        $this->assertNotEquals(self::$user->id, $uid2);

        $this->assertNotNull($uid1);
        $this->assertNotNull($uid2);

        $this->assertEquals($ticket->user->id, $uid2);
        $this->assertTrue($user1->destroy());
        $this->assertTrue($user2->destroy());

        $this->assertTrue($ticket->destroy());
    }
}
?>
