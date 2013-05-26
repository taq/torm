<?php
namespace TORM;

class Connection {
   private static $driver     = "sqlite";
   private static $connection = array("development"=> null,
                                      "test"       => null,
                                      "production" => null);

   public static function setConnection($con,$env="development") {
      self::$connection[$env] = $con;
   }

   public static function getConnection($env="development") {
      return self::$connection[$env];
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
