<?php
/**
 * Belongs to association
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

trait BelongsTo
{
    private static $_belongs_to = array();
    private $_belongs_cache     = array();

    /**
     * Create a belongs relationship
     *
     * @param string $model   model
     * @param mixed  $options options for relation
     *
     * @return null
     */
    public static function belongsTo($model, $options=null)
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_belongs_to)) {
            self::$_belongs_to[$cls] = array();
        }
        self::$_belongs_to[$cls][$model] = $options ? $options : false;
    }

    /**
     * Check if is there a belongs to association and return it
     *
     * @param string $attr   attribute
     * @param mixed  $values values to use
     *
     * @return mixed association
     */
    private function _checkAndReturnBelongs($attr, $values)
    {
        $cls = get_called_class();
        if (array_key_exists($cls, self::$_belongs_to)
            && array_key_exists($attr, self::$_belongs_to[$cls])
        ) {
            return $this->_resolveBelongsTo($attr, $values);
        }
    }

    /**
     * Resolve a belongs to association
     *
     * @param string $attr   attribute
     * @param mixed  $values values to use
     *
     * @return association
     */
    private function _resolveBelongsTo($attr, $values)
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_belongs_to)
            || !array_key_exists($attr, self::$_belongs_to[$cls])
        ) {
            return null;
        }

        if (array_key_exists($attr, $this->_belongs_cache) && $this->_belongs_cache[$attr]) {
            return $this->_belongs_cache[$attr];
        }

        $configs       = self::$_belongs_to[$cls][$attr];
        $belongs_cls   = is_array($configs) && array_key_exists("class_name",  $configs) ? $configs["class_name"]  : ucfirst($attr);
        $belongs_key   = is_array($configs) && array_key_exists("foreign_key", $configs) ? $configs["foreign_key"] : strtolower($belongs_cls)."_id";
        $primary_key   = is_array($configs) && array_key_exists("primary_key", $configs) ? $configs["primary_key"] : "id";
        $value         = $values[$belongs_key];
        $obj           = $belongs_cls::first(array($primary_key => $value));

        if ($obj) {
            $this->_belongs_cache[$attr] = $obj;
        }
        return $obj;
    }

    /**
     * Check the belongs key key/attribute
     *
     * @param string $other class
     * 
     * @return key found or null
     */
    private function _getBelongsKey($other)
    {
        $cls = get_called_class();

        if (!isset(self::$_belongs_to[$cls])) {
            return null;
        }

        $other   = strtolower(get_class($other));
        $idx     = $other;
        $foreign = strtolower($other)."_id";
        $found   = isset(self::$_belongs_to[$cls]) && isset(self::$_belongs_to[$cls][$other]) ? self::$_belongs_to[$cls][$other] : null;

        if (is_null($found)) {
            foreach (self::$_belongs_to[$cls] as $key => $val) {
                if (isset($val["class_name"]) && strtolower($other) == strtolower($val["class_name"])) {
                    $other = $val["class_name"];
                    $idx   = $key;
                }

                if (isset($val["foreign_key"]) && strtolower($other) == strtolower($val["class_name"])) {
                    $foreign = $val["foreign_key"];
                }
            }
        }

        if (!isset(self::$_belongs_to[$cls][$idx])) {
            return null;
        }
        return $foreign;
    }
}
?>
