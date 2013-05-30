<?php
namespace TORM;

class Builder {
   public $table  = null;
   public $where  = null;
   public $limit  = null;
   public $order  = null;

   public function toString() {
      $array = array();
      array_push($array,"select \"".$this->table."\".* from \"".$this->table."\" ");
      if($this->where)
         array_push($array," where ".$this->where);
      if($this->order)
         array_push($array," order by ".$this->order);
      if($this->limit)
         array_push($array," limit ".$this->limit);
      return join(" ",$array);
   }

   public function __toString() {
      return $this->toString();
   }
}
