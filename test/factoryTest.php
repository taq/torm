<?php
   include_once "../torm.php";

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
   }
?>
