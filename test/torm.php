<?php
   include "../torm.php";
   include "../models/user.php";

   class TormTest extends PHPUnit_Framework_TestCase {
      protected static $con  = null;
      protected static $user = null;

      public static function setUpBeforeClass() {
         $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
         self::$con  = new PDO("sqlite:$file");
         
         TORM\Connection::setConnection(self::$con,"test");
         TORM\Connection::setDriver("sqlite");
         TORM\Log::enable(true);

         self::$user = new User();
         self::$user->validates("name" ,array("presence"=>true));
         self::$user->validates("email",array("presence"=>true));
         self::$user->name  = "John Doe Jr.";
         self::$user->email = "jr@doe.com";
      }

      public function testConnection() {
         $this->assertNotNull(self::$con);
         $this->assertEquals(self::$con,TORM\Connection::getConnection("test"));
      }      

      public function testFind() {
         $user = User::find(1);
         $this->assertEquals("Eustaquio Rangel",$user->name);
         $this->assertEquals("eustaquiorangel@gmail.com",$user->email);
      }

      public function testSetAttribute() {
         $user = User::find(1);
         $user->name = "John Doe";
         $this->assertEquals("John Doe",$user->name);
      }

      public function testFirst() {
         $user = User::first();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testLast() {
         $user = User::last();
         $this->assertEquals("Rangel, Eustaquio",$user->name);
      }

      public function testWhere() {
         $users = User::where(array("name"=>"Eustaquio Rangel"));
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testWhereWithString() {
         $users = User::where("name='Eustaquio Rangel'");
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testAll() {
         $users = User::all();
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testInsert() {
         $user = new User();
         $user->name    = "John Doe";
         $user->email   = "john@doe.com";
         $this->assertTrue($user->save());
      }

      public function testUpdate() {
         $user = User::where("email='john@doe.com'")->next();
         $id   = $user->id;
         $user->name = "Doe, John";
         $user->save();
         $this->assertEquals("Doe, John",User::find($id)->name);
         $user->save();
      }

      public function testDestroy() {
         $user = User::where("email='john@doe.com'")->next();
         $this->assertTrue($user->destroy());
      }

      public function testCache() {
         $sql = "select * from Users where id=?";
         $this->assertNull(User::getCache($sql));
         User::putCache($sql);
         $this->assertNotNull(User::getCache($sql));
      }

      public function testInvalidPresence() {
         self::$user->name = null;
         $this->assertFalse(self::$user->isValid());
      }

      public function testValidPresence() {
         self::$user->name = "John Doe Jr.";
         $this->assertTrue(self::$user->isValid());
      }
   }
?>
