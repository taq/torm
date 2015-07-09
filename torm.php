<?php
/**
 * When not using Composer, including this file includes all other ones
 *
 * PHP version 5.5
 *
 * @category Traits
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */

// traits
require_once "src/Finders.php";
require_once "src/Storage.php";
require_once "src/Persistence.php";
require_once "src/Errors.php";
require_once "src/Validations.php";
require_once "src/Scopes.php";
require_once "src/HasMany.php";
require_once "src/HasOne.php";
require_once "src/BelongsTo.php";
require_once "src/Sequences.php";
require_once "src/Cache.php";
require_once "src/Callbacks.php";
require_once "src/Dirty.php";

// classes
require_once "src/Connection.php";
require_once "src/Builder.php";
require_once "src/Model.php";
require_once "src/Collection.php";
require_once "src/Factory.php";
require_once "src/Driver.php";
require_once "src/Validation.php";
require_once "src/Log.php";
require_once "src/Inflections.php";
?>
