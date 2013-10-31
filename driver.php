<?php
namespace TORM;

class Driver {
   const PRIMARY_KEY_DELETE   = 1;
   const PRIMARY_KEY_STRING   = 2;
   const PRIMARY_KEY_EXECUTE  = 3;
   const PRIMARY_KEY_EVAL     = 4;
   const PRIMARY_KEY_SEQUENCE = 5;

   const LIMIT_APPEND         = 11;
   const LIMIT_AROUND         = 12;

   public static $primary_key_behaviour = self::PRIMARY_KEY_DELETE;
   public static $limit_behaviour       = self::LIMIT_APPEND;
   public static $limit_query           = null;
   public static $numeric_column        = "integer";
   public static $escape_char           = "\"";
   public static $current_timestamp     = "current_timestamp";
   public static $pagination_query      = "%query% limit %to% offset %from%";
   public static $pagination_subquery   = false;
   public static $last_id_supported     = true;
}
