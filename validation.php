<?php
namespace TORM;

class Validation {
   const VALIDATION_PRESENCE = "presence";
   const VALIDATION_FORMAT   = "format";

   public static $validation_map = array("presence"=>self::VALIDATION_PRESENCE,
                                         "format"  =>self::VALIDATION_FORMAT);

   public static function presence($cls,$attr,$value) {
      if(!$value)
         return true;
      return strlen(trim($attr))>0;
   }

   public static function format($cls,$attr,$value) {
      return preg_match("/$value/",$attr);
   }
}
?>
