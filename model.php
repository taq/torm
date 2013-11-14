<?php
namespace TORM;

class Model {
   public  static $connection  = null;
   public  static $yaml_file   = null;
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
   private static $has_many_maps  = array();
   private static $belongs_to     = array();
   private static $sequence       = array();
   private static $has_one        = array();
   private static $callbacks      = array();
   private static $scopes         = array();

   private $data           = array();
   private $prev_data      = array();
   private $has_many_ids   = array();
   private $new_rec        = false;
   private $push_later     = array();
   public  $errors         = null;

   /**
    * Constructor
    * If data is sent, then it loads columns with it.
    * @param array $data
    * @package TORM
    */
   public function __construct($data=null) {
      $cls = get_called_class();
      self::checkLoaded();

      // setting default null values
      $this->data      = self::loadNullValues();
      $this->prev_data = self::loadNullValues();

      // if data not send, is a new record, return
      if($data==null) {
         $this->new_rec = true;
         return;
      }

      foreach($data as $key=>$value) {
         if(preg_match("/^\d+$/",$key))
            continue;
         $keyr = $key;
         if(self::isIgnoringCase()) {
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

      $this->data       = $data;
      $this->prev_data  = $data;

      // check if is a new record
      $pk = $cls::getPK();
      if(!array_key_exists($pk,$this->data) ||
         empty($this->data[$pk]))
         $this->new_rec = true;
   }

   public static function isIgnoringCase() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$ignorecase))
         return true;
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
    * If not specified one, get the current class name and pluralize it.
    * @return string table name
    */
   public static function getTableName($cls=null) {
      $cls  = $cls ? $cls : get_called_class();
      if(array_key_exists($cls,self::$table_name))
         return self::$table_name[$cls];
      $name = Inflections::pluralize($cls);
      if(self::isIgnoringCase())
         $name = strtolower($name);
      return $name;
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
      if(!self::resolveConnection())
         return;

      $cls = get_called_class();
      self::$columns[$cls] = array();

      $escape = Driver::$escape_char;

      // try to create the TORM info table
      $type = Driver::$numeric_column;

      // check if the table exists
      $rst  = self::resolveConnection()->query("select id from torm_info");
      if(!$rst->fetch()) {
         $stmt = self::resolveConnection()->query("create table torm_info (id $type(1))");
         $stmt->closeCursor();
      }
      $rst->closeCursor();

      // create table and insert first value
      $rst  = self::resolveConnection()->query("select id from torm_info");
      if(!$rst->fetch()) {
         $stmt = self::resolveConnection()->query("insert into torm_info values (1)");
         $stmt->closeCursor();
      }
      $rst->closeCursor();

      // hack to dont need a query string to get columns
      $sql  = "select $escape".self::getTableName()."$escape.* from torm_info left outer join $escape".self::getTableName()."$escape on 1=1";
      $rst  = self::resolveConnection()->query($sql);
      $keys = array_keys($rst->fetch(\PDO::FETCH_ASSOC));

      foreach($keys as $key) {
         $keyc = self::isIgnoringCase() ? strtolower($key) : $key;
         array_push(self::$columns[$cls],$keyc);
         self::$mapping[$cls][$keyc] = $key;
      }
      $rst->closeCursor();
      self::$loaded[$cls] = true;
   }

   public static function extractUpdateColumns($values) {
      $cls = get_called_class();
      $temp_columns = "";
      $escape = Driver::$escape_char;
      foreach($values as $key=>$value)
         $temp_columns .= "$escape".self::$mapping[$cls][$key]."$escape=?,";
      return substr($temp_columns,0,strlen($temp_columns)-1);
   }

