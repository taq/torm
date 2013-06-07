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

   private function makeBuilderForAggregations($fields) {
      // a lot of people using PHP 5.3 yet ... no deferencing there.
      $builder = $this->builder;
      $table   = $builder->table;
      $where   = $builder->where;

      $builder = new Builder();
      $builder->prefix = "select";
      $builder->fields = $fields;
      $builder->table  = $table;
      $builder->where  = $where;
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
