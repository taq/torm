<?php
/**
 * Callbacks
 *
 * PHP version 5.5
 *
 * @category Callbacks
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Callbacks
{
    private static $_callbacks = array();

    /**
     * Before save callback
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function beforeSave($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "before_save", $func);
    }

    /**
     * Before save callback
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function afterSave($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "after_save", $func);
    }

    /**
     * Before save callback
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function beforeDestroy($func) 
    {
        $cls = get_called_class();
        self::_addCallback($cls, "before_destroy", $func);
    }

    /**
     * Before save callback
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function afterDestroy($func) 
    {
        $cls = get_called_class();
        self::_addCallback($cls, "after_destroy", $func);
    }

    /**
     * Before create
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function beforeCreate($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "before_create", $func);
    }

    /**
     * After create
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function afterCreate($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "after_create", $func);
    }

    /**
     * Before update
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function beforeUpdate($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "before_update", $func);
    }

    /**
     * After update
     *
     * @param mixed $func callback
     *
     * @return null
     */
    public static function afterUpdate($func)
    {
        $cls = get_called_class();
        self::_addCallback($cls, "after_update", $func);
    }

    /**
     * Check if a callback exists
     *
     * @param string $cls      class
     * @param string $callback callback
     * @param mixed  $context  context (instance)
     *
     * @return exist or not
     */
    private static function _checkCallback($cls, $callback, $context)
    {
        self::_initiateCallbacks($cls);
        foreach (self::$_callbacks[$cls][$callback] as $func) {
            if (!call_user_func(array($context, $func))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add callback
     *
     * @param string $cls      class
     * @param mixed  $callback method
     * @param mixed  $func     to call
     *
     * @return null
     */
    private function _addCallback($cls, $callback, $func) 
    {
        self::_initiateCallbacks($cls);
        array_push(self::$_callbacks[$cls][$callback], $func);
    }

    /**
     * Initiate callbacks
     *
     * @param string $cls class
     *
     * @return null
     */
    private function _initiateCallbacks($cls)
    {
        if (!array_key_exists($cls, self::$_callbacks)) {
            self::$_callbacks[$cls] = array();
        }

        $callbacks = array(
            "before_save",
            "after_save",
            "before_destroy",
            "after_destroy", 
            "before_create", 
            "after_create",
            "before_update", 
            "after_update"
        );

        foreach ($callbacks as $callback) {
            if (!array_key_exists($callback, self::$_callbacks[$cls])) {
                self::$_callbacks[$cls][$callback] = array();
            }
        }
    }
}
?>
