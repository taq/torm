<?php
namespace TORM;

class Validation {
   const VALIDATION_PRESENCE     = "presence";
   const VALIDATION_FORMAT       = "format";
   const VALIDATION_UNIQUENESS   = "uniqueness";
   const VALIDATION_NUMERICALITY = "numericality";

   public static $validation_map = array("presence"      => self::VALIDATION_PRESENCE,
                                         "format"        => self::VALIDATION_FORMAT,
                                         "uniqueness"    => self::VALIDATION_UNIQUENESS,
                                         "numericality"  => self::VALIDATION_NUMERICALITY
                                        );

   public static function presence($cls,$id,$attr,$attr_value,$validation_value) {
      if(!$validation_value)
         return true;
      return strlen(trim($attr_value))>0;
   }

   public static function format($cls,$id,$attr,$attr_value,$validation_value) {
      return preg_match("/$validation_value/",$attr_value);
   }

   public static function uniqueness($cls,$id,$attr,$attr_value,$validation_value) {
      return call_user_func_array(array("\\".$cls,"isUnique"),array($id,$attr,$attr_value));
   }

   public static function numericality($cls,$id,$attr,$attr_value,$validation_value) {
      return preg_match("/^[0-9]+$/",trim($attr_value));
   }
}
?>
