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

   public static function presence($cls,$id,$attr,$attr_value,$validation_value,$options) {
      if(!$validation_value)
         return true;
      return strlen(trim($attr_value))>0;
   }

   public static function format($cls,$id,$attr,$attr_value,$validation_value,$options) {
      if(!is_null($options) && array_key_exists("allow_blank",$options) && strlen(trim($attr_value))<1)
         return true;
      if(!is_null($options) && array_key_exists("allow_null",$options) && is_null($attr_value)) 
         return true;
      return preg_match("/$validation_value/u",$attr_value);
   }

   public static function uniqueness($cls,$id,$attr,$attr_value,$validation_value,$options) {
      if(!is_null($options) && array_key_exists("allow_null",$options) && is_null($attr_value))
         return true;
      if(!is_null($options) && array_key_exists("allow_blank",$options) && strlen(trim($attr_value))<1)
         return true;
      return call_user_func_array(array("\\".$cls,"isUnique"),array($id,$attr,$attr_value));
   }

   public static function numericality($cls,$id,$attr,$attr_value,$validation_value,$options) {
      return preg_match("/^[-\.0-9]+$/",trim($attr_value));
   }
}
?>
