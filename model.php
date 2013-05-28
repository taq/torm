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
   private static $validations    = array();

   private $data        = array();
   private $new_rec     = false;
   public  $errors      = null;

   /**
    * Constructor
    * If data is sent, then it loads columns with it.
    * @param array $data
    * @package TORM
    */
   public function __construct($data=null) {
      if(!self::$loaded) 
         self::loadColumns();

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
         if(!array_key_exists($keyr,self::$mapping))
            self::$mapping[$key] = $keyr;
      }
      $this->data = $data;
   }

   /**
    * Load null values on row columns.
    * Useful to new objects.
    */
   private static function loadNullValues() {
      $values = array();
      foreach(self::$columns as $column) {
         $name = self::$ignorecase ? strtolower($column) : $column;
         $values[$column] = null;
      }
      return $values;
   }

   /**
    * Returns the table name.
    * If not specified one, get the current class name and appends a "s" to it.
    * @return string table name
    */
   public static function getTableName() {
      return self::$table_name ? self::$table_name : get_called_class()."s";
   }

   /**
    * Returns the primary key column.
    * @return string primary key
    */
   public static function getPK() {
      return self::$pk;
   }

   /**
    * Returns the default order.
    * If not specified, returns an empty string.
    * @return string order
    */
   public static function getOrder() {
      return self::$order ? " order by ".self::$order : "";
   }

   /**
    * Returns the inverse order.
    * If DESC is specified, retuns ASC.
    * @return string order
    */
   public static function getReversedOrder() {
      $sort = preg_match("/desc/i",self::$order);
      $sort = $sort ? " ASC " : " DESC ";
      return self::$order ? " order by ".self::$order." $sort" : "";
   }

   /**
    * Resolve the current connection handle.
    * Get it from PDO.
    * @return object connection
    */
   private static function resolveConnection() {
      return self::$connection ? self::$connection : Connection::getConnection();
   }

   /**
    * Load column info
    */
   private static function loadColumns() {
      self::$columns = array();
      $rst  = self::resolveConnection()->query("select \"".self::getTableName()."\".* from \"".self::getTableName()."\"");
      $keys = array_keys($rst->fetch(\PDO::FETCH_ASSOC));

      foreach($keys as $key) {
         $keyc = self::$ignorecase ? strtolower($key) : $key;
         array_push(self::$columns,$keyc);
         self::$mapping[$keyc] = $key;
      }
      self::$loaded = true;
   }

   private static function extractWhereConditions($conditions) {
      if(!$conditions)
         return "";

      if(is_array($conditions)) {
         $temp_cond = "";
         foreach($conditions as $key=>$value)
            $temp_cond .= "\"".self::getTableName()."\".\"".self::$mapping[$key]."\"=? and ";
         $temp_cond  = substr($temp_cond,0,strlen($temp_cond)-5);
         $conditions = $temp_cond;
      }
      return $conditions;
   }

   private static function extractWhereValues($conditions) {
      $values = array();
      if(!$conditions)
         return $values;

      if(is_array($conditions)) {
         foreach($conditions as $key=>$value)
            array_push($values,$value);
      }
      return $values;
   }

   /**
    * Use the WHERE clause to return values
    * @conditions string or array - better use is using an array
    * @return Collection of results
    */
   public static function where($conditions) {
      $vals       = self::extractWhereValues($conditions);
      $conditions = self::extractWhereConditions($conditions);
      $sql = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\" where $conditions ".self::getOrder();
      Log::log($sql);
      return new Collection(self::executePrepared($sql,$vals),get_called_class());
   }

   /**
    * Find an object by its primary key
    * @param object $id - primary key
    * @return object result
    */
   public static function find($id) {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\" where \"".self::getTableName()."\".\"".self::$pk."\"=? ".self::getOrder();
      Log::log($sql);
      $cls  = get_called_class();
      $stmt = self::executePrepared($sql,array($id));
      $data = $stmt->fetch(\PDO::FETCH_ASSOC);
      if(!$data)
         return null;
      return new $cls($data);
   }

   /**
    * Return all values
    * @return Collection values
    */
   public static function all() {
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\"".self::getOrder();
      Log::log($sql);
      return new Collection(self::executePrepared($sql),get_called_class());
   }

   /**
    * Get result by position - first or last
    * @param $position first or last
    * @param object conditions
    * @return result or null
    */
   private static function getByPosition($position,$conditions=null) {
      $order      = $position=="first" ? self::getOrder() : self::getReversedOrder();
      $where      = "";
      $vals       = self::extractWhereValues($conditions);
      $conditions = self::extractWhereConditions($conditions);
      if(strlen($conditions)>0) 
         $where = " where $conditions ";
      
      $sql  = "select \"".self::getTableName()."\".* from \"".self::getTableName()."\" $where $order";
      $cls  = get_called_class();
      Log::log($sql);
      $stmt = self::executePrepared($sql,$vals);
      $data = $stmt->fetch(\PDO::FETCH_ASSOC);
      if(!$data)
         return null;
      return new $cls($data);
   }

   /**
    * Return the first value.
    * Get by order.
    * @param conditions
    * @return object result
    */
   public static function first($conditions=null) {
      return self::getByPosition("first",$conditions);
   }

   /**
    * Return the last value.
    * Get by inverse order.
    * @return object result
    */
   public static function last($conditions=null) {
      return self::getByPosition("last",$conditions);
   }

   /**
    * Tell if its a new object (not saved)
    * @return boolean new or not 
    */
   public function is_new() {
      return $this->new_rec;
   }

   /**
    * Return the object current values
    * @return Array data
    */
   public function getData() {
      return $this->data;
   }

   /**
    * Save or update currenct object
    * @return boolean saved/updated
    */
   public function save() {
      if(!$this->isValid())
         return false;

      $calling    = get_called_class();
      $pk         = $calling::$ignorecase ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $sql        = null;
      $attrs      = $this->data;
      $rtn        = false;
      $vals       = array();

      if($this->new_rec) {
         $sql = "insert into \"".$calling::getTableName()."\" (";

         if(Driver::$primary_key_behaviour==Driver::PRIMARY_KEY_DELETE)
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

   /**
    * Destroy the current object
    * @return boolean destroyed or not
    */
   public function destroy() {
      $calling    = get_called_class();
      $table_name = $calling::getTableName();
      $pk         = $calling::$ignorecase ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $sql        = "delete from \"$table_name\" where \"$table_name\".\"".self::$mapping[$pk]."\"=?";
      Log::log($sql);
      return self::executePrepared($sql,array($pk_value))->rowCount()==1;
   }

   /**
    * Execute a prepared statement.
    * Try to get it from cache.
    * @return object statement
    */
   public static function executePrepared($sql,$values=array()) {
      if(!self::$loaded)
         self::loadColumns();

      $stmt = self::putCache($sql);
      $stmt->execute($values);
      return $stmt;
   }

   /**
    * Check if is valid
    */
   public function isValid() {
      $this->errors = array();
      $rtn = true;
      $pk  = self::get(self::getPK());

      foreach(self::$validations as $attr=>$validations) {
         $value = $this->data[$attr];

         foreach($validations as $validation) {
            $validation_key   = array_keys($validation);
            $validation_key   = $validation_key[0];
            $validation_value = array_values($validation);
            $validation_value = $validation_value[0];
            $args = array(get_called_class(),$pk,$attr,$value,$validation_value);
            $test = call_user_func_array(array("TORM\Validation",$validation_key),$args);
            if(!$test) {
               $rtn = false;
               if(!array_key_exists($attr,$this->errors))
                  $this->errors[$attr] = array();
               array_push($this->errors[$attr],Validation::$validation_map[$validation_key]);
            }
         }
      }
      return $rtn;
   }

   /**
    * Check if attribute is unique
    * @param object attribute
    * @return if attribute is unique
    */
   public static function isUnique($id,$attr,$attr_value) {
      $obj = self::first(array($attr=>$attr_value));
      return $obj==null || $obj->get(self::getPK())==$id;
   }

   public function get($attr) {
      return $this->data[$attr];
   }

   public static function validates($attr,$validation) {
      if(!array_key_exists($attr,self::$validations))
         self::$validations[$attr] = array();
      array_push(self::$validations[$attr],$validation);
   }

   /**
    * Put a prepared statement on cache, if not there.
    * @return object prepared statement
    */
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

   /**
    * Get a prepared statement from cache
    * @return object or null if not on cache
    */
   public static function getCache($sql) {
      $md5 = md5($sql);
      if(!array_key_exists($md5,self::$prepared_cache)) 
         return null;
      return self::$prepared_cache[$md5];
   }

   /**
    * Trigger to use object values as methods
    * Like
    * $user->name("john doe");
    * print $user->name();
    */
   function __call($method,$args) {
      if(method_exists($this,$method)) 
         return call_user_func_array(array($this,$method),$args);
      if(!$args)
         return $this->data[$method];
      else
         $this->data[$method] = $args[0];
   }

   /**
    * Trigger to get object values as attributes
    * Like
    * print $user->name;
    */
   function __get($attr) {
      return $this->data[$attr];
   }

   /**
    * Trigger to set object values as attributes
    * Like
    * $user->name = "john doe";
    */
   function __set($attr,$value) {
      $this->data[$attr] = $value;
   }
}
?>
