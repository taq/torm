<?php
namespace TORM;

class Connection {
   private static $driver = array("development"=> "sqlite",
                                  "test"       => "sqlite",
                                  "production" => "sqlite");

   private static $connection = array("development"=> null,
                                      "test"       => null,
                                      "production" => null);

   public static function setConnection($con,$env=null) {
      $env = self::selectEnvironment($env);
      self::$connection[$env] = $con;

      // just send an exception when not on production mode
      if(in_array($env,array("development","test"))) 
         self::setErrorHandling(\PDO::ERRMODE_EXCEPTION);
   }

   public static function getConnection($env=null) {
      return self::$connection[self::selectEnvironment($env)];
   }

   public static function selectEnvironment($env=null) {
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

   public static function setDriver($driver,$env=null) {
      $file = realpath(dirname(__FILE__)."/drivers/$driver.php");
      if(!file_exists($file)) {
         Log::log("ERROR: Driver file $file does not exists");
         return false;
      }
      self::$driver[self::selectEnvironment($env)] = $driver;
      include $file;
   }

   public static function getDriver($env=null) {
      return self::$driver[self::selectEnvironment($env)];
   }

   public static function setErrorHandling($strategy) {
      self::getConnection()->setAttribute(\PDO::ATTR_ERRMODE,$strategy);
   }
}
?>
