<?php
namespace TORM;

class Model {
   public  static $connection  = null;
   public  static $table_name  = null;
   public  static $order       = null;
   public  static $pk          = "id";
   public  static $strings     = array();
   public  static $columns     = array();
   public  static $ignorecase  = true;
   public  static $mapping     = array();
   public  static $loaded      = false;

   private $data     = array();
   private $new_rec  = false;

   public function __construct($data=null) {
      if(!self::$loaded)
         self::loadDataTypes();

      if($data==null) {
         $this->new_rec = true;
         $this->data = self::loadNullValues();
         return;
      }

      foreach($data as $key=>$value) {
         if(preg_match("/^\d+$/",$key))
            continue;
         $keyr = $key;
         if(self::$ignorecase) {
            $keyr = strtolower($key);
            $data[$keyr] = $value;
            if($keyr!=$key)
               unset($data[$key]);
         }
         self::$mapping[$keyr] = $key;
      }
      $this->data = $data;
   }

   private static function loadNullValues() {
      $values = array();
      foreach(self::$columns as $column) {
         $name = self::$ignorecase ? strtolower($column) : $column;
         $values[$column] = null;
         self::$mapping[$name] = $column;
      }
      return $values;
   }

   public static function getTableName() {
      return self::$table_name ? self::$table_name : get_called_class()."s";
   }

   public static function getPK() {
      return self::$pk;
   }

   public static function getOrder() {
      return self::$order ? " order by ".self::$order : "";
   }

   public static function getReversedOrder() {
      $sort = preg_match("/desc/i",self::$order);
      $sort = $sort ? " ASC " : " DESC ";
      return self::$order ? " order by ".self::$order." $sort" : "";
   }

   private static function resolveConnection() {
      return self::$connection ? self::$connection : Connection::getConnection();
   }

   private static function loadDataTypes() {
      self::$strings = array();
      self::$columns = array();

      $rst = self::resolveConnection()->query("select * from ".self::getTableName());

      for($cnt = 0; $cnt<$rst->columnCount(); $cnt++) {
         $meta = $rst->getColumnMeta($cnt);
         $name = self::$ignorecase ? strtolower($meta["name"]) : $meta["name"];
         
         if(in_array(strtolower($meta["native_type"]),array("string")))
            array_push(self::$strings,$name);

         array_push(self::$columns,$name);
         self::$loaded = true;
      }
   }

   public static function execute($sql) {
      if(!self::$loaded)
         self::loadDataTypes();
      return self::resolveConnection()->query($sql);
   }

   public static function fetch($sql) {
      $data = self::execute($sql);
      if(!$data)
         return null;
      return $data->fetch(\PDO::FETCH_ASSOC);
   }

   public static function where($conditions) {
      if(is_array($conditions)) {
         $temp_cond = "";
         foreach($conditions as $key=>$value) {
            $temp_cond .= "$key=".self::delimiterColumn($key,$value)." and ";
         }
         $temp_cond  = substr($temp_cond,0,strlen($temp_cond)-5);
         $conditions = $temp_cond;
      }
      $sql = "select * from ".self::getTableName()." where $conditions ".self::getOrder();
      return new Collection(self::execute($sql),get_called_class());
   }

   public static function find($id) {
      $id   = self::delimiterColumn(self::$pk,$id);
      $sql  = "select * from ".self::getTableName()." where ".self::$pk."=$id ".self::getOrder();
      $cls  = get_called_class();
      return new $cls(self::fetch($sql));
   }

   public static function all() {
      $sql  = "select * from ".self::getTableName().self::getOrder();
      return new Collection(self::execute($sql),get_called_class());
   }

   public static function first() {
      $sql  = "select * from ".self::getTableName().self::getOrder();
      $cls  = get_called_class();
      return new $cls(self::fetch($sql));
   }

   public static function last() {
      $sql  = "select * from ".self::getTableName().self::getReversedOrder();
      $cls  = get_called_class();
      return new $cls(self::fetch($sql));
   }

   public static function delimiterColumn($col,$val) {
      if(in_array($col,self::$strings))
         $val = "'$val'";
      if($val==null)
         $val = "null";
      return $val;
   }

   public function is_new() {
      return $this->new_rec;
   }

   public function getData() {
      return $this->data;
   }

   public function save() {
      $calling    = get_called_class();
      $pk         = $calling::$ignorecase ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $calling::delimiterColumn($calling::$mapping[$pk],$this->data[$pk]);
      $sql        = null;
      $attrs      = $this->data;

      if($this->new_rec) {
         $sql  = "insert into ".$calling::getTableName()." (";
         foreach($attrs as $attr=>$value) 
            $sql .= $calling::$mapping[$attr].",";
         $sql  = substr($sql,0,strlen($sql)-1);
         $sql .= ") values (";
         foreach($attrs as $attr=>$value) 
            $sql .= $calling::delimiterColumn($attr,$value).",";
         $sql  = substr($sql,0,strlen($sql)-1);
         $sql .= ")";
      } else {
         unset($attrs[$pk]);
         $sql  = "update ".$calling::getTableName()." set ";
         foreach($attrs as $attr=>$value) {
            if(strlen(trim($value))<1)
               $value = "null";
            $sql .= $calling::$mapping[$attr]."=".$calling::delimiterColumn($attr,$value).",";
         }
         $sql = substr($sql,0,strlen($sql)-1);
         $sql .= " where $pk=$pk_value";
      }
      return self::resolveConnection()->exec($sql)==1;
   }

   public function destroy() {
      $calling    = get_called_class();
      $table_name = $calling::getTableName();
      $pk         = $calling::$ignorecase ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $calling::delimiterColumn($pk,$this->data[$pk]);
      $sql        = "delete from $table_name where $pk=$pk_value";
      return self::resolveConnection()->exec($sql)==1;
   }

   function __call($method,$args) {
      if(method_exists($this,$method)) 
         return call_user_func_array(array($this,$method),$args);
      if(!$args)
         return $this->data[$method];
      else
         $this->data[$method] = $args[0];
   }

   function __get($attr) {
      return $this->data[$attr];
   }

   function __set($attr,$value) {
      $this->data[$attr] = $value;
   }
}
?>
