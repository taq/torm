<?php
/**
 * Storage with the object data
 *
 * PHP version 5.5
 *
 * @category Traits
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Errors
{
    /**
     * Add an error to an attribute
     *
     * @param string $attr attribute
     * @param string $msg  message
     *
     * @return null
     */
    public function addError($attr, $msg)
    {
        $this->_addError($attr, $msg);
    }

    /**
     * Add an error to an attribute
     *
     * @param string $attr attribute
     * @param string $msg  message
     *
     * @return null
     */
    private function _addError($attr, $msg)
    {
        if (!array_key_exists($attr, $this->errors)) {
            $this->errors[$attr] = array();
        }
        array_push($this->errors[$attr], $msg);
    }

    /**
     * Return the error messages
     *
     * @return mixed error messages
     */
    public function errorMessages()
    {
        $msgs = array();
        foreach ($this->errors as $key => $values) {
            foreach ($values as $value) {
                array_push($msgs, "$key $value");
            }
        }
        return $msgs;
    }

    /** 
     * Sets the YAML file location
     *
     * @param string $file location
     *
     * @return null
     */
    public function setYAMLFile($file)
    {
        self::$yaml_file = $file;
    }

    /**
     * Return textual and translated error messages
     *
     * @param mixed $errors found
     *
     * @return mixed error messages
     */
    public function fullMessages($errors=null) 
    {
        if (!function_exists("yaml_parse") 
            || is_null(self::$yaml_file) 
            || !file_exists(self::$yaml_file)
        ) {
            return array();
        }

        $rtn    = array();
        $parsed = yaml_parse(file_get_contents(self::$yaml_file));
        $errors = is_null($errors) ? $this->errors : $errors;
        $locale = function_exists("locale_get_default") ? locale_get_default() : "en-US";

        if (!array_key_exists($locale, $parsed)
            || !array_key_exists("errors",   $parsed[$locale]) 
            || !array_key_exists("messages", $parsed[$locale]["errors"])
        ) {
            return $this->errorMessages();
        }

        $msgs = $parsed[$locale]["errors"]["messages"];
        $cls  = strtolower(get_called_class());

        foreach ($errors as $key => $values) {
            $attr = array_key_exists("attributes", $parsed[$locale])        &&
                array_key_exists($cls, $parsed[$locale]["attributes"])      &&
                array_key_exists($key, $parsed[$locale]["attributes"][$cls]) ?
                $parsed[$locale]["attributes"][$cls][$key] : $key;

            foreach ($values as $value) {
                $msg = array_key_exists($value, $msgs) ? $msgs[$value] : ":$value";
                array_push($rtn, "$attr $msg");
            }
        }
        return $rtn;
    }

    /**
     * Reset errors
     *
     * @return null
     */
    private function _resetErrors()
    {
        $this->errors = array();
    }
}
?>
