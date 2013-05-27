<?php
namespace TORM;

class Validation {
   const VALIDATION_PRESENCE = "presence";

   public static $validation_map = array("presence"=>self::VALIDATION_PRESENCE);

   public static function presence($cls,$attr,$value) {
      if(!$value)
         return true;
      return strlen(trim($attr))>0;
   }
}
?>
