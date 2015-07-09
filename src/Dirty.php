<?php
/**
 * Dirty attributes
 *
 * PHP version 5.5
 *
 * @category Dirty
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Dirty
{
    /**
     * Check if an attribute changed its value since object was loaded
     *
     * @param string $attr attribute
     *
     * @return changed or not
     */
    private function _changedAttribute($attr) 
    {
        preg_match('/(\w+)_(change|changed|was)$/', $attr, $matches);
        if (sizeof($matches) < 1) {
            return null;
        }

        $attr = $matches[1];
        $meth = $matches[2];
        $cur  = $this->get($attr);
        $old  = $this->get($attr, false);

        if ($meth == "was") {
            return $old;
        }
        if ($meth == "changed") {
            return $cur!=$old;
        }
        if ($meth == "change") {
            return array($old, $cur);
        }
        return null;
    }

    /**
     * Return what was changed, as column as key and old and current values
     *
     * @return changes
     */
    public function changes()
    {
        return $this->changed(true);
    }

    /**
     * Return what changed
     *
     * @param boolean $attrs if true, return old and current values
     *
     * @return changes
     */
    public function changed($attrs=false) 
    {
        $changes = array();
        $cls     = get_called_class();

        foreach (self::$_columns[$cls] as $column) {
            // if is the primary key or one of the automatic columns, skip
            if ($cls::getPK() == $column || in_array(strtolower($column), array("created_at","updated_at"))) {
                continue;
            }

            $cur = $this->get($column);         // current value
            $old = $this->get($column, false);  // old value

            // if same value, didn't change
            if ($cur == $old) {
                continue;
            }

            // set the value as the changed column name
            $value = $column;

            // if checking the attributes, return an array with both values
            if ($attrs) {
                $value = array($old, $cur);
                $changes[$column] = $value;
            } else {
                array_push($changes, $value);
            }
        }
        return $changes;
    }
}
?>
