<?php
namespace TORM;

class Factory {
   private static $path       = null;
   private static $factories  = array();
   private static $loaded     = false;

   public static function setFactoriesPath($path) {
      self::$path = $path;
   }

   public static function getFactoriesPath() {
      self::resolveDefaultPath();
      return self::$path;
   }

   private static function resolveDefaultPath() {
      if(!self::$path)
         self::$path = realpath(dirname(__FILE__)."/factories");
   }

   public static function factoriesCount() {
      self::load();
      return count(self::$factories);
   }

   public static function define($name,$attrs,$options=null) {
      self::$factories[$name] = $attrs;
   }

   public static function get($name) {
      self::load();
      if(!array_key_exists($name,self::$factories))
         return null;
      return self::$factories[$name];
   }

   public static function load($force=false) {
      if(!$force && self::$loaded)
         return false;
      self::resolveDefaultPath();

      $files = glob(realpath(self::$path)."/*.php");
      foreach($files as $file) {
         Log::log("loading factory from $file ...");
         include($file);
      }
      self::$loaded = true;
      return self::$loaded;
   }

   public static function attributes_for($name) {
      self::load();
      $data = self::get($name);
      if(!$data)
         return null;
      return $data;
   }

   public static function create($name) {
      return self::build($name,true);
   }

   public static function build($name,$create=false) {
      self::load();
      $data = self::attributes_for($name);
      if(!$data)
         return null;

      $pos  = array_search($name,array_keys(self::$factories));
      $name = ucfirst(strtolower($name));
      $obj  = new $name();  
      $pk   = $obj::getPK();
   
      if(!array_key_exists($pk,$data))
         $data[$pk] = time()+$pos;

      $obj = new $name($data);  
      if($create) {
         if(!$obj->isValid()) 
            return null;
         $obj->save();
      }
      return $obj;
   }
}
