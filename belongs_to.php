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
    /**
     * Create a belongs relationship
     *
     * @param string $attr    attribute
     * @param mixed  $options options for relation
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
    private static function _checkAndReturnBelongs($attr, $values)
    {
        $cls = get_called_class();
        if (array_key_exists($cls, self::$_belongs_to)
            && array_key_exists($attr, self::$_belongs_to[$cls])
        ) {
            return self::_resolveBelongsTo($attr, $values);
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
    private static function _resolveBelongsTo($attr, $values)
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_belongs_to)
            || !array_key_exists($attr, self::$_belongs_to[$cls])
        ) {
            return null;
        }

        $configs       = self::$_belongs_to[$cls][$attr];
        $belongs_cls   = is_array($configs) && array_key_exists("class_name",  $configs) ? $configs["class_name"]  : ucfirst($attr);
        $belongs_key   = is_array($configs) && array_key_exists("foreign_key", $configs) ? $configs["foreign_key"] : strtolower($belongs_cls)."_id";
        $primary_key   = is_array($configs) && array_key_exists("primary_key", $configs) ? $configs["primary_key"] : "id";
        $value         = $values[$belongs_key];
        $obj           = $belongs_cls::first(array($primary_key => $value));
        return $obj;
    }
}
?>
