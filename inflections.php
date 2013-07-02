<?php
namespace TORM;

class Inflections {
   const SINGULAR    = 0;
   const PLURAL      = 1;
   const IRREGULAR   = 2;

   private static $inflections = array();

   public static function push($idx,$singular,$plural) {
      self::initialize();
      self::$inflections[$idx][$singular] = $plural;
   }

   private static function initialize() {
      for($i=self::SINGULAR; $i<=self::IRREGULAR; $i++) {
         if(!array_key_exists($i,self::$inflections))
            self::$inflections[$i] = array();
      }
   }

   public static function pluralize($str) {
      return self::search($str,self::PLURAL);
   }

   public static function singularize($str) {
      return self::search($str,self::SINGULAR);
   }

   private static function search($str,$idx) {
      self::initialize();

      $idx  = $idx==self::PLURAL ? self::SINGULAR : self::PLURAL;
      $vals = self::$inflections[$idx];

      // adding irregular
      foreach(self::$inflections[self::IRREGULAR] as $key=>$val) {
         $vals[$key] = $val;
         $vals[$val] = $key;
      }

      foreach($vals as $key=>$val) {
         $reg = preg_match('/^\/[\s\S]+\/[imsxeADSUXJu]?$/',$key);
         $exp = $reg ? $key : "/$key/i";
         $mat = preg_match($exp,$str);
         if(!$reg && $mat) 
            return $val;
         if($reg && $mat)
            return preg_replace($key,$val,$str);
      }

      // default behaviour - the "s" thing
      return $idx==self::SINGULAR ? trim($str)."s" : preg_replace('/s$/',"",$str);
   }
}
?>
