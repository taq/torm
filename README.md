# TORM

Just another simple ORM for PHP. You can use it, but don't ask why I made it. Right? :-)

## Usage

Take a look on the [Github Wiki](https://github.com/taq/torm/wiki) for documentation, but let me show the basics here:

```php
<?php
// open the PDO connection and set it
$con = new PDO("sqlite:database.sqlite3");
TORM\Connection::setConnection($con);
TORM\Connection::setDriver("sqlite");

// define your models - an User class will connect to an users table
class User extends TORM\Model {};
class Ticket extends TORM\Model {};

// create some validations
User::validates("name",  ["presence"     => true]);
User::validates("email", ["presence"     => true]);
User::validates("email", ["uniqueness"   => true]);
User::validates("id",    ["numericality" => true]);

// create some relations
User::hasMany("tickets");
User::hasOne("account");
Ticket::belongsTo("user");

// this will create a new user
$user = new User();
$user->name  = "John Doe";
$user->email = "john@doe.com";
$user->level = 1;
$user->save();

// this will find the user using its primary key
$user = User::find(1);

// find some users
$users = User::where(["level" => 1]);

// find some users, using more complex expressions
// the array first element is the query, the rest are values
$users = User::where(["level >= ?", 1]); 

// updating users
User::where(["level" => 1])->updateAttributes(["level" => 3]);

// using fluent queries
$users = User::where(["level" => 1])->limit(5)->order("name desc");

// listing the user tickets
foreach($user->tickets as $ticket) {
   echo $ticket->description;
}

// show user account info
echo $user->account->number; 
?>
```

## Testing

### SQLite

First, use `composer update` to make sure everything is ok with all the
packages. Then, go to the `test` directory and run `run`. It will requires the
[SQLite driver](http://php.net/manual/en/ref.pdo-sqlite.php) so make sure it is
available. If not, check the `php.ini` dir found with

```bash
$ php -r 'phpinfo();' | grep 'php.ini'
Configuration File (php.ini) Path => /etc/php/7.1/cli
Loaded Configuration File => /etc/php/7.1/cli/php.ini
```

and, if not found there or on the `conf.d` on the same location the `php.ini`
file is, it can be installed, on Ubuntu, using:

```bash
$ sudo apt install php-sqlite3
```

### Multibyte strings, locale and YAML

```
$ sudo apt install php-mbstring php-intl php-yaml
```
