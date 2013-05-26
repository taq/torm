<?php
namespace TORM;

class Connection {
   private static $driver     = "sqlite";
   private static $connection = array("development"=> null,
                                      "test"       => null,
                                      "production" => null);

   public static function setConnection($con,$env=null) {
      self::$connection[self::selectEnviroment($env)] = $con;
   }

   public static function getConnection($env=null) {
      return self::$connection[self::selectEnviroment($env)];
   }

   private static function selectEnviroment($env) {
      if(strlen($env)<1) {
         $getenv = self::getEnvironment();
         if(strlen($getenv)>0)
            $env = $getenv;
         else 
            $env = "development";
      }
      return $env;
   }

   private static function getEnvironment() {
      return getenv("TORM_ENV");
   }

   public static function setDriver($driver) {
      $file = realpath(dirname(__FILE__)."/drivers/$driver.php");
      if(!file_exists($file)) {
         Log::log("ERROR: Driver file $file does not exists");
         return false;
      }
      self::$driver = $driver;
      include $file;
   }

   public static function getDriver() {
      return self::$driver;
   }
}
?>
