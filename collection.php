<?php
namespace TORM;

class Collection implements \Iterator {
   private $data     = null;
   private $cls      = null;
   private $curval   = null;
   private $count    = null;
   private $valid    = false;

   public function __construct($data,$cls) {
      $this->data    = $data;
      $this->cls     = $cls;
      $this->count   = 0;
      $this->valid   = $this->curval!=null;
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
      $cls  = $this->cls;
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
