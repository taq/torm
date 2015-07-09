<?php
/**
 * Has one association
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

trait HasOne
{
    private static $_has_one = array();
    private $_has_one_cache  = array();

    /**
     * Create a has one association
     *
     * @param string $attr    attribute
     * @param mixed  $options options to use
     *
     * @return null
     */
    public static function hasOne($attr, $options=null)
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_has_one)) {
            self::$_has_one[$cls] = array();
        }
        self::$_has_one[$cls][$attr] = $options ? $options : false;
    }

    /**
     * Resolve the has one association and returns the object
     *
     * @param string $attr  name
     * @param mixed  $value to use
     *
     * @return collection
     */
    private function _resolveHasOne($attr, $value)
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_has_one)
            || !array_key_exists($attr, self::$_has_one[$cls])
        ) {
            return null;
        }

        if (array_key_exists($attr, $this->_has_one_cache) && $this->_has_one_cache[$attr]) {
            return $this->_has_one_cache[$attr];
        }

        $configs       = self::$_has_one[$cls][$attr];
        $has_one_cls   = is_array($configs) && array_key_exists("class_name", $configs)  ? $configs["class_name"]  : ucfirst(preg_replace('/s$/', "", $attr));
        $this_key      = is_array($configs) && array_key_exists("foreign_key", $configs) ? $configs["foreign_key"] : (self::isIgnoringCase() ? strtolower($cls)."_id" : $cls."_id");
        $obj           = $has_one_cls::first(array($this_key=>$value));

        if ($obj) {
            $this->_has_one_cache[$attr] = $obj;
        }
        return $obj;
    }

    /**
     * Check and return the value of a has one association
     *
     * @param string $method searched
     * @param mixed  $value  the current id
     *
     * @return association
     */
    private function _checkAndReturnHasOne($method, $value)
    {
        $cls = get_called_class();
        if (array_key_exists($cls, self::$_has_one)
            && array_key_exists($method, self::$_has_one[$cls])
        ) {
            return $this->_resolveHasOne($method, $value);
        }
    }
}
?>
