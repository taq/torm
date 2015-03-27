<?php
   class User extends TORM\Model {
      public function before_save() {
         //echo "user before save callback!\n";
         file_put_contents("/tmp/torm-before-save.log","torm test");
         return true;
      }

      public function strip_invalid() {
          $this->email = str_replace("#","",$this->email);
          return true;
      }

      public function after_save() {
         //echo "user after save callback!\n";
         file_put_contents("/tmp/torm-after-save.log","torm test");
         return true;
      }

      public function before_create()
      {
         file_put_contents("/tmp/torm-before-create.log", "torm test");
         return true;
      }

      public function after_create()
      {
         file_put_contents("/tmp/torm-after-create.log", "torm test");
         return true;
      }

      public function before_update()
      {
         file_put_contents("/tmp/torm-before-update.log", "torm test");
         return true;
      }

      public function after_update()
      {
         file_put_contents("/tmp/torm-after-update.log", "torm test");
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

      public function afterInitialize() {
         if (is_null($this->name)) {
            $this->name = "Unnamed User";
         }
      }
   }

   User::setOrder("name");
   User::validates("name" ,array("presence"=>true));
   User::validates("name" ,array("format"=>"^[\p{L},]{2,} [\p{L}\s\.]{2,}"));
   User::validates("email",array("presence"=>true));
   User::validates("email",array("format"  =>"^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$"));
   User::validates("email",array("uniqueness"=>true,"allow_null"=>true,"allow_blank"=>true));
   User::validates("level",array("numericality"=>true));
   User::validates("code" ,array("format"=>"^[0-9]{5}$","allow_null"=>true));

   User::hasMany("tickets",array("class_name"=>"Ticket"));
   User::hasOne("account");

   User::beforeSave("before_save");
   User::beforeSave("strip_invalid");
   User::afterSave("after_save");

   User::beforeDestroy("before_destroy");
   User::afterDestroy("after_destroy");

   User::beforeCreate("before_create");
   User::afterCreate("after_create");

   User::beforeUpdate("before_update");
   User::afterUpdate("after_update");

   User::scope("first_level",array("level"=>1));
   User::scope("by_level",function($args) { return "level=".$args[0]; });
   User::scope("by_level_and_date",function($args) { return "level=".$args[0]." and created_at<'".$args[1]." 23:59:59'"; });
   User::scope("doe", "email like '%doe.com'");
   User::scope("email_first", function($args) { return "email like '".$args[0]."%'"; });
?>
