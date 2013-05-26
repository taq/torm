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
      self::$driver = $driver;
   }

   public static function getDriver() {
      return self::$driver;
   }
}
?>
