<?php
   class Account extends TORM\Model {};
   Account::validates("account_number",array("presence"=>true));
?>
