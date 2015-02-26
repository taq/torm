<?php
/**
 * Traits to operations to find records
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

trait Finders
{
    /**
     * Find an object by its primary key
     *
     * @param object $id primary key
     *
     * @return mixed result
     */
    public static function find($id) 
    {
        self::_checkLoaded();

        $pk               = self::isIgnoringCase() ? strtolower(self::getPK()) : self::getPK();
        $builder          = self::_makeBuilder();
        $builder->fields  = self::extractColumns();
        $builder->where   = self::_extractWhereConditions(array($pk=>$id));
        $builder->limit   = 1;

        $cls  = get_called_class();
        $stmt = self::executePrepared($builder, array($id));
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }
        return new $cls($data);
    }

    /**
     * Use the WHERE clause to return values
     *
     * @param mixed $conditions string or array - better use is using an array
     *
     * @return Collection of results
     */
    public static function where($conditions) 
    {
        self::_checkLoaded();

        $builder          = self::_makeBuilder();
        $builder->where   = self::_extractWhereConditions($conditions);
        $builder->fields  = self::extractColumns();
        $vals             = self::extractWhereValues($conditions);
        return new Collection($builder, $vals, get_called_class());
    }

    /**
     * Return all values (from an optional condition)
     *
     * @param mixed $conditions optional
     *
     * @return Collection values
     */
    public static function all($conditions=null)
    {
        self::_checkLoaded();

        $builder = self::_makeBuilder();
        $builder->fields  = self::extractColumns();
        $vals    = null;

        if ($conditions) {
            $builder->where = self::_extractWhereConditions($conditions);
            $vals           = self::extractWhereValues($conditions);
        }
        return new Collection($builder, $vals, get_called_class());
    }

    /**
     * Get result by position - first or last
     *
     * @param string $position   "first" or "last"
     * @param mixed  $conditions to extract
     *
     * @return result or null
     */
    private static function _getByPosition($position, $conditions=null)
    {
        self::_checkLoaded();

        $builder          = self::_makeBuilder();
        $builder->fields  = self::extractColumns();
        $builder->order   = $position=="first" ? self::getOrder() : self::getReversedOrder();
        $builder->where   = self::_extractWhereConditions($conditions);
        $vals             = self::extractWhereValues($conditions);

        $cls  = get_called_class();
        $stmt = self::executePrepared($builder, $vals);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }
        return new $cls($data);
    }

    /**
     * Return the first value (get by order)
     *
     * @param mixed $conditions to return
     *
     * @return mixed result
     */
    public static function first($conditions=null)
    {
        return self::_getByPosition("first", $conditions);
    }

    /**
     * Return the last value (get by inverse order)
     *
     * @param mixed $conditions to return
     *
     * @return object result
     */
    public static function last($conditions=null)
    {
        return self::_getByPosition("last", $conditions);
    }

}
?>
