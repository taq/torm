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
   private static $prepared_cache = array();

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
      $vals = array();
      if(is_array($conditions)) {
         $temp_cond = "";
         foreach($conditions as $key=>$value) {
            $temp_cond .= "\"".self::getTableName()."\".\"$key\"=? and ";
            array_push($vals,$value);
         }
         $temp_cond  = substr($temp_cond,0,strlen($temp_cond)-5);
         $conditions = $temp_cond;
      }
      $sql = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\" where $conditions ".self::getOrder();
      Log::log($sql);
      return new Collection(self::executePrepared($sql,$vals),get_called_class());
   }

   public static function find($id) {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\" where \"".self::getTableName()."\".\"".self::$pk."\"=? ".self::getOrder();
      Log::log($sql);
      $cls  = get_called_class();
      $stmt = self::executePrepared($sql,array($id));
      return new $cls($stmt->fetch(\PDO::FETCH_ASSOC));
   }

   public static function all() {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\"".self::getOrder();
      Log::log($sql);
      return new Collection(self::executePrepared($sql),get_called_class());
   }

   public static function first() {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\"".self::getOrder();
      $cls  = get_called_class();
      Log::log($sql);
      $stmt = self::executePrepared($sql);
      return new $cls($stmt->fetch(\PDO::FETCH_ASSOC));
   }

   public static function last() {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\"".self::getReversedOrder();
      Log::log($sql);
      $cls  = get_called_class();
      $stmt = self::executePrepared($sql);
      return new $cls($stmt->fetch(\PDO::FETCH_ASSOC));
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
      $pk_value   = $this->data[$pk];
      $sql        = null;
      $attrs      = $this->data;
      $rtn        = false;
      $vals       = array();

      if($this->new_rec) {
         $sql   = "insert into \"".$calling::getTableName()."\" (";
         // process primary key here
         unset($attrs[$calling::getPK()]);

         $marks = join(",",array_fill(0,sizeof($attrs),"?"));
         foreach($attrs as $attr=>$value) 
            $sql .= "\"".$calling::$mapping[$attr]."\",";
         $sql  = substr($sql,0,strlen($sql)-1);
         $sql .= ") values ($marks)";

         foreach($attrs as $attr=>$value) 
            array_push($vals,$value);
         $rtn = self::executePrepared($sql,$vals)->rowCount()==1;
      } else {
         unset($attrs[$pk]);
         $sql  = "update \"".$calling::getTableName()."\" set ";
         foreach($attrs as $attr=>$value) {
            if(strlen(trim($value))<1)
               $value = "null";
            $sql .= "\"".$calling::$mapping[$attr]."\"=?,";
            array_push($vals,$value);
         }
         $sql  = substr($sql,0,strlen($sql)-1);
         $sql .= " where \"".self::getTableName()."\".\"$pk\"=?";
         array_push($vals,$pk_value);
         $rtn = self::executePrepared($sql,$vals)->rowCount()==1;
      }
      Log::log($sql);
      return $rtn;
   }

   public function destroy() {
      $calling    = get_called_class();
      $table_name = $calling::getTableName();
      $pk         = $calling::$ignorecase ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $sql        = "delete from \"$table_name\" where \"$table_name\".\"$pk\"=?";
      Log::log($sql);
      return self::executePrepared($sql,array($pk_value))->rowCount()==1;
   }

   public static function executePrepared($sql,$values=array()) {
      $stmt = self::putCache($sql);
      $stmt->execute($values);
      return $stmt;
   }

   public static function putCache($sql) {
      $md5 = md5($sql);
      if(array_key_exists($md5,self::$prepared_cache)) {
         Log::log("already prepared: $sql");
         return self::$prepared_cache[$md5];
      }
      $prepared = self::resolveConnection()->prepare($sql);
      self::$prepared_cache[$md5] = $prepared;
      return $prepared;
   }

   public static function getCache($sql) {
      $md5 = md5($sql);
      if(!array_key_exists($md5,self::$prepared_cache)) 
         return null;
      return self::$prepared_cache[$md5];
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
