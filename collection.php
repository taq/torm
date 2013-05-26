<?php
namespace TORM;

class Collection implements \Iterator {
   private $data = null;
   private $cls  = null;

   public function __construct($data,$cls) {
      $this->data = $data;
      $this->cls  = $cls;
   }

   public function current()  {}
   public function key()      {}
   public function valid()    {}
   public function rewind()   {}
   
   public function next() {
      $cls = $this->cls;
      return new $cls($this->data->fetch(\PDO::FETCH_ASSOC));
   }
}
