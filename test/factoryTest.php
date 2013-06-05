<?php
   include_once "../torm.php";
   include_once "../models/user.php";

   class FactoryTest extends PHPUnit_Framework_TestCase {
      public static function setUpBeforeClass() {
         TORM\Factory::setFactoriesPath("../factories");
      }

      public function testGetFactories() {
         $this->assertEquals(1,TORM\Factory::factoriesCount());
      }

      public function testGetFactory() {
         $this->assertNotNull(TORM\Factory::get("user"));
      }

      public function testBuildFactory() {
         $user = TORM\Factory::build("user");
         $this->assertEquals("User",get_class($user));
         $this->assertEquals("Mary Doe",$user->name);
         $this->assertEquals("mary@doe.com",$user->email);
      }
   }
?>
