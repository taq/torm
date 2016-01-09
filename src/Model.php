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
    use Finders, Storage, Persistence, Errors, Validations, Scopes, HasMany,
        HasOne, BelongsTo, Sequences, Callbacks, Dirty;

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
    private static $_connections     = array();

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
            $this->_validateAfterInitialize();
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

        $this->_validateAfterInitialize();
    }

    /**
     * Validate after initialize
     *
     * @return null
     */
    private function _validateAfterInitialize()
    {
        if (method_exists($this, "afterInitialize")) {
            $this->afterInitialize();
        }
    }

    /**
     * Reload the current object
     *
     * @return mixed object
     */
    public function reload()
    {
        $data = $this->find($this->id)->getData();
        $this->_setData($data);
        $this->_belongs_cache = array();
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
     * Extract namespace from class name
     *
     * @param string $class class name
     *
     * @return string $class class name
     */
    private static function _extractClassName($cls)
    {
        $tokens = preg_split("/\\\\/", $cls);
        $size   = sizeof($tokens);
        if ($size < 2) {
            return $cls;
        }
        return $tokens[$size-1];
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
        $fullcls = $cls ? $cls : get_called_class();

        if (array_key_exists($fullcls, self::$_table_name)) {
            return self::$_table_name[$fullcls];
        }

        $cls = self::_extractClassName($fullcls);

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
     * @param mixed  $con PDO connection
     * @param string $env enviroment
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
     * Just used for testing, to avoid change the function name withou the
     * underline on all code. This is something PHP should change.
     *
     * @param mixed $conditions to extract
     *
     * @return string where conditions
     */
    public static function extractWhereConditions($conditions)
    {
        return self::_extractWhereConditions($conditions);
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
            // check if is a regular array or an associative one
            if (array_values($conditions) !== $conditions) {
                $conditions = self::_extractWhereAssociativeConditions($conditions, $cls, $escape);
            } else {
                $conditions = self::_extractWhereRegularConditions($conditions, $cls, $escape);
            }
        }
        return $conditions;
    }

    /**
     * Extract where conditions string, from an associative array
     *
     * @param mixed  $conditions to extract
     * @param string $cls        class
     * @param string $escape     escape char
     *
     * @return string where conditions
     */
    private static function _extractWhereAssociativeConditions($conditions, $cls, $escape)
    {
        $temp_cond = "";
        foreach ($conditions as $key => $value) {
            $temp_cond .= "$escape".self::getTableName()."$escape.$escape".self::$_mapping[$cls][$key]."$escape=? and ";
        }
        return substr($temp_cond, 0, strlen($temp_cond)-5);
    }

    /**
     * Extract where conditions string, from a regular array
     *
     * @param mixed  $conditions to extract
     * @param string $cls        class
     * @param string $escape     escape char
     *
     * @return string where conditions
     */
    private static function _extractWhereRegularConditions($conditions, $cls, $escape)
    {
        return $conditions[0]; // the string is always the first
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
            if (array_values($conditions) !== $conditions) {
                $values = self::_extractWhereAssociativeValues($conditions);
            } else {
                $values = self::_extractWhereRegularValues($conditions);
            }
        }
        return $values;
    }

    /**
     * Extract values from an associative array
     *
     * @param mixed $conditions conditions
     *
     * @return mixed values
     */
    private static function _extractWhereAssociativeValues($conditions)
    {
        $values = array();
        foreach ($conditions as $key => $value) {
            array_push($values, $value);
        }
        return $values;
    }

    /**
     * Extract values from a regular array
     *
     * @param mixed $conditions conditions
     *
     * @return mixed values
     */
    private static function _extractWhereRegularValues($conditions)
    {
        return array_slice($conditions, 1);
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
        $builder->cls   = get_called_class();
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
    public static function executePrepared($obj, $values=array())
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

        // if there aren't value marks, execute like a regular query
        if (!preg_match('/\?/', $sql)) {
            return self::query($sql);
        }

        $stmt = Cache::getInstance()->put($sql, get_called_class());
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
     * Get an attribute value
     *
     * @param string  $attr    attribute
     * @param boolean $current current value
     *
     * @return mixed attribute value
     */
    public function get($attr,$current=true)
    {
        if (!$this->_data || !array_key_exists($attr, $this->_data)) {
            return null;
        }
        return Connection::convertToEncoding($current ? $this->_data[$attr] : $this->_prev_data[$attr]);
    }

    /**
     * Set an attribute value
     *
     * @param string $attr  attribute
     * @param mixed  $value value
     *
     * @return null
     */
    public function set($attr, $value)
    {
        $pk = self::getPK();
        // can't change the primary key of an existing record
        if (!$this->_new_rec && $attr == $pk) {
            return;
        }
        $this->_data[$attr] = $value;
    }

    /**
     * Resolve relations on an attribute, if present
     *
     * @param string $attr attribute
     *
     * @return null
     */
    private function _resolveRelations($attr)
    {
        $many = self::_checkAndReturnMany($attr, $this->_data[self::getPK()]);
        if ($many) {
            return $many;
        }

        $belongs = $this->_checkAndReturnBelongs($attr, $this->_data);
        if ($belongs) {
            return $belongs;
        }

        $has_one = $this->_checkAndReturnHasOne($attr, $this->_data[self::getPK()]);
        if ($has_one) {
            return $has_one;
        }
        return null;
    }

    /**
     * Trigger to use object values as methods
     * Like
     * $user->name("john doe");
     * echo $user->name();
     *
     * @param string $method method
     * @param mixed  $args   arguments
     *
     * @return null
     */
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $args);
        }

        $relation = $this->_resolveRelations($method);
        if ($relation) {
            return $relation;
        }

        $ids = $this->_resolveIds($method, $args);
        if ($ids) {
            return $ids;
        }

        if (!$args) {
            return $this->get($method);
        }
        $this->set($method, $args[0]);
    }

    /**
     * Trigger to get object values as attributes
     * Like
     * echo $user->name;
     *
     * @param string $attr attribute
     *
     * @return attribute value
     */
    public function __get($attr) 
    {
        $changes = $this->_changedAttribute($attr);
        if ($changes) {
            return $changes;
        }

        $relation = $this->_resolveRelations($attr);
        if ($relation) {
            return $relation;
        }

        $ids = $this->_resolveIds($attr);
        if ($ids) {
            return $ids;
        }
        return $this->get($attr);
    }

    /**
     * Trigger to set object values as attributes
     * Like
     * $user->name = "john doe";
     *
     * @param string $attr  attribute
     * @param mixed  $value value
     *
     * @return null
     */
    public function __set($attr, $value)
    {
        $ids = $this->_resolveIds($attr, $value);
        if ($ids) {
            return $ids;
        }

        $ids = $this->_resolveCollection($attr, $value);
        if ($ids) {
            return $ids;
        }

        // if is an object, try to find the belongs association
        // if is null, also try to find to nullify it
        if (is_object($value) || is_null($value)) {
            $bkey = $this->_getBelongsKey($attr);
            if (!is_null($bkey) ) {
                $attr  = $bkey;
                if (!is_null($value)) {
                    $ocls  = get_class($value);
                    $okey  = $ocls::getPK();
                    $value = $value->get($okey);
                }
            }
        }
        $this->set($attr, $value);
    }

    /**
     * Behaviour to close cursor
     *
     * @param mixed $action to use
     *
     * @return null
     */
    public static function closeCursorBehaviour($action)
    {
        self::$_cc_action = $action;
    }

    /**
     * Close cursor
     *
     * @param mixed $stmt statement
     *
     * @return null
     */
    public static function closeCursor($stmt) 
    {
        if (self::$_cc_action == self::CURSOR_NOTHING) {
            return;
        }

        if (self::$_cc_action == self::CURSOR_CLOSE && is_object($stmt)) {
            $stmt->closeCursor();
        } else {
            $stmt = null;
        }
    }

    /**
     * Return columns
     *
     * @return mixed columns
     */
    public static function getColumns()
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_columns)) {
            return null;
        }
        return self::$_columns[$cls];
    }
}
?>
