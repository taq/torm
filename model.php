<?php
/**
 * Model class, the heart of the system
 *
 * PHP version 5.5
 *
 * @category Model
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Model class, the heart of the system
 *
 * PHP version 5.5
 *
 * @category Model
 * @package  Torm
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Model
{
    use Finders, Storage, Persistence, Errors;

    const CURSOR_NOTHING = 0;
    const CURSOR_CLOSE   = 1;
    const CURSOR_NULL    = 2;

    public  static $connection = null;
    public  static $yaml_file  = null;
    public $errors             = array();

    private static $_table_name      = array();
    private static $_order           = array();
    private static $_pk              = array();
    private static $_columns         = array();
    private static $_ignorecase      = array();
    private static $_mapping         = array();
    private static $_loaded          = array();
    private static $_cc_action       = self::CURSOR_CLOSE;
    private static $_prepared_cache  = array();
    private static $_validations     = array();
    private static $_has_many        = array();
    private static $_has_many_maps   = array();
    private static $_belongs_to      = array();
    private static $_sequence        = array();
    private static $_has_one         = array();
    private static $_callbacks       = array();
    private static $_scopes          = array();
    private static $_sequence_exists = array();
    private static $_connections     = array();

    private $_data         = array();
    private $_prev_data    = array();
    private $_orig_data    = array();
    private $_has_many_ids = array();
    private $_new_rec      = false;
    private $_push_later   = array();

    /**
     * Constructor
     *
     * If data is sent, then it loads columns with it.
     *
     * @param array $data to fill the object
     *
     * @package TORM
     */
    public function __construct($data=null)
    {
        $cls = get_called_class();
        self::_checkLoaded();

        // setting default null values
        $this->_data       = self::_loadNullValues();
        $this->_prev_data  = self::_loadNullValues();
        $this->_orig_data  = self::_loadNullValues();

        // if data not send, is a new record, return
        if ($data == null) {
            $this->_new_rec = true;
            return;
        }

        foreach ($data as $key => $value) {
            // not numeric keys
            if (preg_match("/^\d+$/", $key)) {
                continue;
            }
            $keyr = $key;

            // if ignoring case, convert all keys to lowercase
            if (self::isIgnoringCase()) {
                $keyr = strtolower($key);
                $data[$keyr] = $value;
                if ($keyr != $key) {
                    unset($data[$key]);
                }
            }

            // if there is no mapping array, create one
            if (!array_key_exists($cls, self::$_mapping)) {
                self::$_mapping[$cls] = array();
            }

            // if the key is not on the mapping array, add it
            if (!array_key_exists($keyr, self::$_mapping[$cls])) {
                self::$_mapping[$cls][$key] = $keyr;
            }
        }

        $this->_data       = $data;
        $this->_prev_data  = $data;
        $this->_orig_data  = $data;

        // check if is a new record
        $pk = $cls::getPK();

        // if there is no value on PK, is a new record
        if (!array_key_exists($pk, $this->_data) || empty($this->_data[$pk])) {
            $this->_new_rec = true;
        }

        // check if there is a afterInitialize method, if so, run it
        if (method_exists($this, "afterInitialize")) {
            $this->afterInitialize();
        }
    }

    /**
     * Check if is ignoring case
     *
     * @return boolean ignoring case
     */
    public static function isIgnoringCase() 
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_ignorecase)) {
            return true;
        }
        return self::$_ignorecase[$cls];
    }

    /**
     * Load null values on row columns. Useful to new objects.
     *
     * @return null
     */
    private static function _loadNullValues()
    {
        $values = array();
        $cls    = get_called_class();

        if (!array_key_exists($cls, self::$_columns)) {
            return null;
        }

        foreach (self::$_columns[$cls] as $column) {
            $name = self::isIgnoringCase() ? strtolower($column) : $column;
            $values[$column] = null;
        }
        return $values;
    }

    /**
     * Set the model's table name
     *
     * @param string $table_name table name
     *
     * @return null
     */
    public static function setTableName($table_name)
    {
        $cls = get_called_class();
        self::$_table_name[$cls] = $table_name;
    }

    /**
     * Returns the table name.
     * If not specified one, get the current class name and pluralize it.
     *
     * @param string $cls class name
     *
     * @return string table name
     */
    public static function getTableName($cls=null)
    {
        $cls = $cls ? $cls : get_called_class();

        if (array_key_exists($cls, self::$_table_name)) {
            return self::$_table_name[$cls];
        }
        $name = Inflections::pluralize($cls);
        if (self::isIgnoringCase()) {
            $name = strtolower($name);
        }
        return $name;
    }

    /**
     * Set the primary key column
     *
     * @param string $pk primary key
     *
     * @return null
     */
    public static function setPK($pk)
    {
        $cls = get_called_class();
        self::$_pk[$cls] = $pk;
    }

    /**
     * Returns the primary key column.
     *
     * @return string primary key
     */
    public static function getPK()
    {
        $cls = get_called_class();
        return array_key_exists($cls, self::$_pk) ? self::$_pk[$cls] : "id";
    }

    /**
     * Set the default order
     *
     * @param string $order default
     *
     * @return null
     */
    public static function setOrder($order)
    {
        $cls = get_called_class();
        self::$_order[$cls] = $order;
    }

    /**
     * Returns the default order.
     * If not specified, returns an empty string.
     *
     * @return string order
     */
    public static function getOrder()
    {
        $cls = get_called_class();
        return array_key_exists($cls, self::$_order) ? self::$_order[$cls] : "";
    }

    /**
     * Returns the inverse order.
     * If DESC is specified, returns ASC.
     *
     * @return string order
     */
    public static function getReversedOrder()
    {
        $sort = preg_match("/desc/i", self::getOrder());
        $sort = $sort ? " ASC " : " DESC ";
        return self::getOrder() ? self::getOrder()." $sort" : "";
    }

    /**
     * Sets a specific connection for the current model
     *
     * @param $con PDO connection
     * 
     * @return null
     */
    public static function setConnection($con, $env="development")
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_connections)) {
            self::$_connections[$cls] = array();
        }
        self::$_connections[$cls][$env] = $con;
    }

    /**
     * Resolve the current connection handle.
     * Get it from PDO or from the current class.
     *
     * @return object connection
     */
    public static function resolveConnection() 
    {
        $cls = get_called_class();
        $env = Connection::selectEnvironment();
        if (array_key_exists($cls, self::$_connections) 
            && array_key_exists($env, self::$_connections[$cls])
        ) {
                return self::$_connections[$cls][$env];
        }
        return self::$connection ? self::$connection : Connection::getConnection();
    }

    /**
     * Load column info
     *
     * @return null
     */
    private static function _loadColumns()
    {
        if (!self::resolveConnection()) {
            return;
        }

        $cls = get_called_class();
        self::$_columns[$cls] = array();

        $escape = Driver::$escape_char;

        // try to create the TORM info table
        $type = Driver::$numeric_column;

        // check if the torm table exists
        $rst   = null;
        $check = false;
        try {
            $rst   = self::resolveConnection()->query("select id from torm_info");
            $check = true;
        } catch (\Exception $e) {
        }

        // needs to create table
        if (!$check || !is_object($rst) || !$rst->fetch()) {
            $stmt = self::resolveConnection()->query("create table torm_info (id $type(1))");
            self::closeCursor($stmt);
        }
        if (is_object($rst)) {
            self::closeCursor($rst);
        }

        // insert first value
        $rst = self::resolveConnection()->query("select id from torm_info");
        if (!$rst->fetch()) {
            $stmt = self::resolveConnection()->query("insert into torm_info values (1)");
            self::closeCursor($stmt);
        }
        self::closeCursor($rst);

        // hack to dont need a query string to get columns
        $sql  = "select $escape".self::getTableName()."$escape.* from torm_info left outer join $escape".self::getTableName()."$escape on 1=1";
        $rst  = self::resolveConnection()->query($sql);
        $keys = array_keys($rst->fetch(\PDO::FETCH_ASSOC));

        foreach ($keys as $key) {
            $keyc = self::isIgnoringCase() ? strtolower($key) : $key;
            array_push(self::$_columns[$cls], $keyc);
            self::$_mapping[$cls][$keyc] = $key;
        }
        self::closeCursor($rst);
        self::$_loaded[$cls] = true;
    }

    /**
     * Extract table columns string
     *
     * @return string columns
     */
    public static function extractColumns() 
    {
        self::_checkLoaded();
        $cls = get_called_class();
        $temp_columns = "";
        $escape = Driver::$escape_char;
        foreach (self::$_columns[$cls] as $column) {
            $temp_columns .= "$escape".self::getTableName()."$escape.$escape".self::$_mapping[$cls][$column]."$escape,";
        }
        return substr($temp_columns, 0, strlen($temp_columns)-1);
    }

    /**
     * Extract update columns string
     *
     * @param mixed $values to set columns
     *
     * @return string columns
     */
    public static function extractUpdateColumns($values)
    {
        $cls = get_called_class();
        $temp_columns = "";
        $escape = Driver::$escape_char;
        foreach ($values as $key => $value) {
            $temp_columns .= "$escape".self::$_mapping[$cls][$key]."$escape=?,";
        }
        return substr($temp_columns, 0, strlen($temp_columns)-1);
    }

    /**
     * Extract where conditions string
     *
     * @param mixed $conditions to extract
     *
     * @return string where conditions
     */
    private static function _extractWhereConditions($conditions) 
    {
        if (!$conditions) {
            return "";
        }

        $cls    = get_called_class();
        $escape = Driver::$escape_char;

        if (is_array($conditions)) {
            $temp_cond = "";
            foreach ($conditions as $key => $value) {
                $temp_cond .= "$escape".self::getTableName()."$escape.$escape".self::$_mapping[$cls][$key]."$escape=? and ";
            }
            $temp_cond  = substr($temp_cond, 0, strlen($temp_cond)-5);
            $conditions = $temp_cond;
        }
        return $conditions;
    }

    /**
     * Extract where values from conditions
     *
     * @param mixed $conditions to extract values
     *
     * @return mixed $values
     */
    public static function extractWhereValues($conditions) 
    {
        $values = array();
        if (!$conditions) {
            return $values;
        }

        if (is_array($conditions)) {
            foreach ($conditions as $key => $value) {
                array_push($values, $value);
            }
        }
        return $values;
    }

    /**
     * Create a query builder
     *
     * @return mixed $builder
     */
    private static function _makeBuilder()
    {
        $builder = new Builder();
        $builder->table = self::getTableName();
        $builder->order = self::getOrder();
        return $builder;
    }

    /**
     * Tell if its a new object (not saved)
     *
     * @return boolean new or not
     */
    public function is_new()
    {
        return $this->_new_rec;
    }

    /**
     * Check if the object columns were loaded
     *
     * @return boolean loaded or not
     */
    private static function _checkLoaded()
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_loaded)) {
            self::$_loaded[$cls] = false;
        }
        if (!self::$_loaded[$cls]) {
            self::_loadColumns();
        }
    } 

    /**
     * Check if a column exists
     *
     * @param string $column to check
     *
     * @return column key
     */
    public static function hasColumn($column)
    {
        $cls  = get_called_class();
        $key  = null;
        $keys = self::$_columns[$cls];

        foreach ($keys as $ckey) {
            $col1 = self::isIgnoringCase() ? strtolower($ckey)   : $ckey;
            $col2 = self::isIgnoringCase() ? strtolower($column) : $column;
            if ($col1==$col2) {
                $key = $ckey;
                break;
            }
        }
        return $key;
    }

    /**
     * Execute a prepared statement, trying to get it from cache.
     *
     * @param mixed $obj    object
     * @param mixed $values to use
     *
     * @return object statement
     */
    public static function executePrepared($obj,$values=array())
    {
        if (!self::$_loaded) {
            self::_loadColumns();
        }

        // convert a builder to string
        if (!is_string($obj) && get_class($obj) == "TORM\Builder") {
            $sql = $obj->toString();
        }

        if (is_string($obj)) {
            $sql = $obj;
        }

        $stmt = self::putCache($sql);
        $stmt->execute($values);
        return $stmt;
    }

    /**
     * Run a SQL query
     *
     * @param string $sql query
     *
     * @return mixed result
     */
    public static function query($sql) 
    {
        return self::resolveConnection()->query($sql);
    }

   /**
    * Check if object is valid
    */
   public function isValid() {
      $this->_resetErrors();
      $cls = get_called_class();
      $rtn = true;
      $pk  = self::get(self::getPK());

      if(!array_key_exists($cls,self::$_validations) ||
         sizeof(self::$_validations[$cls])<1)
         return true;

      foreach(self::$_validations[$cls] as $attr=>$validations) {
         $value = $this->_data[$attr];

         foreach($validations as $validation) {
            $validation_key   = array_keys($validation);
            $validation_key   = $validation_key[0];
            $validation_value = array_values($validation);
            $validation_value = $validation_value[0];
            $args = array(get_called_class(),$pk,$attr,$value,$validation_value,$validation);
            $test = call_user_func_array(array("TORM\Validation",$validation_key),$args);
            if(!$test) {
               $rtn = false;
               $this->_addError($attr,Validation::$validation_map[$validation_key]);
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
      if(!$this->_data || !array_key_exists($attr,$this->_data))
         return null;
      return Connection::convertToEncoding($current ? $this->_data[$attr] : $this->_prev_data[$attr]);
   }

   public function set($attr,$value) {
      $pk = self::getPK();
      // can't change the primary key of an existing record
      if(!$this->_new_rec && $attr==$pk)
         return;
      $this->_data[$attr] = $value;
   }

   public static function validates($attr,$validation) {
      $cls = get_called_class();

      // bummer! need to verify the calling class
      if(!array_key_exists($cls,self::$_validations))
         self::$_validations[$cls] = array();

      if(!array_key_exists($attr,self::$_validations[$cls]))
         self::$_validations[$cls][$attr] = array();

      array_push(self::$_validations[$cls][$attr],$validation);
   }

   /**
    * Create a scope
    * @param $scope name
    * @param $conditions
    */
   public static function scope($name,$conditions) {
      $cls = get_called_class();
      
      if(!array_key_exists($cls,self::$_scopes))
         self::$_scopes[$cls] = array();

      if(!array_key_exists($name,self::$_scopes[$cls]))
         self::$_scopes[$cls][$name] = array();
         
      self::$_scopes[$cls][$name] = $conditions;
   }

   public static function resolveScope($name,$args=null) {
      $cls = get_called_class();
      
      if(!array_key_exists($cls,self::$_scopes) ||
         !array_key_exists($name,self::$_scopes[$cls]))
         return null;

      $conditions = self::$_scopes[$cls][$name];
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
      if(!array_key_exists($cls,self::$_has_many))
         self::$_has_many[$cls] = array();
      self::$_has_many[$cls][$attr] = $options ? $options : false;

      $klass = self::hasManyClass($attr);
      $ids   = strtolower($klass)."_ids";
      self::$_has_many_maps[$cls][$ids] = $attr;
   }

   public function hasHasMany($attr) {
      $cls = get_called_class();
      return array_key_exists($cls,self::$_has_many) &&
             array_key_exists($attr,self::$_has_many[$cls]);
   }

   /**
    * Check a has many relationship and returns it resolved, if exists.
    * @param $method name
    * @param $value  
    * @return has many collection, if any
    */
   private static function checkAndReturnMany($method,$value) {
      $cls = get_called_class();
      if(array_key_exists($cls   ,self::$_has_many) &&
         array_key_exists($method,self::$_has_many[$cls]))
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
      $configs = self::$_has_many[$cls][$attr];
      $klass   = is_array($configs) && array_key_exists("class_name",$configs)  ? $configs["class_name"] : ucfirst(preg_replace('/s$/',"",$attr));
      return $klass;
   }

   public static function hasManyForeignKey($attr) {
      if(!self::hasHasMany($attr))
         return null;

      $cls     = get_called_class();
      $configs = self::$_has_many[$cls][$attr];
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

      $configs       = self::$_has_many[$cls][$attr];
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
      if(!array_key_exists($cls,self::$_belongs_to))
         self::$_belongs_to[$cls] = array();
      self::$_belongs_to[$cls][$model] = $options ? $options : false;
   }

   private static function checkAndReturnBelongs($method,$values) {
      $cls = get_called_class();
      if(array_key_exists($cls   ,self::$_belongs_to) &&
         array_key_exists($method,self::$_belongs_to[$cls]))
         return self::resolveBelongsTo($method,$values);
   }

   private static function resolveBelongsTo($attr,$values) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$_belongs_to) ||
         !array_key_exists($attr,self::$_belongs_to[$cls]))
         return null;

      $configs       = self::$_belongs_to[$cls][$attr];
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
      if(!array_key_exists($cls,self::$_has_one))
         self::$_has_one[$cls] = array();
      self::$_has_one[$cls][$attr] = $options ? $options : false;
   }

   /**
    * Resolve the has one relationship and returns the object
    * @param $attr name
    * @param $value
    * @return collection
    */
   private static function resolveHasOne($attr,$value) {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$_has_one) ||
         !array_key_exists($attr,self::$_has_one[$cls]))
         return null;

      $configs       = self::$_has_one[$cls][$attr];
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
      if(array_key_exists($cls   ,self::$_has_one) &&
         array_key_exists($method,self::$_has_one[$cls]))
         return self::resolveHasOne($method,$value);
   }

   public function updateAttributes($attrs) {
      if(array_key_exists(self::getPK(),$attrs))
         unset($attrs[self::getPK()]);
      foreach($attrs as $attr=>$value) 
         $this->_data[$attr] = $value;
      return $this->save();
   }

   /**
    * Set the sequence name, if any
    * @param $name of the sequence
    */
   public static function setSequenceName($name) {
      $cls = get_called_class();
      self::$_sequence[$cls] = $name;
   }

   /**
    * Returns the sequence name, if any
    * @return $name of the sequence
    */
   public static function getSequenceName() {
      $cls = get_called_class();
      if(!array_key_exists($cls,self::$_sequence))
         return null;
      return self::$_sequence[$cls];
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

      // caching if the sequence exists
      $cls  = get_called_class();
      $name = self::resolveSequenceName();
      if(array_key_exists($cls ,self::$_sequence_exists) &&
         array_key_exists($name,self::$_sequence_exists[$cls]))
      {
          return true;
      }

      // checking if the sequence exists
      $escape = Driver::$escape_char;
      $sql    = "select count(sequence_name) as $escape"."CNT"."$escape from user_sequences where sequence_name='$name' or sequence_name='".strtolower($name)."' or sequence_name='".strtoupper($name)."'";
      $stmt   = self::resolveConnection()->query($sql);
      $rst    = $stmt->fetch(\PDO::FETCH_ASSOC);
      $rtn    = intval($rst["CNT"])>0;
      self::closeCursor($stmt);

      // if exists, cache result
      if ($rtn) {
         if (!array_key_exists($cls, self::$_sequence_exists)) {
            self::$_sequence_exists[$cls] = array();
         }
         self::$_sequence_exists[$cls][$name] = true;
      }
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
      self::closeCursor($stmt);
   }

   /**
    * Put a prepared statement on cache, if not there.
    * @return object prepared statement
    */
   public static function putCache($sql) {
      $md5 = md5($sql);
      if(array_key_exists($md5,self::$_prepared_cache)) {
         Log::log("already prepared: $sql");
         return self::$_prepared_cache[$md5];
      } else {
         Log::log("inserting on cache: $sql");
      }
      $prepared = self::resolveConnection()->prepare($sql);
      self::$_prepared_cache[$md5] = $prepared;
      return $prepared;
   }

   /**
    * Get a prepared statement from cache
    * @return object or null if not on cache
    */
   public static function getCache($sql) {
      $md5 = md5($sql);
      if(!array_key_exists($md5,self::$_prepared_cache)) 
         return null;
      return self::$_prepared_cache[$md5];
   }

   /**
    * Resolve relations, if present
    */
   private function resolveRelations($method) {
      $many = self::checkAndReturnMany($method,$this->_data[self::getPK()]);
      if($many)
         return $many;

      $belongs = self::checkAndReturnBelongs($method,$this->_data);
      if($belongs)
         return $belongs;

      $has_one = self::checkAndReturnHasOne($method,$this->_data[self::getPK()]);
      if($has_one)
         return $has_one;

      return null;
   }

   private function resolveIds($attr,$values=null) {
      $cls = get_called_class();

      if(!array_key_exists($cls ,self::$_has_many_maps) ||
         !array_key_exists($attr,self::$_has_many_maps[$cls]))
         return null;

      $klass   = self::hasManyClass(self::$_has_many_maps[$cls][$attr]);
      $foreign = self::hasManyForeignKey(Inflections::pluralize(strtolower($klass)));
      $value   = $this->_data[self::getPK()];
      $klasspk = $klass::getPK();

      // if values sent, set them
      if($values) {
         $this->_has_many_ids = $values;
         $ids   = join(",",$values);
         $this->nullNotPresentIds($klass,$foreign,$ids,$value);
      } else {
         $data = $klass::where(array($foreign=>$value));
         $this->_has_many_ids = array();
         while($row=$data->next())
            array_push($this->_has_many_ids,$row->get($klasspk));
      }
      return $this->_has_many_ids;
   }

   private function resolveCollection($attr,$values) {
      $cls = get_called_class();

      if(!array_key_exists($cls,self::$_has_many_maps))
         return null;
      
      $maps = array_values(self::$_has_many_maps[$cls]);
      if(!in_array($attr,$maps))
         return null;
      
      if(!$values || !is_array($values) || sizeof($values)<1 || !is_object($values[0]))
         return null;
      
      $this->_has_many_ids = array();
      foreach($values as $value) {
         $klass = get_class($value);
         $this->push($value);
         $id = $value->get($klass::getPK());
         if($id)
            array_push($this->_has_many_ids,$id);
      }
      return $this->_has_many_ids;
   }

   private function nullNotPresentIds($klass,$foreign,$ids,$id) {
      $escape  = Driver::$escape_char;
      $klasspk = $klass::getPK();
      $klass   = strtolower($klass);
      $table   = Model::getTableName($klass);
      $sql     = "update $escape$table$escape set $escape$foreign$escape=null where $escape$foreign$escape=$id and $escape$table$escape.$escape$klasspk$escape not in ($ids)";
      $stmt    = self::query($sql);
      self::closeCursor($stmt);
   }

   public function push($obj) {
      if(!$obj)
         return;

      $cls           = get_called_class();
      $escape        = Driver::$escape_char;
      $value         = array_key_exists(self::getPK(),$this->_data) ? $this->_data[self::getPK()] : null;
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
         $foreign    = self::$_mapping[$other_cls][$foreign];
         $other_pk   = self::$_mapping[$other_cls][$other_pk];
         $sql        = "update $escape$table$escape set $escape$foreign$escape=$value where $escape$other_pk$escape=$other_value";
         $stmt       = self::query($sql);
         $rst        = $stmt->rowCount()==1;
         self::closeCursor($stmt);
         return $rst;
      }

      // if current object does not exists ...
      if(is_null($value)) {
         $this->pushLater($obj);
      }
   }

   private function pushLater($obj) {
      array_push($this->_push_later,$obj);
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
      foreach(self::$_callbacks[$cls][$callback] as $func) {
         if(!call_user_func(array($cls,$func)))
            return false;
      }
      return true;
   }

   private function addCallback($cls,$callback,$func) {
      self::initiateCallbacks($cls);
      array_push(self::$_callbacks[$cls][$callback],$func);
   }

   private function initiateCallbacks($cls) {
      if(!array_key_exists($cls,self::$_callbacks)) 
         self::$_callbacks[$cls] = array();

      $callbacks = array("before_save","after_save","before_destroy","after_destroy");
      foreach($callbacks as $callback) {
         if(!array_key_exists($callback,self::$_callbacks[$cls]))
            self::$_callbacks[$cls][$callback] = array();
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
         return $cur!=$old;
      if($meth=="change")
         return array($old,$cur);
      return null;
   }

   public function changes() {
      return $this->changed(true);
   }

   public function changed($attrs=false) {
      $changes = array();
      $cls     = get_called_class();
      foreach(self::$_columns[$cls] as $column) {
         if($cls::getPK()==$column || in_array(strtolower($column),array("created_at","updated_at")))
            continue;
         $cur = $this->get($column);
         $old = $this->get($column,false);
         if($cur==$old) 
            continue;
         $value = $column;
         if($attrs) {
            $value = array($old,$cur);
            $changes[$column] = $value;
         } else
            array_push($changes,$value);
      }
      return $changes;
   }

   public static function closeCursorBehaviour($action) {
       self::$_cc_action = $action;
   }

   private static function closeCursor($stmt) {
      if (self::$_cc_action == self::CURSOR_NOTHING) {
          return;
      }
      if (self::$_cc_action == self::CURSOR_CLOSE && is_object($stmt)) {
          $stmt->closeCursor();
      } else {
          $stmt = null;
      }
   }
}
?>
