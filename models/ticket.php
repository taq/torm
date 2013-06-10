<?php
   class Ticket extends TORM\Model {
      public static function getNewPKValue() {
         return time();
      }
   }

   Ticket::validates("description" ,array("presence"=>true));
   Ticket::belongsTo("user");
   Ticket::setOrder("id");
?>

