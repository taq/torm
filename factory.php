<?php
/**
 * Factories
 *
 * PHP version 5.5
 *
 * @category Factories
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Factory main class
 *
 * PHP version 5.5
 *
 * @category Factories
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Factory
{
    private static $_path       = null;
    private static $_factories  = array();
    private static $_options    = array();
    private static $_loaded     = false;

    /**
     * Set the factories path
     *
     * @param string $path path
     *
     * @return null
     */
    public static function setFactoriesPath($path)
    {
        self::$_path = $path;
    }

    /**
     * Return the factories path
     *
     * @return factories path
     */
    public static function getFactoriesPath()
    {
        self::_resolveDefaultPath();
        return self::$_path;
    }

    /**
     * Resolve the default factory path
     *
     * @return null
     */
    private static function _resolveDefaultPath()
    {
        if (!self::$_path) {
            self::$_path = realpath(dirname(__FILE__)."/factories");
        }
    }

    /**
     * Return the factories count
     *
     * @return int count
     */
    public static function factoriesCount()
    {
        self::load();
        return count(self::$_factories);
    }

    /**
     * Define a factory
     *
     * @param string $name    factory name
     * @param mixed  $attrs   attributes
     * @param mixed  $options options to use
     *
     * @return null
     */
    public static function define($name, $attrs, $options = null)
    {
        self::$_factories[$name] = $attrs;
        self::$_options[$name]   = $options;
    }

    /**
     * Return a factory
     *
     * @param string $name factory name
     *
     * @return mixed factory
     */
    public static function get($name) 
    {
        self::load();
        if (!array_key_exists($name, self::$_factories)) {
            return null;
        }
        return self::$_factories[$name];
    }

    /**
     * Load factories
     *
     * @param boolean $force force loading
     *
     * @return boolean loaded or not 
     */
    public static function load($force = false) 
    {
        // already loaded
        if (!$force && self::$_loaded) {
            return false;
        }
        self::_resolveDefaultPath();

        $files = glob(realpath(self::$_path)."/*.php");
        foreach ($files as $file) {
            Log::log("loading factory from $file ...");
            include_once $file;
        }
        self::$_loaded = true;
        return self::$_loaded;
    }

    /**
     * Attributes for a factory
     *
     * @param string $name name of the factory
     *
     * @return mixed attributes 
     */
    public static function attributes_for($name) 
    {
        self::load();
        $data = self::get($name);
        if (!$data) {
            return null;
        }
        return $data;
    }

    /**
     * Create a factory
     *
     * @param string $name factory name
     *
     * @return mixed factory 
     */
    public static function create($name)
    {
        return self::build($name, true);
    }

    /**
     * Build a factory
     *
     * @param string  $name   factory name
     * @param boolean $create create factory
     *
     * @return mixed factory
     */
    public static function build($name, $create = false) 
    {
        self::load();
        $data = self::attributes_for($name);
        if (!$data) {
            return null;
        }

        // if is a different class ...
        if (is_array(self::$_options[$name])
            && array_key_exists("class_name", self::$_options[$name])
        ) {
            $name = self::$_options[$name]["class_name"];
        }

        $cls = ucfirst(strtolower($name));
        $obj = new $cls();
        $pk  = $obj::getPK();

        if (!array_key_exists($pk, $data)) {
            $data[$pk] = null;
        }

        $obj = new $cls($data);  
        if ($create) {
            if (!$obj->isValid()) {
                return null;
            }
            $obj->save();
        }
        return $obj;
    }
}
