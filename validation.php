<?php
namespace TORM;

class Validation {
   const VALIDATION_PRESENCE     = "presence";
   const VALIDATION_FORMAT       = "format";
   const VALIDATION_UNIQUENESS   = "uniqueness";

   public static $validation_map = array("presence"   => self::VALIDATION_PRESENCE,
                                         "format"     => self::VALIDATION_FORMAT,
                                         "uniqueness" => self::VALIDATION_UNIQUENESS
                                        );

   public static function presence($cls,$attr,$attr_value,$validation_value) {
      if(!$validation_value)
         return true;
      return strlen(trim($attr_value))>0;
   }

   public static function format($cls,$attr,$attr_value,$validation_value) {
      return preg_match("/$validation_value/",$attr_value);
   }

   public static function uniqueness($cls,$attr,$attr_value,$validation_value) {
      return call_user_func_array(array("\\".$cls,"isUnique"),array($attr,$attr_value));
   }
}
?>
