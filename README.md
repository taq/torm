# What

Just another simple ORM for PHP. You can use it, but don't ask why I made it. :-)

# How

## Includes

Include the `torm.php` to include the other classes needed: `Connection`,
`Model` and `Collection`. 

## Connection

We use PDO to create the connections. Create and send your connection (on this
example, stored on the `$con` var) and set the name of the database driver:

    <?php
        TORM\Connection::setConnection($con);
        TORM\Connection::setDriver("sqlite");
    ?>

The database driver will be used if needed some specific feature of the
database.

You can send the connection enviroment **after** the PDO connection object, and select which environment will be used setting the `TORM_ENV` enviroment
variable.

    <?php
        TORM\Connection::setConnection($con,"test");
    ?>

## Models

Define your models where you want like

    <?php
        class User extends TORM\Model {};
        User::$order = "name";
    ?>

include them and use like

    <?php
        // this will search for user with id 1
        $user = User::find(1);

        // this will create a new user
        $user = new User();
        $user->name  = "John Doe";
        $user->email = "john@doe.com";
        $user->save();
    ?>

### Models attributes

You can set the following attributes:

1. **table_name** - The default behaviour is use the name of the current class
   with a 's' on the end (all right, lacks a lot of pluralization but we're
   starting simple). Change to the table name you want.
2. **order** - Default sort order.
3. **pk** - Primary key column. Defaults to `id`.
4. **ignorecase** - Process all columns as lower case. Defaults to `true`.

# Log

You can enable log messages with:

    <?php
        TORM\Log::enable(true);
    ?>

# Test

Go to the `test` directory and use `PHPUnit` to test it like this:

    $export TORM_ENV=test
    $phpunit torm.php
