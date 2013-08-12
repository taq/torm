<?php
namespace TORM;

class Builder {
   public $prefix = "select ";
   public $fields = null;
   public $table  = null;
   public $where  = null;
   public $limit  = null;
   public $offset = null;
   public $order  = null;

   public function toString() {
      $array  = array();
      $escape = Driver::$escape_char;

      array_push($array,$this->prefix." ");

      if(is_null($this->fields))
         array_push($array,$escape.$this->table.$escape.".* ");
      else
         array_push($array,$this->fields);

      array_push($array," from $escape".$this->table."$escape ");

      if($this->where)
         array_push($array," where ".$this->where);
      
      if($this->order)
         array_push($array," order by ".$this->order);
      
      if(!is_null($this->limit) && is_null($this->offset) && Driver::$limit_behaviour==Driver::LIMIT_APPEND)
         array_push($array," limit ".$this->limit);

      // basic query
      $query = join(" ",$array);

      if(!is_null($this->limit) && is_null($this->offset) &&
         Driver::$limit_behaviour==Driver::LIMIT_AROUND   &&
         Driver::$limit_query) {
         $query = str_replace("%query%",$query,Driver::$limit_query);
         $query = str_replace("%limit%",$this->limit,$query);
      }

      if(!is_null($this->limit) && !is_null($this->offset) && Driver::$pagination_query) {
         $query = str_replace("%query%",$query,Driver::$pagination_query);
         $query = str_replace("%from%" ,$this->offset,$query);
         $query = str_replace("%to%"   ,$this->limit ,$query);
      }
      return $query;
   }

   public function __toString() {
      return $this->toString();
   }
}
