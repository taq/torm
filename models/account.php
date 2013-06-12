<?php
   class Account extends TORM\Model {};
   Account::validates("number",array("presence"=>true));
?>