   private static function extractWhereConditions($conditions) {
      if(!$conditions)
         return "";

      $cls = get_called_class();
      $escape = Driver::$escape_char;
      if(is_array($conditions)) {
         $temp_cond = "";
         foreach($conditions as $key=>$value)
            $temp_cond .= "$escape".self::getTableName()."$escape.$escape".self::$mapping[$cls][$key]."$escape=? and ";
         $temp_cond  = substr($temp_cond,0,strlen($temp_cond)-5);
         $conditions = $temp_cond;
      }
      return $conditions;
   }

   public static function extractWhereValues($conditions) {
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
   public static function all($conditions=null) {
      self::checkLoaded();

      $builder = self::makeBuilder();
      $vals    = null;
      if($conditions) {
         $builder->where = self::extractWhereConditions($conditions);
         $vals           = self::extractWhereValues($conditions);
      }
      return new Collection($builder,$vals,get_called_class());
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

   /**
    * Return the data previous state
    * @return Array data
    */
   public function getPrevData() {
      return $this->prev_data;
   }

   private function checkLoaded() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$loaded))
         self::$loaded[$cls] = false;
      if(!self::$loaded[$cls])
         self::loadColumns();
   } 

   private static function hasColumn($column) {
      $cls  = get_called_class();
      $key  = null;
      $keys = self::$columns[$cls];
      foreach($keys as $ckey) {
         $col1 = self::isIgnoringCase() ? strtolower($ckey)   : $ckey;
         $col2 = self::isIgnoringCase() ? strtolower($column) : $column;
         if($col1==$col2) {
            $key = $ckey;
            break;
         }
      }
      return $key;
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
      if(!self::checkCallback($calling,"before_save"))
         return false;

      $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $attrs      = $this->data;

      if(!$pk_value) {
         // if there is a method to get the new primary key value on the class, 
         // call it
         if(method_exists($calling,"getNewPKValue")) {
            $pk_value = $calling::getNewPKValue();
            if(!$this->data[$pk])
               $this->data[$pk] = $pk_value;
            $attrs = $this->data;
         }
      }

      if($pk_value)
         $this->new_rec = !self::find($pk_value);

      $rst = false;
      if($this->new_rec) 
         $rst = $this->insert($attrs,$calling,$pk,$pk_value);
      else
         $rst = $this->update($attrs,$calling,$pk,$pk_value);

      if($rst)
         self::checkCallback($calling,"after_save");
      return $rst;
   }

   private function insert($attrs,$calling,$pk,$pk_value) {
      $escape        = Driver::$escape_char;
      $vals          = array();
      $create_column = self::hasColumn("created_at");
      $update_column = self::hasColumn("updated_at");

      $sql = "insert into $escape".$calling::getTableName()."$escape (";

      // remove the current value when need to insert a NULL value to create 
      // the autoincrement value
      if(Driver::$primary_key_behaviour==Driver::PRIMARY_KEY_DELETE && !$pk_value)
         unset($attrs[$pk]);

      if(Driver::$primary_key_behaviour==Driver::PRIMARY_KEY_SEQUENCE && empty($pk_value)) {
         $seq_name   = self::resolveSequenceName();

         // check if the sequence exists
         self::checkSequence();
         if(!self::sequenceExists()) {
            $this->addError($pk,"Sequence $seq_name could not be created");
            return false;
         }

         // get the sequence next value
         $seq_sql    = "select $seq_name.nextval from dual";
         $seq_stmt   = self::query($seq_sql);
         $seq_data   = $seq_stmt->fetch(\PDO::FETCH_ASSOC);
         if($seq_data) {
            $seq_keys = array("nextval","NEXTVAL");
            foreach($seq_keys as $seq_key) {
               if(array_key_exists($seq_key,$seq_data)) {
                  $attrs[$pk] = $seq_data[$seq_key];
                  break;
               }
            }
         }
         $seq_stmt->closeCursor();
      } 

      // use sequence, but there is already a value on the primary key.
      // remember that it will allow this only if is really a record that
      // wasn't found when checking for the primary key, specifying that its 
      // a new record!
      if(Driver::$primary_key_behaviour==Driver::PRIMARY_KEY_SEQUENCE && !empty($pk_value))
         $attrs[$pk] = $pk_value;

      if($create_column && array_key_exists($create_column,$attrs))
         unset($attrs[$create_column]);

      if($update_column && array_key_exists($update_column,$attrs))
         unset($attrs[$update_column]);

      // marks to insert values on prepared statement
      $marks = array();
      foreach($attrs as $attr=>$value) {
         $sql .= "$escape".self::$mapping[$calling][$attr]."$escape,";
         array_push($marks,"?");
      }
      if($create_column) {
         $sql .= "$escape".self::$mapping[$calling][$create_column]."$escape,";
         array_push($marks,Driver::$current_timestamp);
      }
      if($update_column) {
         $sql .= "$escape".self::$mapping[$calling][$update_column]."$escape,";
         array_push($marks,Driver::$current_timestamp);
      }

      $marks = join(",",$marks); 
      $sql   = substr($sql,0,strlen($sql)-1);
      $sql  .= ") values ($marks)";

      // now fill the $vals array with all values to be inserted on the 
      // prepared statement
      foreach($attrs as $attr=>$value) 
         array_push($vals,$value);
      $rtn = self::executePrepared($sql,$vals)->rowCount()==1;

      // if inserted
      if($rtn) {
         // check for last inserted value
         $lid = null;
         if(Driver::$last_id_supported) {
            $lid = self::resolveConnection()->lastInsertId();
            if(empty($this->data[$pk]) && !empty($lid))
               $this->data[$pk] = $lid;
         }

         // or, like Oracle, if the database does not support last inserted id
         if(empty($this->data[$pk]) && empty($lid) && !empty($attrs[$pk]))
            $this->data[$pk] = $attrs[$pk];

         // check for database filled columns
         if($this->data[$pk]) {
            $found = self::find($this->data[$pk]);
            if($found) {
               if($create_column)
                  $this->data[$create_column] = $found->get($create_column);
            }
         }

         // push later objects
         foreach($this->push_later as $obj) {
            $this->push($obj);
         }
         $this->push_later = array();
      }
      Log::log($sql);
      return $rtn;
   }

   private function update($attrs,$calling,$pk,$pk_value) {
      $escape        = Driver::$escape_char;
      $vals          = array();
      $update_column = self::hasColumn("updated_at");
      $create_column = self::hasColumn("created_at");

      unset($attrs[$pk]);
      $sql  = "update $escape".$calling::getTableName()."$escape set ";
      foreach($attrs as $attr=>$value) {
         if(($update_column && $attr==$update_column) ||
            ($create_column && $attr==$create_column))
            continue;
         if(strlen(trim($value))<1)
            $value = null;
         $sql .= "$escape".self::$mapping[$calling][$attr]."$escape=?,";
         array_push($vals,$value);
      }
      if($update_column)
         $sql .= "$escape".self::$mapping[$calling][$update_column]."$escape=".Driver::$current_timestamp.",";

      $sql  = substr($sql,0,strlen($sql)-1);
      $sql .= " where $escape".self::getTableName()."$escape.$escape".self::$mapping[$calling][$pk]."$escape=?";
      array_push($vals,$pk_value);

      Log::log($sql);
      return self::executePrepared($sql,$vals)->rowCount()==1;
   }

   /**
    * Destroy the current object
    * @return boolean destroyed or not
    */
   public function destroy() {
      if(!self::$loaded) 
         self::loadColumns();

      $calling    = get_called_class();
      if(!self::checkCallback($calling,"before_destroy"))
         return false;

      $table_name = $calling::getTableName();
      $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
      $pk_value   = $this->data[$pk];
      $escape     = Driver::$escape_char;
      $sql        = "delete from $escape$table_name$escape where $escape$table_name$escape.$escape".self::$mapping[$calling][$pk]."$escape=?";
      Log::log($sql);

      $rst = self::executePrepared($sql,array($pk_value))->rowCount()==1;
      if($rst)
         self::checkCallback($calling,"after_destroy");
      return $rst;
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

   public static function query($sql) {
      return self::resolveConnection()->query($sql);
   }

   /**
    * Add an error to an attribute
    * @param $attr attribute
    * @param $msg  message
    */
   private function addError($attr,$msg) {
      if(!array_key_exists($attr,$this->errors))
         $this->errors[$attr] = array();
      array_push($this->errors[$attr],$msg);
   }

   public function errorMessages() {
      $msgs = array();
      foreach($this->errors as $key=>$values) {
         foreach($values as $value)
            array_push($msgs,"$key $value");
      }
      return $msgs;
   }

   /** 
    * Sets the YAML file location
    */
   public function setYAMLFile($file) {
      self::$yaml_file = $file;
   }

   /**
    * Return textual and translated error messages
    */
   public function fullMessages($errors=null) {
      if(!function_exists("yaml_parse") ||
         is_null(self::$yaml_file)      ||
         !file_exists(self::$yaml_file))
         return false;

      $rtn    = array();
      $parsed = yaml_parse(file_get_contents(self::$yaml_file));
      $errors = is_null($errors) ? $this->errors : $errors;
      $locale = function_exists("locale_get_default") ? locale_get_default() : "en-US";

      if(!array_key_exists($locale   ,$parsed)          ||
         !array_key_exists("errors"  ,$parsed[$locale]) ||
         !array_key_exists("messages",$parsed[$locale]["errors"]))
         return $this->errorMessages();

      $msgs = $parsed[$locale]["errors"]["messages"];
      $cls  = strtolower(get_called_class());

      foreach($errors as $key=>$values) {
         $attr = array_key_exists("attributes",$parsed[$locale]) &&
                 array_key_exists($cls,$parsed[$locale]["attributes"]) &&
                 array_key_exists($key,$parsed[$locale]["attributes"][$cls]) ?
                 $parsed[$locale]["attributes"][$cls][$key] : $key;
         foreach($values as $value) {
            $msg = array_key_exists($value,$msgs) ? $msgs[$value] : ":$value";
            array_push($rtn,"$attr $msg");
         }
      }
      return $rtn;
   }

   /**
    * Reset errors
    */
   private function resetErrors() {
      $this->errors = array();
   }

   /**
    * Check if is valid
    */
   public function isValid() {
      $this->resetErrors();
      $cls = get_called_class();
      $rtn = true;
      $pk  = self::get(self::getPK());

      if(!array_key_exists($cls,self::$validations) ||
         sizeof(self::$validations[$cls])<1)
         return true;

      foreach(self::$validations[$cls] as $attr=>$validations) {
         $value = $this->data[$attr];

         foreach($validations as $validation) {
            $validation_key   = array_keys($validation);
            $validation_key   = $validation_key[0];
            $validation_value = array_values($validation);
            $validation_value = $validation_value[0];
            $args = array(get_called_class(),$pk,$attr,$value,$validation_value,$validation);
            $test = call_user_func_array(array("TORM\Validation",$validation_key),$args);
            if(!$test) {
               $rtn = false;
               $this->addError($attr,Validation::$validation_map[$validation_key]);
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

   public function get($attr,$current=true) {
      if(!$this->data || !array_key_exists($attr,$this->data))
         return null;
      return $current ? $this->data[$attr] : $this->prev_data[$attr];
   }

   public function set($attr,$value) {
      $pk = self::getPK();
      // can't change the primary key of an existing record
      if(!$this->new_rec && $attr==$pk)
         return;
      $this->data[$attr] = $value;
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
    * Create a scope
    * @param $scope name
    * @param $conditions
    */
   public static function scope($name,$conditions) {
      $cls = get_called_class();
      
      if(!array_key_exists($cls,self::$scopes))
         self::$scopes[$cls] = array();

      if(!array_key_exists($name,self::$scopes[$cls]))
         self::$scopes[$cls][$name] = array();
         
      self::$scopes[$cls][$name] = $conditions;
   }

   public static function resolveScope($name,$args=null) {
      $cls = get_called_class();
      
      if(!array_key_exists($cls,self::$scopes) ||
         !array_key_exists($name,self::$scopes[$cls]))
         return null;

      $conditions = self::$scopes[$cls][$name];
      if(!$conditions)
         return null;

      if(is_callable($conditions)) 
         $conditions = $conditions($args);
      return self::where($conditions);
   }

   /**
    * Create a has many relationship
    * @param $attr attribute
    */
   public static function hasMany($attr,$options=null) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$has_many))
         self::$has_many[$cls] = array();
      self::$has_many[$cls][$attr] = $options ? $options : false;

      $klass = self::hasManyClass($attr);
      $ids   = strtolower($klass)."_ids";
      self::$has_many_maps[$cls][$ids] = $attr;
   }

   public function hasHasMany($attr) {
      $cls = get_called_class();
      return array_key_exists($cls,self::$has_many) &&
             array_key_exists($attr,self::$has_many[$cls]);
   }

   /**
    * Check a has many relationship and returns it resolved, if exists.
    * @param $method name
    * @param $value  
    * @return has many collection, if any
    */
   private static function checkAndReturnMany($method,$value) {
      $cls = get_called_class();
      if(array_key_exists($cls   ,self::$has_many) &&
         array_key_exists($method,self::$has_many[$cls]))
         return self::resolveHasMany($method,$value);
   }

   /**
    * Check class from a relation, like
    * hasManyClass("tickets") => "Ticket"
    */
   public static function hasManyClass($attr) {
      if(!self::hasHasMany($attr))
         return null;

      $cls     = get_called_class();
      $configs = self::$has_many[$cls][$attr];
      $klass   = is_array($configs) && array_key_exists("class_name",$configs)  ? $configs["class_name"] : ucfirst(preg_replace('/s$/',"",$attr));
      return $klass;
   }

   public static function hasManyForeignKey($attr) {
      if(!self::hasHasMany($attr))
         return null;

      $cls     = get_called_class();
      $configs = self::$has_many[$cls][$attr];
      $key     = is_array($configs) && array_key_exists("foreign_key",$configs) ? $configs["foreign_key"] : (self::isIgnoringCase() ? strtolower($cls)."_id" : $cls."_id");
      return $key;
   }

   /**
    * Resolve the has many relationship and returns the collection with values
    * @param $attr name
    * @param $value
    * @return collection
    */
   private static function resolveHasMany($attr,$value) {
      $cls = get_called_class();
      if(!self::hasHasMany($attr))
         return null;

      $configs       = self::$has_many[$cls][$attr];
      $has_many_cls  = self::hasManyClass($attr);
      $this_key      = self::hasManyForeignKey($attr);
      $collection    = $has_many_cls::where(array($this_key=>$value));
      return $collection;
   }

   /**
    * Create a belongs relationship
    * @param $attr attribute
    * @param $options options for relation
    */
   public static function belongsTo($model,$options=null) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$belongs_to))
         self::$belongs_to[$cls] = array();
      self::$belongs_to[$cls][$model] = $options ? $options : false;
   }

   private static function checkAndReturnBelongs($method,$values) {
      $cls = get_called_class();
      if(array_key_exists($cls   ,self::$belongs_to) &&
         array_key_exists($method,self::$belongs_to[$cls]))
         return self::resolveBelongsTo($method,$values);
   }

   private static function resolveBelongsTo($attr,$values) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$belongs_to) ||
         !array_key_exists($attr,self::$belongs_to[$cls]))
         return null;

      $configs       = self::$belongs_to[$cls][$attr];
      $belongs_cls   = is_array($configs) && array_key_exists("class_name" ,$configs) ? $configs["class_name"]  : ucfirst($attr);
      $belongs_key   = is_array($configs) && array_key_exists("foreign_key",$configs) ? $configs["foreign_key"] : strtolower($belongs_cls)."_id";
      $primary_key   = is_array($configs) && array_key_exists("primary_key",$configs) ? $configs["primary_key"] : "id";
      $value         = $values[$belongs_key];
      $obj           = $belongs_cls::first(array($primary_key=>$value));
      return $obj;
   }

   /**
    * Create a has one relationship
    * @param $attr attribute
    */
   public static function hasOne($attr,$options=null) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$has_one))
         self::$has_one[$cls] = array();
      self::$has_one[$cls][$attr] = $options ? $options : false;
   }

   /**
    * Resolve the has one relationship and returns the object
    * @param $attr name
    * @param $value
    * @return collection
    */
   private static function resolveHasOne($attr,$value) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$has_one) ||
         !array_key_exists($attr,self::$has_one[$cls]))
         return null;

      $configs       = self::$has_one[$cls][$attr];
      $has_one_cls   = is_array($configs) && array_key_exists("class_name",$configs)  ? $configs["class_name"]  : ucfirst(preg_replace('/s$/',"",$attr));
      $this_key      = is_array($configs) && array_key_exists("foreign_key",$configs) ? $configs["foreign_key"] : (self::isIgnoringCase() ? strtolower($cls)."_id" : $cls."_id");
      $obj           = $has_one_cls::first(array($this_key=>$value));
      return $obj;
   }

   /**
    * Check and return the value of a has one relationship
    * @param $method searched
    * @param $value the current id
    */
   private static function checkAndReturnHasOne($method,$value) {
      $cls = get_called_class();
      if(array_key_exists($cls   ,self::$has_one) &&
         array_key_exists($method,self::$has_one[$cls]))
         return self::resolveHasOne($method,$value);
   }

   public function updateAttributes($attrs) {
      if(array_key_exists(self::getPK(),$attrs))
         unset($attrs[self::getPK()]);
      foreach($attrs as $attr=>$value) 
         $this->data[$attr] = $value;
      return $this->save();
   }

   /**
    * Set the sequence name, if any
    * @param $name of the sequence
    */
   public static function setSequenceName($name) {
      $cls = get_called_class();
      self::$sequence[$cls] = $name;
   }

   /**
    * Returns the sequence name, if any
    * @return $name of the sequence
    */
   public static function getSequenceName() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$sequence))
         return null;
      return self::$sequence[$cls];
   }

   /**
    * Resolve the sequence name, if any
    * @returns $name of the sequence
    */
   public static function resolveSequenceName() {
      if(Driver::$primary_key_behaviour!=Driver::PRIMARY_KEY_SEQUENCE)
         return null;

      $suffix  = "_sequence";
      $table   = strtolower(self::getTableName());
      $name    = self::getSequenceName();
      if($name) 
         return $name;
      return $table.$suffix;
   }

   private static function sequenceExists() {
      if(Driver::$primary_key_behaviour!=Driver::PRIMARY_KEY_SEQUENCE)
         return null;

      $escape = Driver::$escape_char;
      $name   = self::resolveSequenceName();
      $sql    = "select count(*) as $escape"."CNT"."$escape from user_sequences where sequence_name='$name' or sequence_name='".strtolower($name)."' or sequence_name='".strtoupper($name)."'";
      $stmt   = self::resolveConnection()->query($sql);
      $rst    = $stmt->fetch(\PDO::FETCH_ASSOC);
      $rtn    = intval($rst["CNT"])>0;
      $stmt->closeCursor();
      return $rtn;
   }

   /**
    * Check if sequence exists
    * If not, create it.
    */
   private static function checkSequence() {
      if(Driver::$primary_key_behaviour!=Driver::PRIMARY_KEY_SEQUENCE)
         return null;

      if(self::sequenceExists())
         return;

      // create sequence
      $name = self::resolveSequenceName();
      $sql  = "create sequence $name increment by 1 start with 1 nocycle nocache";
      Log::log($sql);
      $stmt = self::resolveConnection()->query($sql);
      $stmt->closeCursor();
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
    * Resolve relations, if present
    */
   private function resolveRelations($method) {
      $many = self::checkAndReturnMany($method,$this->data[self::getPK()]);
      if($many)
         return $many;

      $belongs = self::checkAndReturnBelongs($method,$this->data);
      if($belongs)
         return $belongs;

      $has_one = self::checkAndReturnHasOne($method,$this->data[self::getPK()]);
      if($has_one)
         return $has_one;

      return null;
   }

   private function resolveIds($attr,$values=null) {
      $cls = get_called_class();

      if(!array_key_exists($cls ,self::$has_many_maps) ||
         !array_key_exists($attr,self::$has_many_maps[$cls]))
         return null;

      $klass   = self::hasManyClass(self::$has_many_maps[$cls][$attr]);
      $foreign = self::hasManyForeignKey(Inflections::pluralize(strtolower($klass)));
      $value   = $this->data[self::getPK()];
      $klasspk = $klass::getPK();

      // if values sent, set them
      if($values) {
         $this->has_many_ids = $values;
         $ids   = join(",",$values);
         $this->nullNotPresentIds($klass,$foreign,$ids,$value);
      } else {
         $data = $klass::where(array($foreign=>$value));
         $this->has_many_ids = array();
         while($row=$data->next())
            array_push($this->has_many_ids,$row->get($klasspk));
      }
      return $this->has_many_ids;
   }

   private function resolveCollection($attr,$values) {
      $cls = get_called_class();

      if(!array_key_exists($cls,self::$has_many_maps))
         return null;
      
      $maps = array_values(self::$has_many_maps[$cls]);
      if(!in_array($attr,$maps))
         return null;
      
      if(!$values || !is_array($values) || sizeof($values)<1 || !is_object($values[0]))
         return null;
      
      $this->has_many_ids = array();
      foreach($values as $value) {
         $klass = get_class($value);
         $this->push($value);
         $id = $value->get($klass::getPK());
         if($id)
            array_push($this->has_many_ids,$id);
      }
      return $this->has_many_ids;
   }

   private function nullNotPresentIds($klass,$foreign,$ids,$id) {
      $escape  = Driver::$escape_char;
      $klasspk = $klass::getPK();
      $klass   = strtolower($klass);
      $table   = Model::getTableName($klass);
      $sql     = "update $escape$table$escape set $escape$foreign$escape=null where $escape$foreign$escape=$id and $escape$table$escape.$escape$klasspk$escape not in ($ids)";
      $stmt    = self::query($sql);
      $stmt->closeCursor();
   }

   public function push($obj) {
      if(!$obj)
         return;

      $cls           = get_called_class();
      $escape        = Driver::$escape_char;
      $value         = array_key_exists(self::getPK(),$this->data) ? $this->data[self::getPK()] : null;
      $other_cls     = get_class($obj);
      $other_pk      = $other_cls::getPK();
      $other_value   = $obj->get($other_pk);
      $table         = Model::getTableName($other_cls);
      $foreign       = self::hasManyForeignKey(Inflections::pluralize(strtolower($other_cls)));

      // if the current object exists ...
      if(!is_null($value)) {
         $obj->set(strtolower($foreign),$value);
         // if the pushed object is still not saved
         if(is_null($other_value)) {
            if(!$obj->save())
               return false;
            $other_value = $obj->get($other_pk);
         }
         $foreign    = self::$mapping[$other_cls][$foreign];
         $other_pk   = self::$mapping[$other_cls][$other_pk];
         $sql        = "update $escape$table$escape set $escape$foreign$escape=$value where $escape$other_pk$escape=$other_value";
         $stmt       = self::query($sql);
         $rst        = $stmt->rowCount()==1;
         $stmt->closeCursor();
         return $rst;
      }

      // if current object does not exists ...
      if(is_null($value)) {
         $this->pushLater($obj);
      }
   }

   private function pushLater($obj) {
      array_push($this->push_later,$obj);
   }

   /**
    * Trigger to use object values as methods
    * Like
    * $user->name("john doe");
    * echo $user->name();
    */
   public function __call($method,$args) {
      if(method_exists($this,$method)) 
         return call_user_func_array(array($this,$method),$args);

      $relation = $this->resolveRelations($method);
      if($relation)
         return $relation;

      $ids = $this->resolveIds($method,$args);
      if($ids)
         return $ids;

      if(!$args) 
         return $this->get($method);
      $this->set($method,$args[0]);
   }

   public static function __callStatic($method,$args) {
      $scope = self::resolveScope($method,$args);
      if($scope)
         return $scope;
      return null;
   }

   /**
    * Trigger to get object values as attributes
    * Like
    * echo $user->name;
    */
   function __get($attr) {
      $changes  = $this->changedAttribute($attr);
      if($changes)
         return $changes;

      $relation = $this->resolveRelations($attr);
      if($relation)
         return $relation;

      $ids = $this->resolveIds($attr);
      if($ids)
         return $ids;
      return $this->get($attr);
   }

   /**
    * Trigger to set object values as attributes
    * Like
    * $user->name = "john doe";
    */
   function __set($attr,$value) {
      $ids = $this->resolveIds($attr,$value);
      if($ids) 
         return $ids;

      $ids = $this->resolveCollection($attr,$value);
      if($ids) 
         return $ids;
      
      $this->set($attr,$value);
   }

   public static function beforeSave($func) {
      $cls = get_called_class();
      self::addCallback($cls,"before_save",$func);
   }

   public static function afterSave($func) {
      $cls = get_called_class();
      self::addCallback($cls,"after_save",$func);
   }

   public static function beforeDestroy($func) {
      $cls = get_called_class();
      self::addCallback($cls,"before_destroy",$func);
   }

   public static function afterDestroy($func) {
      $cls = get_called_class();
      self::addCallback($cls,"after_destroy",$func);
   }

   private function checkCallback($cls,$callback) {
      self::initiateCallbacks($cls);
      foreach(self::$callbacks[$cls][$callback] as $func) {
         if(!call_user_func(array($cls,$func)))
            return false;
      }
      return true;
   }

   private function addCallback($cls,$callback,$func) {
      self::initiateCallbacks($cls);
      array_push(self::$callbacks[$cls][$callback],$func);
   }

   private function initiateCallbacks($cls) {
      if(!array_key_exists($cls,self::$callbacks)) 
         self::$callbacks[$cls] = array();

      $callbacks = array("before_save","after_save","before_destroy","after_destroy");
      foreach($callbacks as $callback) {
         if(!array_key_exists($callback,self::$callbacks[$cls]))
            self::$callbacks[$cls][$callback] = array();
      }
   }

   private function changedAttribute($attr) {
      preg_match('/(\w+)_(change|changed|was)$/',$attr,$matches);
      if(sizeof($matches)<1)
         return null;
      $attr = $matches[1];
      $meth = $matches[2];
      $cur  = $this->get($attr);
      $old  = $this->get($attr,false);

      if($meth=="was")
         return $old;
      if($meth=="changed")
         return $cur==$old;
      if($meth=="change")
         return array($old,$cur);
      return null;
   }

   public function changes() {
      $changes = array();
      $cls    = get_called_class();
      foreach(self::$columns[$cls] as $column) {
         if($cls::getPK()==$column)
            continue;
         $cur = $this->get($column);
         $old = $this->get($column,false);
         if($cur!=$old)
            array_push($changes,$column);
      }
      return $changes;
   }
}
?>
