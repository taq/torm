<?php
namespace TORM;

class Collection implements \Iterator {
   private $data     = null;
   private $builder  = null;
   private $vals     = null;
   private $cls      = null;
   private $curval   = null;
   private $count    = null;
   private $valid    = false;
   public  $page     = null;
   public  $per_page = null;

   public function __construct($builder,$vals,$cls) {
      $this->data    = null;
      $this->builder = $builder;
      $this->vals    = $vals;
      $this->cls     = $cls;
      $this->count   = 0;
      $this->valid   = true;
   }

   function limit($limit) {
      $this->builder->limit = $limit;
      return $this;
   }

   function order($order) {
      $this->builder->order = $order;
      return $this;
   }

   public function current()  {
      if($this->curval==null)
         return $this->next();
      return new $this->cls($this->curval);
   }
   
   public function key() {
      return $this->count;
   }
      
   public function valid() {
      return $this->valid;
   }    
   
   public function rewind() {
      $this->count = 0;
   }

   public function count() {
      $builder = $this->makeBuilderForAggregations(" count(*) ");
      return $this->executeAndReturnFirst($builder,$this->vals);
   }

   public function sum($attr) {
      $builder = $this->makeBuilderForAggregations(" sum($attr) ");
      return $this->executeAndReturnFirst($builder,$this->vals);
   }

   public function avg($attr) {
      $builder = $this->makeBuilderForAggregations(" avg($attr) ");
      return $this->executeAndReturnFirst($builder,$this->vals);
   }

   public function min($attr) {
      $builder = $this->makeBuilderForAggregations(" min($attr) ");
      return $this->executeAndReturnFirst($builder,$this->vals);
   }

   public function max($attr) {
      $builder = $this->makeBuilderForAggregations(" max($attr) ");
      return $this->executeAndReturnFirst($builder,$this->vals);
   }

   public function paginate($page,$per_page=50) {
      $this->builder->limit  = $per_page;
      $this->builder->offset = ($page-1)*$per_page;
      $this->page            = $page;
      $this->per_page        = $per_page;

      if(Driver::$pagination_subquery) {
         $this->builder->limit   = $this->builder->offset+$per_page-1;
         $this->builder->offset  = $this->builder->offset+1;
      }
      return $this;
   }

   private function makeBuilderForAggregations($fields) {
      // a lot of people using PHP 5.3 yet ... no deferencing there.
      $builder = $this->builder;
      $table   = $builder->table;
      $where   = $builder->where;
      $limit   = $builder->limit;
      $offset  = $builder->offset;

      $builder = new Builder();
      $builder->prefix = "select";
      $builder->fields = $fields;
      $builder->table  = $table;
      $builder->where  = $where;
      $builder->limit  = $limit;
      $builder->offset = $offset;
      return $builder;
   }

   private function executeAndReturnFirst($builder,$vals) {
      $cls  = $this->cls;
      $stmt = $cls::executePrepared($builder,$this->vals);
      $data = $stmt->fetch();
      if(!$data)
         return 0;
      return $data[0];
   }

   public function destroy() {
      $builder = $this->builder;
      $table   = $builder->table;
      $where   = $builder->where;

      $builder = new Builder();
      $builder->prefix = "delete";
      $builder->fields = "";
      $builder->table  = $table;
      $builder->where  = $where;

      $cls = $this->cls;
      return $cls::executePrepared($builder,$this->vals)->rowCount()>0;
   }

   public function updateAttributes($attrs) {
      $cls     = $this->cls;
      $builder = $this->builder;
      $table   = $builder->table;
      $where   = $builder->where;
      $escape  = Driver::$escape_char;

      $sql   = "update $escape$table$escape set ";
      $sql  .= $cls::extractUpdateColumns($attrs,",");
      $vals  = $cls::extractWhereValues($attrs);
      if(!empty($where))
         $sql .= " where $where";
      $nval = array_merge($vals,is_array($this->vals) ? $this->vals : array());
      return $cls::executePrepared($sql,$nval);
   }

   public function toArray() {
      $ar = array();
      while($data=$this->next())
         array_push($ar,$data);
      return $ar;
   }
   
   public function next() {
      $cls = $this->cls;

      if(!$this->data) {
         $this->data = $cls::executePrepared($this->builder,$this->vals);
      }
      $data = $this->data->fetch(\PDO::FETCH_ASSOC);

      if(!$data) {
         $this->valid  = false;
         $this->curval = null;
         return $this->curval;
      } else {
         ++$this->count;
         $this->curval = $data;
         return new $this->cls($this->curval);
      }
   }
}
