<?php
namespace TORM;

class Driver {
   const PRIMARY_KEY_DELETE   = 1;
   const PRIMARY_KEY_STRING   = 2;
   const PRIMARY_KEY_EXECUTE  = 3;
   const LIMIT_APPEND         = 4;
   const LIMIT_AROUND         = 5;

   public static $primary_key_behaviour = self::PRIMARY_KEY_DELETE;
   public static $limit_behaviour       = self::LIMIT_APPEND;
   public static $limit_query           = null;
}
