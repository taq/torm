<?php
   include_once "../torm.php";
   include_once "../models/user.php";

   class FactoryTest extends PHPUnit_Framework_TestCase {
      protected static $con  = null;

      public static function setUpBeforeClass() {
         $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
         self::$con  = new PDO("sqlite:$file");
         
         TORM\Connection::setConnection(self::$con,"test");
         TORM\Connection::setDriver("sqlite");
         TORM\Factory::setFactoriesPath("./factories");
         TORM\Log::enable(false);
      }

      public function testGetFactories() {
         $this->assertEquals(4,TORM\Factory::factoriesCount());
      }

      public function testGetFactory() {
         $this->assertNotNull(TORM\Factory::get("user"));
      }

      public function testFactoryWithDifferentClass() {
         $admin = TORM\Factory::build("admin");
         $this->assertNotNull($admin);
         $this->assertEquals("User",get_class($admin));
      }

      public function testBuildFactory() {
         $user = TORM\Factory::build("user");
         $this->assertEquals("User",get_class($user));
         $this->assertEquals("Mary Doe",$user->name);
         $this->assertEquals("mary@doe.com",$user->email);
      }

      public function testAttributes() {
         $data = TORM\Factory::attributes_for("user");
         $this->assertNotNull($data);
         $this->assertTrue(is_array($data));
         $this->assertEquals("Mary Doe",$data["name"]);
         $this->assertEquals("mary@doe.com",$data["email"]);
      }

      public function testCreateFactory() {
         $user = TORM\Factory::create("user");
         $this->assertEquals("User",get_class($user));
         $this->assertNotNull($user->id);
         $this->assertEquals("Mary Doe",$user->name);
         $this->assertEquals("mary@doe.com",$user->email);
         $this->assertNotNull(User::find($user->id));
         $this->assertTrue($user->destroy());
      }
   }
?>
