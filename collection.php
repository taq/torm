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
