<?php
namespace TORM;

Driver::$primary_key_behaviour = Driver::PRIMARY_KEY_SEQUENCE;
Driver::$limit_behaviour       = Driver::LIMIT_AROUND;
Driver::$limit_query           = "select * from (%query%) where rownum<=%limit%";
Driver::$pagination_query      = "select * from (select a.*, rownum as rnum from (%query%) a where rownum <= %to%) where rnum >= %from%";
Driver::$pagination_subquery   = true;
Driver::$numeric_column        = "number";
Driver::$current_timestamp     = "sysdate";
Driver::$last_id_supported     = false;
?>
