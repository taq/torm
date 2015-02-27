<?php
/**
 * Validation
 *
 * PHP version 5.5
 *
 * @category Validations
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Validation main class
 *
 * PHP version 5.5
 *
 * @category Validations
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Validation
{
    const VALIDATION_PRESENCE     = "presence";
    const VALIDATION_FORMAT       = "format";
    const VALIDATION_UNIQUENESS   = "uniqueness";
    const VALIDATION_NUMERICALITY = "numericality";

    public static $validation_map = array(
        "presence"      => self::VALIDATION_PRESENCE,
        "format"        => self::VALIDATION_FORMAT,
        "uniqueness"    => self::VALIDATION_UNIQUENESS,
        "numericality"  => self::VALIDATION_NUMERICALITY
    );

    /**
     * Check if an attribute is present
     *
     * @param string $cls              class
     * @param mixed  $id               id
     * @param string $attr             attribute
     * @param mixed  $attr_value       attribute value
     * @param mixed  $validation_value validation value
     * @param mixed  $options          options
     *
     * @return valid or not
     */
    public static function presence($cls, $id, $attr, $attr_value, $validation_value, $options)
    {
        if (!$validation_value) {
            return true;
        }
        return strlen(trim($attr_value)) > 0;
    }

    /**
     * Check if an attribute format is ok
     *
     * @param string $cls              class
     * @param mixed  $id               id
     * @param string $attr             attribute
     * @param mixed  $attr_value       attribute value
     * @param mixed  $validation_value validation value
     * @param mixed  $options          options
     *
     * @return valid or not
     */
    public static function format($cls, $id, $attr, $attr_value, $validation_value, $options)
    {
        // check if allow blank values
        if (!is_null($options) && array_key_exists("allow_blank", $options) && strlen(trim($attr_value)) < 1) {
            return true;
        }

        // check if allow null values
        if (!is_null($options) && array_key_exists("allow_null", $options) && is_null($attr_value)) {
            return true;
        }

        // check format using regex
        return preg_match("/$validation_value/u", $attr_value);
    }

    /**
     * Check if an attribute is unique
     *
     * @param string $cls              class
     * @param mixed  $id               id
     * @param string $attr             attribute
     * @param mixed  $attr_value       attribute value
     * @param mixed  $validation_value validation value
     * @param mixed  $options          options
     *
     * @return valid or not
     */
    public static function uniqueness($cls, $id, $attr, $attr_value, $validation_value, $options)
    {
        // check if allow null values
        if (!is_null($options) && array_key_exists("allow_null", $options) && is_null($attr_value)) {
            return true;
        }
        // check if allow blank values
        if (!is_null($options) && array_key_exists("allow_blank", $options) && strlen(trim($attr_value)) < 1) {
            return true;
        }
        // use a class method to check if it is unique
        return call_user_func_array(array("\\".$cls, "isUnique"), array($id, $attr, $attr_value));
    }

    /**
     * Check if an attribute is numeric
     *
     * @param string $cls              class
     * @param mixed  $id               id
     * @param string $attr             attribute
     * @param mixed  $attr_value       attribute value
     * @param mixed  $validation_value validation value
     * @param mixed  $options          options
     *
     * @return valid or not
     */
    public static function numericality($cls, $id, $attr, $attr_value, $validation_value, $options)
    {
        return preg_match("/^[-\.0-9]+$/", trim($attr_value));
    }
}
?>
