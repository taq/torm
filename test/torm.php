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
         self::$user->validates("email",array("format"  =>"^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$"));
         self::$user->validates("email",array("uniqueness"=>true));
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

      public function testNotFound() {
         $user = User::find(10);
         $this->assertNull($user);
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

      public function testFirstWithCondition() {
         $user = User::first(array("email"=>"eustaquiorangel@gmail.com"));
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testFirstNotFound() {
         $user = User::first(array("email"=>"yoda@gmail.com"));
         $this->assertNull($user);
      }

      public function testLast() {
         $user = User::last();
         $this->assertEquals("Rangel, Eustaquio",$user->name);
      }

      public function testLastWithCondition() {
         $user = User::last(array("email"=>"taq@bluefish.com.br"));
         $this->assertEquals("Rangel, Eustaquio",$user->name);
      }

      public function testLastNotFound() {
         $user = User::last(array("email"=>"yoda@gmail.com"));
         $this->assertNull($user);
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

      public function testInvalidFormat() {
         self::$user->email = "yadda@yadda";
         $this->assertFalse(self::$user->isValid());
      }

      public function testValidFormat() {
         self::$user->email = "jr@doe.com";
         $this->assertTrue(self::$user->isValid());
      }

      public function testUniqueness() {
         $old_user = User::find(1);
         $new_user = new User();
         $new_user->name  = $old_user->name;
         $new_user->email = $old_user->email;
         $this->assertFalse($new_user->isValid());
         $this->assertEquals(TORM\Validation::VALIDATION_UNIQUENESS,$new_user->errors["email"][0]);
      }
   }
?>
