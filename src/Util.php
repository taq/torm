<?php
/**
 * Util class, for sharing among other classes and traits
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
 * Main class
 *
 * PHP version 5.5
 *
 * @category Model
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Util
{
    /**
     * Decamelize a string
     *
     * @param string $str string
     *
     * @return string decamelized
     */
    public static function decamelize($str)
    {
        return substr(preg_replace_callback('/([A-Z][a-z]+)/', 
            function($matches) { 
                return strtolower($matches[0])."_"; 
            }, $str), 0, -1);
    }
}
?>
