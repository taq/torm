<?php
   include_once "../torm.php";
   include_once "../models/user.php";
   include_once "../models/ticket.php";
   include_once "../models/account.php";

   class ModelTest extends PHPUnit_Framework_TestCase {
      protected static $con  = null;
      protected static $user = null;

      public static function setUpBeforeClass() {
         $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
         self::$con  = new PDO("sqlite:$file");
         // self::$con  = new PDO('mysql:host=localhost;dbname=torm',"torm","torm");

         TORM\Connection::setConnection(self::$con,"test");
         TORM\Connection::setDriver("sqlite");
         // TORM\Connection::setDriver("mysql");
         TORM\Factory::setFactoriesPath("./factories");
         TORM\Log::enable(false);

         self::$user        = new User();
         self::$user->id    = 1;
         self::$user->name  = "John Doe Jr.";
         self::$user->email = "jr@doe.com";
         self::$user->code  = "12345";
         self::$user->level = 1;
      }

      public function setUp() {
         Account::all()->destroy();
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

         $user  = $users->next();
         $this->assertEquals("Rangel, Eustaquio",$user->name);

         echo "checking all users ...\n";
         foreach(User::all() as $user) {
            echo "user: ".$user->name."\n";
         }
      }

      public function testInsert() {
         $user = new User();
         $user->name    = "John Doe";
         $user->email   = "john@doe.com";
         $user->level   = 1;
         $user->code    = "12345";
         $this->assertTrue($user->isValid());
         $this->assertNull($user->id);
         $this->assertTrue($user->save());
         $this->assertNotNull($user->id);
         $this->assertNotNull($user->created_at);

         $new_user = User::find($user->id);
         $this->assertNotNull($new_user);
         $this->assertEquals($user->name ,$new_user->name);
         $this->assertEquals($user->email,$new_user->email);
         $this->assertEquals($user->level,$new_user->level);
         $this->assertEquals($user->code ,$new_user->code);
      }

      public function testInsertNoCreatedAtColumn() {
         $ticket = new Ticket();
         $ticket->description = "A new ticket";
         $ticket->user_id = 1;
         $this->assertTrue($ticket->save());
         $this->assertTrue($ticket->destroy());
      }

      public function testUpdate() {
         $user = User::where("email='john@doe.com'")->next();
         $id   = $user->id;
         $user->name = "Doe, John";
         $user->save();

         $this->assertEquals("Doe, John",User::find($id)->name);
         $this->assertTrue($user->isValid());
         $user->save();
      }

      public function testUpdateNoUpdatedAtColumn() {
         $ticket = Ticket::first();
         $old_desc = $ticket->description;
         $ticket->description = "New description";
         $this->assertTrue($ticket->save());
         $ticket->description = $old_desc;
         $this->assertTrue($ticket->save());
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

      public function testEmptyFormat() {
         self::$user->code = "";
         $this->assertFalse(self::$user->isValid());
      }

      public function testNullFormat() {
         self::$user->code = null;
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

      public function testNumericality() {
         self::$user->level = "one";
         $this->assertFalse(self::$user->isValid());
      }

      public function testCantSaveInvalidObject() {
         $user = new User();
         $this->assertFalse($user->save());
      }

      public function testLimit() {
         $users = User::where(array("name"=>"Eustaquio Rangel"))->limit(1);
         $this->assertNotNull($users->next());
         $this->assertNull($users->next());
      }

      public function testOrder() {
         $users = User::where("name like '%rangel%'")->order("email desc")->limit(1);
         $user  = $users->next();
         $this->assertNotNull($user);
         $this->assertEquals("taq@bluefish.com.br",$user->email);
      }

      public function testHasMany() {
         $user    = User::find(1);
         $tickets = $user->tickets;
         $this->assertNotNull($tickets);
         echo "\ntickets:\n";
         foreach($tickets as $ticket)
            echo "ticket: ".$ticket->id." ".$ticket->description."\n";
      }

      public function testBelongs() {
         $ticket = Ticket::first();
         $this->assertNotNull($ticket);
         $user = $ticket->user;
         $this->assertNotNull($user);
         echo "user on belongs: ".$user->name."\n";
      }

      public function testCheckEmptySequence() {
         $this->assertEquals(null,User::resolveSequenceName());
      }

      public function testDefaultSequence() {
         $old = TORM\Driver::$primary_key_behaviour;
         TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
         $name = User::resolveSequenceName();
         $this->assertEquals("users_sequence",User::resolveSequenceName());
         TORM\Driver::$primary_key_behaviour = $old;
      }

      public function testNamedSequence() {
         $old = TORM\Driver::$primary_key_behaviour;
         TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
         $test = "yadda_sequence";
         User::setSequenceName($test);
         $name = User::resolveSequenceName();
         $this->assertEquals($test,User::resolveSequenceName());
         TORM\Driver::$primary_key_behaviour = $old;
      }

      public function testCantChangeExistingPK() {
         $user = User::find(1);
         $old  = $user->id;
         $user->id = 10;
         $this->assertEquals($old,$user->id);
      }

      public function testCanChangeNewPK() {
         $user = new User();
         $new  = 10;
         $user->id = $new;
         $this->assertEquals($new,$user->id);
      }

      public function testCount() {
         $this->assertEquals(2,User::all()->count());
      }

      public function testCountWithConditions() {
         $this->assertEquals(1,User::all(array("email"=>"eustaquiorangel@gmail.com"))->count());
      }

      public function testCountWithConditionsAndWhere() {
         $this->assertEquals(1,User::where(array("email"=>"eustaquiorangel@gmail.com"))->count());
      }

      public function testSum() {
         $this->assertEquals(3,User::all()->sum("level"));
      }

      public function testSumWithConditions() {
         $this->assertEquals(2,User::all(array("email"=>"taq@bluefish.com.br"))->sum("level"));
      }

      public function testSumWithConditionsAndWhere() {
         $this->assertEquals(1,User::where(array("email"=>"eustaquiorangel@gmail.com"))->sum("level"));
      }

      public function testAvg() {
         $this->assertEquals(1.5,User::all()->avg("level"));
      }

      public function testAvgWithConditions() {
         $this->assertEquals(2,User::all(array("email"=>"taq@bluefish.com.br"))->avg("level"));
      }

      public function testAvgWithConditionsAndWhere() {
         $this->assertEquals(1,User::where(array("email"=>"eustaquiorangel@gmail.com"))->avg("level"));
      }

      public function testMin() {
         $this->assertEquals(1,User::all()->min("level"));
      }

      public function testMinWithConditions() {
         $this->assertEquals(2,User::all(array("email"=>"taq@bluefish.com.br"))->min("level"));
      }

      public function testMinWithConditionsAndWhere() {
         $this->assertEquals(1,User::where(array("email"=>"eustaquiorangel@gmail.com"))->min("level"));
      }

      public function testMax() {
         $this->assertEquals(2,User::all()->max("level"));
      }

      public function testMaxWithConditions() {
         $this->assertEquals(2,User::all(array("email"=>"taq@bluefish.com.br"))->max("level"));
      }

      public function testMaxWithConditionsAndWhere() {
         $this->assertEquals(1,User::where(array("email"=>"eustaquiorangel@gmail.com"))->max("level"));
      }

      public function testDestroyCollection() {
         $users = User::all();
         $user1 = $users->next();
         $user2 = $users->next();
         User::all()->destroy();

         $this->assertEquals(0,User::all()->count());
         $this->assertTrue($user1->save());
         $this->assertTrue($user2->save());
         $this->assertEquals(2,User::all()->count());
      }

      public function testDestroyCollectionWithConditions() {
         $cond  = array("email"=>"eustaquiorangel@gmail.com");
         $users = User::all($cond);
         $user1 = $users->next();
         User::all($cond)->destroy();

         $this->assertEquals(1,User::all()->count());
         $this->assertTrue($user1->save());
         $this->assertEquals(2,User::all()->count());
      }

      public function testUpdateAttributes() {
         $new_level = 3;
         $new_email = "iwishigottaq@gmail.com";

         $user = User::find(1);
         $old_level = $user->level;
         $old_email = $user->email;
         $user->updateAttributes(array("email"=>$new_email,"level"=>$new_level));

         $user = User::find(1);
         $this->assertEquals($new_level,$user->level);
         $this->assertEquals($new_email,$user->email);
         $user->updateAttributes(array("email"=>$old_email,"level"=>$old_level));

         $user = User::find(1);
         $this->assertEquals($old_level,$user->level);
         $this->assertEquals($old_email,$user->email);
      }

      public function testUpdateAttributesOnCollection() {
         $users = User::all();
         $user1 = $users->next();
         $user2 = $users->next();

         User::all()->updateAttributes(array("email"=>"void@gmail.com","level"=>0));
         $users = User::all();
         while($user = $users->next()) {
            $this->assertEquals("void@gmail.com",$user->email);
            $this->assertEquals(0,$user->level);
         }

         $this->assertTrue($user1->save());
         $this->assertTrue($user2->save());
      }

      public function testUpdateAttributesOnCollectionWithConditions() {
         $cond  = array("email"=>"eustaquiorangel@gmail.com");
         $users = User::where($cond);
         $user1 = $users->next();
         $this->assertNotNull($user1);

         User::where($cond)->updateAttributes(array("email"=>"void@gmail.com","level"=>0));
         $users = User::where($cond);
         while($user = $users->next()) {
            $this->assertEquals("void@gmail.com",$user->email);
            $this->assertEquals(0,$user->level);
         }
         $this->assertTrue($user1->save());
      }

      public function testPKMethod() {
         $user   = User::first();
         $ticket = new Ticket();
         $ticket->user_id     = $user->id;
         $ticket->description = "pk value test";
         $this->assertTrue($ticket->save());

         $ticket = Ticket::last();
         $this->assertTrue($ticket->id>=time()-1000);
         $this->assertTrue($ticket->destroy());
      }

      public function testHasOneRelation() {
         $account = TORM\Factory::create("account");

         $user = User::first();
         $acct = $user->account;
         $this->assertNotNull($acct);
         $this->assertEquals("Account",get_class($acct));
         $this->assertEquals($account->number,$acct->number);
      }

      public function testGet() {
         $user = TORM\Factory::build("user");
         $this->assertNotNull($user->get("name"));
      }

      public function testNullGet() {
         $user = TORM\Factory::build("user");
         $this->assertNull($user->get("yadda"));
      }

      public function testHasCreateColumn() {
         $user = TORM\Factory::build("user");
         $this->assertNotNull($user->hasCreateColumn());
      }

      public function testHasNotCreateColumn() {
         $account = TORM\Factory::build("account");
         $this->assertNull($account->hasCreateColumn());
      }

      public function testHasUpdateColumn() {
         $user = TORM\Factory::build("user");
         $this->assertNotNull($user->hasUpdateColumn());
      }

      public function testHasNotUpdateColumn() {
         $account = TORM\Factory::build("account");
         $this->assertNull($account->hasUpdateColumn());
      }
   }
?>
