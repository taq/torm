<?php
namespace TORM;

Driver::$name                  = "postgresql";
Driver::$primary_key_behaviour = Driver::PRIMARY_KEY_SEQUENCE;
Driver::$limit_behaviour       = Driver::LIMIT_APPEND;
Driver::$current_timestamp     = "now()";
Driver::$numeric_column        = "numeric";
?>
