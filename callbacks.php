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
        self::addCallback($cls, "before_save", $func);
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
        self::addCallback($cls, "after_save", $func);
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
        self::addCallback($cls, "before_destroy", $func);
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
        self::addCallback($cls, "after_destroy", $func);
    }

    /**
     * Check if a callback exists
     *
     * @param string $cls      class
     * @param string $callback callback
     *
     * @return exist or not
     */
    private function _checkCallback($cls, $callback)
    {
        self::initiateCallbacks($cls);
        foreach (self::$_callbacks[$cls][$callback] as $func) {
            if (!call_user_func(array($cls, $func))) {
                return false;
            }
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
}
?>
