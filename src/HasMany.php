<?php
/**
 * Has many association
 *
 * PHP version 5.5
 *
 * @category Associations
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait HasMany
{
    private static $_has_many      = array();
    private static $_has_many_maps = array();
    private        $_has_many_ids  = array();

    /**
     * Create a has many relationship
     *
     * @param string $attr    attribute
     * @param mixed  $options to use
     *
     * @return null
     */
    public static function hasMany($attr, $options=null)
    {
        $cls = get_called_class();

        if (!array_key_exists($cls, self::$_has_many)) {
            self::$_has_many[$cls] = array();
        }

        self::$_has_many[$cls][$attr] = $options ? $options : false;

        $klass = self::hasManyClass($attr);
        $ids   = strtolower($klass)."_ids";
        self::$_has_many_maps[$cls][$ids] = $attr;
    }

    /**
     * Check if there is a has many association
     *
     * @param string $attr attribute to check
     *
     * @return boolean 
     */
    public static function hasHasMany($attr) 
    {
        $cls = get_called_class();
        return array_key_exists($cls,  self::$_has_many) &&
               array_key_exists($attr, self::$_has_many[$cls]);
    }

    /**
     * Check a has many association and returns it resolved, if exists.
     *
     * @param string $method name
     * @param mixed  $value  of this object primary key
     *
     * @return has many collection, if any
     */
    private static function _checkAndReturnMany($method, $value) 
    {
        $cls = get_called_class();
        if (array_key_exists($cls, self::$_has_many)
            && array_key_exists($method, self::$_has_many[$cls])
        ) {
            return self::_resolveHasMany($method, $value);
        }
    }

    /**
     * Check class from a relation, like hasManyClass("tickets") => "Ticket"
     *
     * @param string $attr attribute to check
     *
     * @return the class
     */
    public static function hasManyClass($attr)
    {
        if (!self::hasHasMany($attr)) {
            return null;
        }

        $cls     = get_called_class();
        $configs = self::$_has_many[$cls][$attr];
        $klass   = is_array($configs) && array_key_exists("class_name", $configs)  ? $configs["class_name"] : ucfirst(preg_replace('/s$/', "", $attr));
        return $klass;
    }

    /**
     * Check if there is a has many foreign key
     *
     * @param string $attr attribute
     *
     * @return string foreign key
     */
    public static function hasManyForeignKey($attr)
    {
        if (!self::hasHasMany($attr)) {
            return null;
        }

        $cls     = get_called_class();
        $configs = self::$_has_many[$cls][$attr];
        $key     = is_array($configs) && array_key_exists("foreign_key", $configs) ? $configs["foreign_key"] : (self::isIgnoringCase() ? strtolower($cls)."_id" : $cls."_id");
        return $key;
    }

    /**
     * Resolve the has many association and returns the collection with values
     *
     * @param string $attr  association name
     * @param mixed  $value of this object
     * 
     * @return collection
     */
    private static function _resolveHasMany($attr, $value)
    {
        $cls = get_called_class();
        if (!self::hasHasMany($attr)) {
            return null;
        }

        $configs       = self::$_has_many[$cls][$attr];
        $has_many_cls  = self::hasManyClass($attr);
        $this_key      = self::hasManyForeignKey($attr);
        $collection    = $has_many_cls::where(array($this_key => $value));
        return $collection;
    }

    /**
     * Resolve has many ids
     *
     * @param string $attr   attribute name
     * @param mixed  $values to resolve
     *
     * @return ids
     */
    private function _resolveIds($attr, $values=null)
    {
        $cls = get_called_class();

        if (!array_key_exists($cls, self::$_has_many_maps)
            || !array_key_exists($attr, self::$_has_many_maps[$cls])
        ) {
            return null;
        }

        $klass   = self::hasManyClass(self::$_has_many_maps[$cls][$attr]);
        $foreign = self::hasManyForeignKey(Inflections::pluralize(strtolower($klass)));
        $value   = $this->_data[self::getPK()];
        $klasspk = $klass::getPK();

        // if values sent, set them
        if ($values) {
            $this->_has_many_ids = $values;
            $ids = join(",", $values);
            $this->_nullNotPresentIds($klass, $foreign, $ids, $value);
        } else {
            $data = $klass::where(array($foreign => $value));
            $this->_has_many_ids = array();
            while ($row=$data->next()) {
                array_push($this->_has_many_ids, $row->get($klasspk));
            }
        }
        return $this->_has_many_ids;
    }

    /**
     * Set values to a has many association, from an array
     *
     * @param string $attr   attribute
     * @param mixed  $values values to use
     *
     * @return collections
     */
    private function _resolveCollection($attr, $values)
    {
        $cls = get_called_class();

        if (!array_key_exists($cls, self::$_has_many_maps)) {
            return null;
        }

        $maps = array_values(self::$_has_many_maps[$cls]);
        if (!in_array($attr, $maps)) {
            return null;
        }

        if (!$values || !is_array($values) || sizeof($values)<1 || !is_object($values[0])) {
            return null;
        }

        $this->_has_many_ids = array();

        foreach ($values as $value) {
            $klass = get_class($value);
            $this->push($value);
            $id = $value->get($klass::getPK());
            if ($id) {
                array_push($this->_has_many_ids, $id);
            }
        }
        return $this->_has_many_ids;
    }

    /**
     * Nullify foreign class keys not present in array
     *
     * @param string $klass   association class
     * @param string $foreign foreign key on association class
     * @param mixed  $ids     ids to check
     * @param string $id      id of the foreign association
     *
     * @return null
     */
    private function _nullNotPresentIds($klass, $foreign, $ids, $id)
    {
        $escape  = Driver::$escape_char;
        $klasspk = $klass::getPK();
        $klass   = strtolower($klass);
        $table   = Model::getTableName($klass);
        $sql     = "update $escape$table$escape set $escape$foreign$escape=null where $escape$foreign$escape=$id and $escape$table$escape.$escape$klasspk$escape not in ($ids)";
        $stmt    = self::query($sql);
        self::closeCursor($stmt);
    }

    /**
     * Push an objet to a has many association
     *
     * @param mixed $obj object
     *
     * @return pushed
     */
    public function push($obj)
    {
        if (!$obj) {
            return;
        }

        $cls           = get_called_class();
        $escape        = Driver::$escape_char;
        $value         = array_key_exists(self::getPK(), $this->_data) ? $this->_data[self::getPK()] : null;
        $other_cls     = get_class($obj);
        $other_pk      = $other_cls::getPK();
        $other_value   = $obj->get($other_pk);
        $table         = Model::getTableName($other_cls);
        $foreign       = self::hasManyForeignKey(Inflections::pluralize(strtolower($other_cls)));

        // if the current object exists ...
        if (!is_null($value)) {
            $obj->set(strtolower($foreign), $value);

            // if the pushed object is still not saved
            if (is_null($other_value)) {
                if (!$obj->save()) {
                    return false;
                }
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
        if (is_null($value)) {
            $this->_pushLater($obj);
        }
    }

    /**
     * Send an object to push later
     *
     * @param mixed $obj object
     *
     * @return null
     */
    private function _pushLater($obj)
    {
        array_push($this->_push_later, $obj);
    }
}
?>
