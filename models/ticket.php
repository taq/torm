<?php
   class Ticket extends TORM\Model {};
   Ticket::validates("description" ,array("presence"=>true));
?>

