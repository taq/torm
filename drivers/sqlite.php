<?php
namespace TORM;

Driver::$name                  = "sqlite";
Driver::$primary_key_behaviour = Driver::PRIMARY_KEY_DELETE;
Driver::$limit_behaviour       = Driver::LIMIT_APPEND;
?>
