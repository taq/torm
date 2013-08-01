<?php
   class User extends TORM\Model {
      public function before_save() {
         //echo "user before save callback!\n";
         file_put_contents("/tmp/torm-before-save.log","torm test");
         return true;
      }

      public function after_save() {
         //echo "user after save callback!\n";
         file_put_contents("/tmp/torm-after-save.log","torm test");
         return true;
      }

      public function before_destroy() {
         //echo "user before destroy callback!\n";
         file_put_contents("/tmp/torm-before-destroy.log","torm test");
         return true;
      }

      public function after_destroy() {
         //echo "user after destroy callback!\n";
         file_put_contents("/tmp/torm-after-destroy.log","torm test");
         return true;
      }
   }

   User::setOrder("name");
   User::validates("name" ,array("presence"=>true));
   User::validates("email",array("presence"=>true));
   User::validates("email",array("format"  =>"^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$"));
   User::validates("email",array("uniqueness"=>true,"allow_null"=>true,"allow_blank"=>true));
   User::validates("level",array("numericality"=>true));
   User::validates("code" ,array("format"=>"^[0-9]{5}$","allow_null"=>true));
   User::hasMany("tickets",array("class_name"=>"Ticket"));
   User::hasOne("account");
   User::beforeSave("before_save");
   User::afterSave("after_save");
   User::beforeDestroy("before_destroy");
   User::afterDestroy("after_destroy");
   User::scope("first_level",array("level"=>1));
?>
