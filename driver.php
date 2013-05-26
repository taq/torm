<?php
namespace TORM;

class Driver {
   const PRIMARY_KEY_DELETE  = 1;
   const PRIMARY_KEY_STRING  = 2;
   const PRIMARY_KEY_EXECUTE = 3;

   public static $primary_key_behaviour = self::PRIMARY_KEY_DELETE;
}
