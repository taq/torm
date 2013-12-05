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

      public function testNotANumber() {
         self::$user->level = "one";
         $this->assertFalse(self::$user->isValid());
      }

      public function testNotANumberWithSpecialChars() {
         self::$user->level = "$%@";
         $this->assertFalse(self::$user->isValid());
      }

      public function testAPositiveNumber() {
         self::$user->level = 1;
         $this->assertTrue(self::$user->isValid());
      }

      public function testANegativeNumber() {
         self::$user->level = -1;
         $this->assertTrue(self::$user->isValid());
      }

      public function testAFloatingPointNumber() {
         self::$user->level = 1.23;
         $this->assertTrue(self::$user->isValid());
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
         $ids = array();
         foreach($tickets as $ticket) {
            echo "ticket: ".$ticket->id." ".$ticket->description."\n";
            array_push($ids,$ticket->id);
         }
         $this->assertNotNull($user->ticket_ids);
         $this->assertEquals(sizeof($ids),sizeof($user->ticket_ids));
         foreach($ids as $id) {
            $this->assertTrue(in_array($id,$user->ticket_ids));
         }
      }

      public function testHasManyUpdateIds() {
         $user    = User::find(1);
         $ticket  = TORM\Factory::build("ticket");
         $ticket->user_id = $user->id;
         $this->assertTrue($ticket->save());

         $this->assertEquals(3,$user->tickets->count());
         $user->ticket_ids = [1,2];
         $this->assertEquals(2,$user->tickets->count());
         $this->assertNotNull(Ticket::find($ticket->id));
         $this->assertTrue($ticket->destroy());
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

      public function testCantUpdatePKAttributes() {
         $account = TORM\Factory::create("account");
         $this->assertTrue($account->save());

         $account = Account::first();
         $old_id  = $account->id;
         $new_id  = 999;
         $new_num = "54321";

         $this->assertTrue($account->updateAttributes(array("id"=>$new_id,"number"=>$new_num)));
         $account = Account::find($old_id);
         $this->assertNotNull($account);
         $this->assertEquals($new_num,$account->number);
         $this->assertNull(Account::find($new_id));
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

         $this->assertTrue($user1->save(true));
         $this->assertTrue($user2->save(true));
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
         $this->assertTrue($user1->save(true));
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
         $this->assertNotNull($user->hasColumn("created_at"));
      }

      public function testHasNotCreateColumn() {
         $account = TORM\Factory::build("account");
         $this->assertNull($account->hasColumn("created_at"));
      }

      public function testHasUpdateColumn() {
         $user = TORM\Factory::build("user");
         $this->assertNotNull($user->hasColumn("updated_at"));
      }

      public function testHasNotUpdateColumn() {
         $account = TORM\Factory::build("account");
         $this->assertNull($account->hasColumn("updated_at"));
      }

      public function testHasHasManyRelation() {
         $account = TORM\Factory::build("account");
         $user    = TORM\Factory::build("user");

         $this->assertFalse($account->hasHasMany("users"));
         $this->assertTrue($user->hasHasMany("tickets"));
      }

      public function testHasManyClass() {
         $user = TORM\Factory::build("user");
         $this->assertEquals("Ticket",$user->hasManyClass("tickets"));
      }

      public function testHasManyForeignKey() {
         $user = TORM\Factory::build("user");
         $this->assertEquals("user_id",$user->hasManyForeignKey("tickets"));
      }

      public function testModelTableName() {
         $this->assertEquals("users",User::getTableName());
      }

      public function testModelTableNameByString() {
         $this->assertEquals("tickets",TORM\Model::getTableName("Ticket"));
      }

      public function testPushReceiverWithIdObjectWithId() {
         $user    = TORM\Factory::create("user");
         $ticket  = TORM\Factory::create("ticket");

         $this->assertEquals(0,$user->tickets->count());
         $user->push($ticket);
         $this->assertEquals($user->id,$ticket->user_id);
         $this->assertEquals(1,$user->tickets->count());

         $ticket = Ticket::find($ticket->id);
         $this->assertEquals($user->id,$ticket->user->id);
         $this->assertTrue($user->destroy());
         $this->assertTrue($ticket->destroy());
      }

      public function testPushReceiverWithoutIdObjectWithId() {
         $attrs       = TORM\Factory::attributes_for("user");
         $attrs["id"] = null;

         $user    = new User($attrs);
         $ticket  = TORM\Factory::create("ticket");

         $this->assertEquals(0,$user->tickets->count());
         $user->push($ticket);
         $this->assertTrue($user->save());
         $this->assertEquals(1,$user->tickets->count());

         $ticket = Ticket::find($ticket->id);
         $this->assertEquals($user->id,$ticket->user->id);
         $this->assertTrue($user->destroy());
         $this->assertTrue($ticket->destroy());
      }

      public function testPushReceiverWithIdObjectWithoutId() {
         $user    = TORM\Factory::create("user");
         $ticket  = TORM\Factory::build("ticket");

         $this->assertEquals(0,$user->tickets->count());
         $user->push($ticket);
         $this->assertEquals(1,$user->tickets->count());

         $ticket = Ticket::find($ticket->id);
         $this->assertEquals($user->id,$ticket->user->id);
         $this->assertTrue($user->destroy());
         $this->assertTrue($ticket->destroy());
      }

      public function testPushReceiverWithoutIdObjectWithoutId() {
         $user    = TORM\Factory::build("user");
         $ticket  = TORM\Factory::build("ticket");

         $user->push($ticket);
         $this->assertTrue($user->save());

         $ticket = Ticket::find($ticket->id);
         $this->assertEquals($user->id,$ticket->user->id);
         $this->assertTrue($user->destroy());
         $this->assertTrue($ticket->destroy());
      }

      public function testManyCollection() {
         $user = TORM\Factory::create("user");
         $t1   = TORM\Factory::create("ticket");
         $t2   = TORM\Factory::create("ticket");

         $this->assertEquals(0,$user->tickets->count());
         $user->tickets = array($t1,$t2);
         $this->assertEquals(2,$user->tickets->count());

         $this->assertTrue($user->destroy());
         $this->assertTrue($t1->destroy());
         $this->assertTrue($t2->destroy());
      }

      private function checkCallbackFile($file) {
         $user = TORM\Factory::build("user");
         if(file_exists($file))
            unlink($file);

         $this->assertFalse(file_exists($file));
         $this->assertTrue($user->save());
         $this->assertTrue($user->destroy());
         $this->assertTrue(file_exists($file));
      }

      public function testBeforeSave() {
         $this->checkCallbackFile("/tmp/torm-before-save.log");
      }

      public function testAfterSave() {
         $this->checkCallbackFile("/tmp/torm-after-save.log");
      }

      public function testBeforeDestroy() {
         $this->checkCallbackFile("/tmp/torm-before-destroy.log");
      }

      public function testAfterDestroy() {
         $this->checkCallbackFile("/tmp/torm-before-save.log");
      }

      public function testMustRespondToAScopeDefinitionMethod() {
         $this->assertTrue(method_exists("User","scope"));
      }

      public function testMustRespondToAScopeAsAMethod() {
         $this->assertEquals(1,User::first_level()->count());
      }

      public function testMustRespondToAScopeAsAMethodWithParameters() {
         $this->assertEquals(1,User::by_level(1)->count());
      }

      public function testMustRespondToAScopeAsAMethodWithMultipleParameters() {
         $this->assertEquals(1,User::by_level_and_date(1,date("Y-m-d"))->count());
      }

      public function testAccentedCharsOnValidation() {
         $user = TORM\Factory::build("user");
         $user->name = "Eustáquio Rangel";
         $this->assertTrue($user->isValid());
      }

      public function testCollectionToArray() {
         $user       = User::first();
         $tickets    = $user->tickets->toArray();
         $this->assertEquals(2,sizeof($tickets));
      }

      public function testFullErrorMessages() {
         Locale::setDefault("en-US");
         User::setYAMLFile("torm.yml");
         $user = User::first();
         $msgs = $user->fullMessages(array("name"  => array("presence"),
                                           "level" => array("numericality"),
                                           "email" => array("uniqueness","format")));

         $this->assertEquals("Name cannot be blank"      ,$msgs[0]);
         $this->assertEquals("Level must be a number"    ,$msgs[1]);
         $this->assertEquals("E-mail must be unique"     ,$msgs[2]);
         $this->assertEquals("E-mail has invalid format" ,$msgs[3]);
      }

      public function testPagination() {
         $user = User::first();
         $objs = array();
         for($i=0; $i<21; $i++) {
            $ticket = TORM\Factory::build("ticket");
            $ticket->user_id = $user->id;
            $this->assertTrue($ticket->save());
            array_push($objs,$ticket);
         }

         $pages = array(1=>5,2=>5,3=>5,4=>5,5=>3);
         foreach($pages as $page=>$qty) {
            $tickets = $user->tickets->paginate($page,5);
            $ar = $tickets->toArray();
            $this->assertEquals($page,$tickets->page);
            $this->assertEquals($qty,sizeof($ar));
         }

         foreach($objs as $obj) 
            $this->assertTrue($obj->destroy());
      }

      public function testOldAttr() {
         $user = TORM\Factory::build("user");
         $this->assertTrue($user->save());

         $old_name = $user->name;
         $new_name = "Dirty Objects";

         $user = User::find($user->id);
         $user->name = $new_name;
         $this->assertTrue($user->name_changed);
         $this->assertEquals($old_name,$user->name_was);
         $this->assertEquals(array($old_name,$new_name),$user->name_change);

         $this->assertEquals(0,sizeof(array_diff_assoc(array("name"),$user->changed())));

         $changes = $user->changes();
         $this->assertTrue(array_key_exists("name",$changes));
         $this->assertEquals(0,sizeof(array_diff_assoc(array($old_name,$new_name),$changes["name"])));
         $this->assertTrue($user->save());

         $newer_name = "Another dirty object test";
         $user->name = $newer_name;
         $this->assertTrue($user->name_changed);
         $this->assertEquals($new_name,$user->name_was);
         $this->assertEquals(array($new_name,$newer_name),$user->name_change);
         $this->assertTrue($user->destroy());
      }
   }
?>
