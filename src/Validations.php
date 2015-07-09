<?php
/**
 * Methods to use with validations
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

trait Validations
{
    private static $_validations = array();

    /**
     * Check if object is valid
     *
     * @return boolean valid?
     */
    public function isValid() 
    {
        $this->_resetErrors();
        $cls = get_called_class();
        $rtn = true;
        $pk  = self::get(self::getPK());

        if (!array_key_exists($cls, self::$_validations)
            || sizeof(self::$_validations[$cls]) < 1
        ) {
            return true;
        }

        foreach (self::$_validations[$cls] as $attr => $validations) {
            $value = $this->_data[$attr];

            foreach ($validations as $validation) {
                $validation_key   = array_keys($validation);
                $validation_key   = $validation_key[0];
                $validation_value = array_values($validation);
                $validation_value = $validation_value[0];

                $args = array(get_called_class(), $pk, $attr, $value, $validation_value, $validation);
                $test = call_user_func_array(array("TORM\Validation", $validation_key), $args);

                if (!$test) {
                    $rtn = false;
                    $this->_addError($attr, Validation::$validation_map[$validation_key]);
                }
            }
        }
        return $rtn;
    }

    /**
     * Check if attribute is unique
     *
     * @param mixed $id         id
     * @param mixed $attr       to check
     * @param mixed $attr_value to check
     *
     * @return if attribute is unique
     */
    public static function isUnique($id, $attr, $attr_value)
    {
        $obj = self::first(array($attr => $attr_value));
        return $obj==null || $obj->get(self::getPK()) == $id;
    }

    /**
     * Validates an attribute with a validation rule
     *
     * @param string $attr       attribute
     * @param mixed  $validation validation rule
     *
     * @return valid or not
     */
    public static function validates($attr, $validation) 
    {
        $cls = get_called_class();

        if (!array_key_exists($cls, self::$_validations)) {
            self::$_validations[$cls] = array();
        }

        if (!array_key_exists($attr, self::$_validations[$cls])) {
            self::$_validations[$cls][$attr] = array();
        }

        array_push(self::$_validations[$cls][$attr], $validation);
    }
}
?>
