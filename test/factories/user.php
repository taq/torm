<?php
TORM\Factory::define("user",array(
                     "id"           => time(),
                     "name"         => "Mary Doe",
                     "email"        => "mary@doe.com",
                     "level"        => 1,
                     "code"         => "12345",
                     "created_at"   => null,
                     "updated_at"   => null));

TORM\Factory::define("admin",array(
                     "id"           => time(),
                     "name"         => "Mary Doe",
                     "email"        => "mary@doe.com",
                     "level"        => 1,
                     "code"         => "12345",
                     "created_at"   => null,
                     "updated_at"   => null),
                     array("class_name"=>"User"));
?>
