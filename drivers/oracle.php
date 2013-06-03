<?php
namespace TORM;

Driver::$primary_key_behaviour = Driver::PRIMARY_KEY_DELETE;
Driver::$limit_behaviour       = Driver::LIMIT_AROUND;
Driver::$limit_query           = "select * from (%query%) where rownum<=%limit%";
?>
