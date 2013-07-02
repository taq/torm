<?php
   include_once "../torm.php";
   include_once "../models/user.php";
   include_once "../models/ticket.php";
   include_once "../models/account.php";

   class InflectionsTest extends PHPUnit_Framework_TestCase {
      public function setUp() {
         TORM\Inflections::push(TORM\Inflections::IRREGULAR,"person","people");
         TORM\Inflections::push(TORM\Inflections::SINGULAR ,'/ao$/i',"oes");
         TORM\Inflections::push(TORM\Inflections::PLURAL   ,'/oes$/i',"ao");
      }

      public function testPluralize() {
         $this->assertEquals("people",TORM\Inflections::pluralize("person"));
      }

      public function testSingularize() {
         $this->assertEquals("person",TORM\Inflections::singularize("people"));
      }

      public function testDefaultPluralize() {
         $this->assertEquals("books",TORM\Inflections::pluralize("book"));
      }

      public function testDefaultSingularize() {
         $this->assertEquals("book",TORM\Inflections::singularize("books"));
      }

      public function testPluralizeWithRegex() {
         $this->assertEquals("acoes",TORM\Inflections::pluralize("acao"));
      }

      public function testSingularizeWithRegex() {
         $this->assertEquals("acao",TORM\Inflections::singularize("acoes"));
      }
   }
?>
