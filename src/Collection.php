<?php
/**
 * Collection class
 *
 * PHP version 5.5
 *
 * @category Collections
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

/**
 * Collection main class
 *
 * PHP version 5.5
 *
 * @category Collections
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Collection implements \Iterator
{
    private $_data     = null;
    private $_builder  = null;
    private $_vals     = null;
    private $_cls      = null;
    private $_curval   = null;
    private $_count    = null;

    public  $page     = null;
    public  $per_page = null;

    /**
     * Constructor
     *
     * @param mixed  $builder builder object
     * @param mixed  $vals    values
     * @param string $cls     class
     */
    public function __construct($builder, $vals, $cls) 
    {
        $this->_data    = null;
        $this->_builder = $builder;
        $this->_vals    = $vals;
        $this->_cls     = $cls;
        $this->_count   = 0;
    }

    /**
     * Define row limit
     *
     * @param int $limit row limit
     *
     * @return mixed this object
     */
    public function limit($limit)
    {
        $this->_builder->limit = $limit;
        return $this;
    }

    /**
     * Define row order
     *
     * @param int $order row order
     *
     * @return mixed this object
     */
    function order($order)
    {
        $this->_builder->order = $order;
        return $this;
    }

    /**
     * Return current value
     *
     * @return mixed value
     */
    public function current()
    {
        if ($this->_curval == null) {
            return $this->next();
        }
        return new $this->_cls($this->_curval);
    }

    /**
     * Return current key
     *
     * @return mixed key
     */
    public function key()
    {
        return $this->_count;
    }

    /**
     * Return if its valid
     *
     * @return boolean valid
     */
    public function valid()
    {
        return $this->current() != null && $this->_curval != null;
    }

    /**
     * Rewind collection
     *
     * @return null
     */
    public function rewind()
    {
        $this->_count = 0;
    }

    /**
     * Return collection row count
     *
     * Example:
     * echo Person::all()->count();
     *
     * @return int row count
     */
    public function count()
    {
        $cls     = $this->_cls;
        $pk      = $cls::getPK();
        $builder = $this->_makeBuilderForAggregations(" count($pk) ");
        return $this->_executeAndReturnFirst($builder, $this->_vals);
    }

    /**
     * Return collection attribute sum
     *
     * Example:
     * echo Person::all()->sum("age");
     *
     * @param string $attr attribute
     *
     * @return mixed sum
     */
    public function sum($attr)
    {
        $builder = $this->_makeBuilderForAggregations(" sum($attr) ");
        return $this->_executeAndReturnFirst($builder, $this->_vals);
    }

    /**
     * Return collection attribute average
     *
     * Example:
     * echo Person::all()->avg("age");
     *
     * @param string $attr attribute
     *
     * @return mixed average
     */
    public function avg($attr)
    {
        $builder = $this->_makeBuilderForAggregations(" avg($attr) ");
        return $this->_executeAndReturnFirst($builder, $this->_vals);
    }

    /**
     * Return collection attribute minimum value
     *
     * Example:
     * echo Person::all()->min("age");
     *
     * @param string $attr attribute
     *
     * @return mixed minimum
     */
    public function min($attr)
    {
        $builder = $this->_makeBuilderForAggregations(" min($attr) ");
        return $this->_executeAndReturnFirst($builder, $this->_vals);
    }

    /**
     * Return collection attribute maximum value
     *
     * Example:
     * echo Person::all()->max("age");
     *
     * @param string $attr attribute
     *
     * @return mixed maximum
     */
    public function max($attr)
    {
        $builder = $this->_makeBuilderForAggregations(" max($attr) ");
        return $this->_executeAndReturnFirst($builder, $this->_vals);
    }

    /**
     * Return collection pagination page
     *
     * Example:
     * echo Person::all()->paginate(1, 25);
     *
     * @param int $page     current page
     * @param int $per_page rows per page
     *
     * @return mixed this object
     */
    public function paginate($page, $per_page = 50) 
    {
        $this->_builder->limit  = $per_page;
        $this->_builder->offset = ($page - 1) * $per_page;
        $this->page             = $page;
        $this->per_page         = $per_page;

        if (Driver::$pagination_subquery) {
            $this->_builder->limit   = $this->_builder->offset + $per_page - 1;
            $this->_builder->offset  = $this->_builder->offset + 1;
        }
        return $this;
    }

    /**
     * Construct builder for aggregations
     *
     * @param mixed $fields fields to use
     *
     * @return mixed average
     */
    private function _makeBuilderForAggregations($fields)
    {
        $table   = $this->_builder->table;
        $where   = $this->_builder->where;
        $limit   = $this->_builder->limit;
        $offset  = $this->_builder->offset;

        $builder         = new Builder();
        $builder->prefix = "select";
        $builder->fields = $fields;
        $builder->table  = $table;
        $builder->where  = $where;
        $builder->limit  = $limit;
        $builder->offset = $offset;
        return $builder;
    }

    /**
     * Return the first value
     *
     * @param mixed $builder builder
     * @param mixed $vals    values to use
     *
     * @return mixed average
     */
    private function _executeAndReturnFirst($builder,$vals)
    {
        $cls  = $this->_cls;
        $stmt = $cls::executePrepared($builder, $this->_vals);
        $data = $stmt->fetch();
        return $data ? $data[0] : 0;
    }

    /**
     * Destroy collection records
     *
     * Example:
     * Person::all()->destroy();
     *
     * @return destroyed or not
     */
    public function destroy()
    {
        $table   = $this->_builder->table;
        $where   = $this->_builder->where;

        $builder = new Builder();
        $builder->prefix = "delete";
        $builder->fields = "";
        $builder->table  = $table;
        $builder->where  = $where;

        $cls = $this->_cls;
        return $cls::executePrepared($builder, $this->_vals)->rowCount()>0;
    }

    /**
     * Update collection attributes
     *
     * Example:
     * echo Person::all()->updateAttributes("age", 25);
     *
     * @param string $attrs attributes
     *
     * @return updated or not
     */
    public function updateAttributes($attrs)
    {
        $cls     = $this->_cls;
        $table   = $this->_builder->table;
        $where   = $this->_builder->where;
        $escape  = Driver::$escape_char;

        $sql   = "update $escape$table$escape set ";
        $sql  .= $cls::extractUpdateColumns($attrs, ",");
        $vals  = $cls::extractWhereValues($attrs);

        if (!empty($where)) {
            $sql .= " where $where";
        }
        $nval = array_merge($vals, is_array($this->_vals) ? $this->_vals : array());
        return $cls::executePrepared($sql, $nval);
    }

    /**
     * Convert collection to an array
     *
     * Example:
     * echo Person::all()->toArray();
     *
     * @param string $limit -1 to all collection, otherwise the number of 
     * elements
     *
     * @return mixed average
     */
    public function toArray($limit=-1)
    {
        $ar  = array();
        $cnt = 0;

        while ($data=$this->next()) {
            array_push($ar, $data);
            $cnt ++;
            if ($limit != -1 && $cnt >= $limit) {
                break;
            }
        }
        return $ar;
    }

    /**
     * Get the next result from collection
     *
     * @return mixed result
     */
    private function _getCurrentData()
    {
        $cls = $this->_cls;
        if (!$this->_data) {
            $this->_data = $cls::executePrepared($this->_builder, $this->_vals);
        }
        return $this->_data->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Return the next collection object
     *
     * Example:
     * echo Person::all()->next();
     *
     * @return mixed object
     */
    public function next()
    {
        $cls  = $this->_cls;
        $data = $this->_getCurrentData();

        if (!$data) {
            $this->_curval = null;
            return $this->_curval;
        } else {
            ++$this->_count;
            $this->_curval = $data;
            return new $this->_cls($this->_curval);
        }
    }

    /**
     * Call a method
     *
     * @param string $method to call
     * @param mixed  $args   arguments to send
     *
     * @return method return
     */
    public function __call($method, $args)
    {
        if (!$this->_cls) {
            return null;
        }
        $cls   = $this->_cls;
        $conditions = Model::getScope($method, $cls);
        if (!$conditions) {
            return $this;
        }
        if (is_callable($conditions)) {
            $conditions = $conditions($args);
        }
        $this->_builder->where .= " and $conditions";
        return $this;
    }
}
