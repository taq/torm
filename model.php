<?php
namespace TORM;

class Model {
   public  static $connection  = null;
   private static $table_name  = array();
   private static $order       = array();
   private static $pk          = array();
   private static $columns     = array();
   private static $ignorecase  = array();
   private static $mapping     = array();
   private static $loaded      = array();

   private static $prepared_cache = array();
   private static $validations    = array();
   private static $has_many       = array();

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
      $cls = get_called_class();
      self::checkLoaded();

      if($data==null) {
         $this->new_rec = true;
         $this->data = self::loadNullValues();
         return;
      }

      foreach($data as $key=>$value) {
         if(preg_match("/^\d+$/",$key))
            continue;
         $keyr = $key;
         if(array_key_exists($cls,self::$ignorecase) &&
            self::$ignorecase[$cls]) {
            $keyr = strtolower($key);
            $data[$keyr] = $value;
            if($keyr!=$key)
               unset($data[$key]);
         }
         if(!array_key_exists($cls,self::$mapping))
            self::$mapping[$cls] = array();

         if(!array_key_exists($keyr,self::$mapping[$cls]))
            self::$mapping[$cls][$key] = $keyr;
      }
      $this->data = $data;
   }

   public static function isIgnoringCase() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$ignorecase))
         return false;
      return self::$ignorecase[$cls];
   }

   /**
    * Load null values on row columns.
    * Useful to new objects.
    */
   private static function loadNullValues() {
      $values = array();
      $cls    = get_called_class();

      if(!array_key_exists($cls,self::$columns))
         return null;

      foreach(self::$columns[$cls] as $column) {
         $name = self::isIgnoringCase() ? strtolower($column) : $column;
         $values[$column] = null;
      }
      return $values;
   }

   public static function setTableName($table_name) {
      $cls = get_called_class();
      self::$table_name[$cls] = $table_name;
   }

   /**
    * Returns the table name.
    * If not specified one, get the current class name and appends a "s" to it.
    * @return string table name
    */
   public static function getTableName() {
      $cls = get_called_class();
      return array_key_exists($cls,self::$table_name) ? self::$table_name[$cls] : get_called_class()."s";
   }

   public static function setPK($pk) {
      $cls = get_called_class();
      self::$pk[$cls] = $pk;
   }

   /**
    * Returns the primary key column.
    * @return string primary key
    */
   public static function getPK() {
      $cls = get_called_class();
      return array_key_exists($cls,self::$pk) ? self::$pk[$cls] : "id";
   }

   public static function setOrder($order) {
      $cls = get_called_class();
      self::$order[$cls] = $order;
   }

   /**
    * Returns the default order.
    * If not specified, returns an empty string.
    * @return string order
    */
   public static function getOrder() {
      $cls = get_called_class();
      return array_key_exists($cls,self::$order) ? self::$order[$cls] : "";
   }

   /**
    * Returns the inverse order.
    * If DESC is specified, retuns ASC.
    * @return string order
    */
   public static function getReversedOrder() {
      $sort = preg_match("/desc/i",self::getOrder());
      $sort = $sort ? " ASC " : " DESC ";
      return self::getOrder() ? self::getOrder()." $sort" : "";
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
      $cls = get_called_class();
      self::$columns[$cls] = array();

      $rst  = self::resolveConnection()->query("select \"".self::getTableName()."\".* from \"".self::getTableName()."\"");
      $keys = array_keys($rst->fetch(\PDO::FETCH_ASSOC));

      foreach($keys as $key) {
         $keyc = self::isIgnoringCase() ? strtolower($key) : $key;
         array_push(self::$columns[$cls],$keyc);
         self::$mapping[$cls][$keyc] = $key;
      }
      self::$loaded[$cls] = true;
   }

   private static function extractWhereConditions($conditions) {
      if(!$conditions)
         return "";

      $cls = get_called_class();
      if(is_array($conditions)) {
         $temp_cond = "";
         foreach($conditions as $key=>$value)
            $temp_cond .= "\"".self::getTableName()."\".\"".self::$mapping[$cls][$key]."\"=? and ";
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
      self::checkLoaded();

      $builder          = self::makeBuilder();
      $builder->where   = self::extractWhereConditions($conditions);
      $vals             = self::extractWhereValues($conditions);
      return new Collection($builder,$vals,get_called_class());
   }

   private static function makeBuilder() {
      $builder = new Builder();
      $builder->table = self::getTableName();
      $builder->order = self::getOrder();
      return $builder;
   }

   /**
    * Find an object by its primary key
    * @param object $id - primary key
    * @return object result
    */
   public static function find($id) {
      self::checkLoaded();

      $pk               = self::isIgnoringCase() ? strtolower(self::getPK()) : self::getPK();
      $builder          = self::makeBuilder();
      $builder->where   = self::extractWhereConditions(array($pk=>$id));
      $builder->limit   = 1;
      $cls  = get_called_class();
      $stmt = self::executePrepared($builder,array($id));
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
      self::checkLoaded();

      $builder = self::makeBuilder();
      return new Collection($builder,null,get_called_class());
   }

   /**
    * Get result by position - first or last
    * @param $position first or last
    * @param object conditions
    * @return result or null
    */
   private static function getByPosition($position,$conditions=null) {
      self::checkLoaded();

      $builder          = self::makeBuilder();
      $builder->order   = $position=="first" ? self::getOrder() : self::getReversedOrder();
      $builder->where   = self::extractWhereConditions($conditions);
      $vals             = self::extractWhereValues($conditions);
      
      $cls  = get_called_class();
      $stmt = self::executePrepared($builder,$vals);
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

   private function checkLoaded() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$loaded))
         self::$loaded[$cls] = false;
      if(!self::$loaded[$cls])
         self::loadColumns();
   } 

   /**
    * Save or update currenct object
    * @return boolean saved/updated
    */
   public function save() {
      if(!$this->isValid())
         return false;

      if(!self::$loaded) 
         self::loadColumns();

      $calling    = get_called_class();
      $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
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
            $sql .= "\"".self::$mapping[$calling][$attr]."\",";
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
            $sql .= "\"".self::$mapping[$calling][$attr]."\"=?,";
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
      if(!self::$loaded) 
         self::loadColumns();

      $calling    = get_called_class();
      $table_name = $calling::getTableName();
      $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $sql        = "delete from \"$table_name\" where \"$table_name\".\"".self::$mapping[$calling][$pk]."\"=?";
      Log::log($sql);
      return self::executePrepared($sql,array($pk_value))->rowCount()==1;
   }

   /**
    * Execute a prepared statement.
    * Try to get it from cache.
    * @return object statement
    */
   public static function executePrepared($obj,$values=array()) {
      if(!self::$loaded)
         self::loadColumns();

      if(!is_string($obj) && get_class($obj)=="TORM\Builder")
         $sql = $obj->toString();
      if(is_string($obj))
         $sql = $obj;

      $stmt = self::putCache($sql);
      $stmt->execute($values);
      return $stmt;
   }

   /**
    * Check if is valid
    */
   public function isValid() {
      $this->errors = array();
      $cls = get_called_class();
      $rtn = true;
      $pk  = self::get(self::getPK());

      foreach(self::$validations[$cls] as $attr=>$validations) {
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
      $cls = get_called_class();

      // bummer! need to verify the calling class
      if(!array_key_exists($cls,self::$validations))
         self::$validations[$cls] = array();

      if(!array_key_exists($attr,self::$validations[$cls]))
         self::$validations[$cls][$attr] = array();

      array_push(self::$validations[$cls][$attr],$validation);
   }

   /**
    * Create a has many relationship
    * @param $attr attribute
    */
   public static function hasMany($attr,$options=null) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$has_many))
         self::$has_many[$cls] = array();
      print "has many $attr on $cls ...\n";
      self::$has_many[$cls][$attr] = $options;
   }

   private static function resolveHasMany($attr) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$has_many) ||
         !array_key_exists($attr,self::$has_many[$cls]))
         return null;
      return self::$has_many[$cls][$attr];
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
      } else {
         Log::log("inserting on cache: $sql");
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
   public function __call($method,$args) {
      $cls = get_called_class();
      if(method_exists($this,$method)) 
         return call_user_func_array(array($this,$method),$args);

      if(!$args) {
         return $this->data[$method];
      } else
         $this->data[$method] = $args[0];
   }

   public static function __callStatic($method,$args) {
      $cls = get_called_class();
      print "checking $method on $cls ...\n";

      if(array_key_exists($cls   ,self::$has_many) &&
         array_key_exists($method,self::$has_many[$cls]))
         return self::resolveHasMany($method);
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
