<?php
/**
 * Persistence operations
 *
 * PHP version 5.5
 *
 * @category Traits
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Persistence
{
    /**
     * Save or update currenct object
     *
     * @param boolean $force to force saving
     *
     * @return boolean saved/updated
     */
    public function save($force=false)
    {
        if (!self::$_loaded) {
            self::_loadColumns();
        }

        // check for callbacks before validation below
        $calling = get_called_class();
        if (!self::_checkCallback($calling, "before_save", $this)) {
            return false;
        }

        // with all the before callbacks checked, check if its valid
        if (!$this->isValid()) {
            return false;
        }

        $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
        $pk_value   = array_key_exists($pk, $this->_data) ? $this->_data[$pk] : null;
        $attrs      = $this->_data;

        if (!$pk_value) {
            // if there is a method to get the new primary key value on the class, 
            // call it
            if (method_exists($calling, "getNewPKValue")) {
                $pk_value = $calling::getNewPKValue();
                if (!$this->_data[$pk]) {
                    $this->_data[$pk] = $pk_value;
                }
                $attrs = $this->_data;
            }
        }

        // if found a primary key value, check if exists
        if ($pk_value) {
            $this->_new_rec = !self::find($pk_value);
        }

        $rst    = false;
        $newr   = false;

        if ($this->_new_rec) {
            $newr = true;
            self::_checkCallback($calling, "before_create", $this);
            $rst = $this->_insert($attrs, $calling, $pk, $pk_value);
        } else {
            self::_checkCallback($calling, "before_update", $this);

            // no need to update if there weren't changes
            if (sizeof($this->changed()) < 1 && !$force) {
                Log::log("No changes, not updating");
                $rst = true;
            } else {
                $rst = $this->_update($attrs, $calling, $pk, $pk_value);
            }
        }

        if ($rst) {
            self::_checkCallback($calling, "after_save", $this);
            if ($newr) {
                self::_checkCallback($calling, "after_create", $this);
            } else {
                self::_checkCallback($calling, "after_update", $this);
            }
        }
        $this->_prev_data = $this->_data;
        return $rst;
    }

    /**
     * Insert a new record
     *
     * @param mixed  $attrs    attributes
     * @param string $calling  class
     * @param string $pk       primary key
     * @param mixed  $pk_value primary key value
     *
     * @return inserted or not
     */
    private function _insert($attrs, $calling, $pk, $pk_value)
    {
        $escape        = Driver::$escape_char;
        $vals          = array();
        $create_column = self::hasColumn("created_at");
        $update_column = self::hasColumn("updated_at");

        $sql = "insert into $escape".$calling::getTableName()."$escape (";

        // remove the current value when need to insert a NULL value to create 
        // the autoincrement value
        if (Driver::$primary_key_behaviour == Driver::PRIMARY_KEY_DELETE && !$pk_value) {
            unset($attrs[$pk]);
        }

        if (Driver::$primary_key_behaviour == Driver::PRIMARY_KEY_SEQUENCE && empty($pk_value)) {
            $seq_name   = self::resolveSequenceName();

            // check if the sequence exists
            self::_checkSequence();
            if (!self::_sequenceExists()) {
                $this->_addError($pk, "Sequence $seq_name could not be created");
                return false;
            }

            // get the sequence next value
            $attrs[$pk] = self::sequenceNextVal($seq_name);
        } 

        // use sequence, but there is already a value on the primary key.
        // remember that it will allow this only if is really a record that
        // wasn't found when checking for the primary key, specifying that its 
        // a new record!
        if (Driver::$primary_key_behaviour == Driver::PRIMARY_KEY_SEQUENCE && !empty($pk_value)) {
            $attrs[$pk] = $pk_value;
        }

        if ($create_column && array_key_exists($create_column, $attrs)) {
            unset($attrs[$create_column]);
        }

        if ($update_column && array_key_exists($update_column, $attrs)) {
            unset($attrs[$update_column]);
        }

        // marks to insert values on prepared statement
        $marks = array();

        foreach ($attrs as $attr => $value) {
            $sql .= "$escape".self::$_mapping[$calling][$attr]."$escape,";
            array_push($marks, "?");
        }

        // if is there a 'created_at' column ...
        if ($create_column) {
            $sql .= "$escape".self::$_mapping[$calling][$create_column]."$escape,";
            array_push($marks, Driver::$current_timestamp);
        }

        // if is there an 'updated_at' column ...
        if ($update_column) {
            $sql .= "$escape".self::$_mapping[$calling][$update_column]."$escape,";
            array_push($marks, Driver::$current_timestamp);
        }

        $marks = join(",", $marks);
        $sql   = substr($sql, 0, strlen($sql)-1);
        $sql  .= ") values ($marks)";

        // now fill the $vals array with all values to be inserted on the 
        // prepared statement
        foreach ($attrs as $attr => $value) {
            array_push($vals, $value);
        }
        $rtn = self::executePrepared($sql, $vals)->rowCount()==1;

        // if inserted
        if ($rtn) {
            // check for last inserted value
            $lid = null;
            if (Driver::$last_id_supported) {
                $lid = self::resolveConnection()->lastInsertId();
                if (empty($this->_data[$pk]) && !empty($lid)) {
                    $this->_data[$pk] = $lid;
                }
            }

            // or, like Oracle, if the database does not support last inserted id
            if (empty($this->_data[$pk]) && empty($lid) && !empty($attrs[$pk])) {
                $this->_data[$pk] = $attrs[$pk];
            }

            // check for database filled columns
            if ($this->_data[$pk]) {
                $found = self::find($this->_data[$pk]);
                if ($found) {
                    if ($create_column) {
                        $this->_data[$create_column] = $found->get($create_column);
                    }
                }
            }

            // push later objects
            foreach ($this->_push_later as $obj) {
                $this->push($obj);
            }
            $this->_push_later = array();
        }
        Log::log($sql);
        return $rtn;
    }

    /**
     * Update a record
     *
     * @param mixed  $attrs    attributes
     * @param string $calling  class
     * @param string $pk       primary key
     * @param mixed  $pk_value primary key value
     *
     * @return boolean updated or not
     */
    private function _update($attrs,$calling,$pk,$pk_value)
    {
        $escape        = Driver::$escape_char;
        $vals          = array();
        $update_column = self::hasColumn("updated_at");
        $create_column = self::hasColumn("created_at");

        // no way to update a primary key!
        unset($attrs[$pk]);

        $sql = "update $escape".$calling::getTableName()."$escape set ";

        foreach ($attrs as $attr => $value) {
            if (($update_column &&    $attr == $update_column)
                || ($create_column && $attr == $create_column)
            ) {
                continue;
            }

            if (strlen(trim($value)) < 1) {
                $value = null;
            }

            $sql .= "$escape".self::$_mapping[$calling][$attr]."$escape=?,";
            array_push($vals, $value);
        }

        if ($update_column) {
            $sql .= "$escape".self::$_mapping[$calling][$update_column]."$escape=".Driver::$current_timestamp.",";
        }

        $sql  = substr($sql, 0, strlen($sql)-1);
        $sql .= " where $escape".self::getTableName()."$escape.$escape".self::$_mapping[$calling][$pk]."$escape=?";
        array_push($vals, $pk_value);

        Log::log($sql);
        return self::executePrepared($sql, $vals)->rowCount()==1;
    }

    /**
     * Destroy the current object
     *
     * @return boolean destroyed or not
     */
    public function destroy()
    {
        if (!self::$_loaded) {
            self::_loadColumns();
        }

        $calling = get_called_class();

        if (!self::_checkCallback($calling, "before_destroy", $this)) {
            return false;
        }

        $table_name = $calling::getTableName();
        $pk         = $calling::isIgnoringCase() ? strtolower($calling::getPK()) : $calling::getPK();
        $pk_value   = $this->_data[$pk];
        $escape     = Driver::$escape_char;
        $sql        = "delete from $escape$table_name$escape where $escape$table_name$escape.$escape".self::$_mapping[$calling][$pk]."$escape=?";
        Log::log($sql);

        $rst = self::executePrepared($sql, array($pk_value))->rowCount()==1;
        if ($rst) {
            self::_checkCallback($calling, "after_destroy", $this);
        }
        return $rst;
    }

    /**
     * Update object attributes
     *
     * @param mixed $attrs attributes
     *
     * @return updated or not
     */
    public function updateAttributes($attrs)
    {
        if (array_key_exists(self::getPK(), $attrs)) {
            unset($attrs[self::getPK()]);
        }
        foreach ($attrs as $attr => $value) {
            $this->_data[$attr] = $value;
        }
        return $this->save();
    }
}
?>
