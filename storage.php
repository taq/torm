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

trait Storage
{
    private $_data      = array();
    private $_prev_data = array();
    private $_orig_data = array();

    /**
     * Set data
     *
     * @param mixed $data data
     *
     * @return null
     */
    private function _setData($data)
    {
        $this->_data = $data;
    }

    /**
     * Return the object current values
     *
     * @return mixed data
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Return the previous data array
     *
     * @return mixed data
     */
    public function getPrevData()
    {
        return $this->_prev_data;
    }

    /**
     * Return the original data array, immutable through the life of the object
     *
     * @return mixed data
     */
    public function getOriginalData()
    {
        return $this->_orig_data ;
    }
}
?>
