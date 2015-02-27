<?php
/**
 * Inflections
 *
 * PHP version 5.5
 *
 * @category Inflections
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Inflection main class
 *
 * PHP version 5.5
 *
 * @category Inflections
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Inflections
{
    const SINGULAR    = 0;
    const PLURAL      = 1;
    const IRREGULAR   = 2;

    private static $_inflections = array();

    /**
     * Push an inflection
     *
     * @param mixed  $idx      index
     * @param string $singular form
     * @param string $plural   form
     *
     * @return null
     */
    public static function push($idx, $singular, $plural) 
    {
        self::_initialize();
        self::$_inflections[$idx][$singular] = $plural;
    }

    /**
     * Initialize inflections
     *
     * @return null
     */
    private static function _initialize() 
    {
        for ($i=self::SINGULAR; $i <= self::IRREGULAR; $i++) {
            if (!array_key_exists($i, self::$_inflections)) {
                self::$_inflections[$i] = array();
            }
        }
    }

    /**
     * Pluralize 
     *
     * @param string $str string
     *
     * @return pluralized string
     */
    public static function pluralize($str)
    {
        return self::_search($str, self::PLURAL);
    }

    /**
     * Singularize 
     *
     * @param string $str string
     *
     * @return singlarized string
     */
    public static function singularize($str)
    {
        return self::_search($str, self::SINGULAR);
    }

    /**
     * Search an inflection
     *
     * @param string $str string
     * @param mixed  $idx index
     *
     * @return inflection 
     */
    private static function _search($str,$idx) 
    {
        self::_initialize();

        $idx  = $idx == self::PLURAL ? self::SINGULAR : self::PLURAL;
        $vals = self::$_inflections[$idx];

        // adding irregular
        foreach (self::$_inflections[self::IRREGULAR] as $key => $val) {
            $vals[$key] = $val;
            $vals[$val] = $key;
        }

        foreach ($vals as $key => $val) {
            $reg = preg_match('/^\/[\s\S]+\/[imsxeADSUXJu]?$/', $key);
            $exp = $reg ? $key : "/$key/i";
            $mat = preg_match($exp, $str);

            if (!$reg && $mat) {
                return $val;
            }
            if ($reg && $mat) {
                return preg_replace($key, $val, $str);
            }
        }

        // default behaviour - the "s" thing
        return $idx == self::SINGULAR ? trim($str)."s" : preg_replace('/s$/', "", $str);
    }
}
?>
