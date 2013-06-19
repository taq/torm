<?php
namespace TORM;

Driver::$primary_key_behaviour = Driver::PRIMARY_KEY_DELETE;
Driver::$limit_behaviour       = Driver::LIMIT_APPEND;
Driver::$escape_char           = "`";
Driver::$current_timestamp     = "now()";
?>
