<?php
/**
 * TORM tests
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */

/**
 * Ticket model
 *
 * PHP version 5.5
 *
 * @category Tests
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
class Ticket extends TORM\Model
{
    /**
     * Return new primary key value
     *
     * @return int value
     */
    public static function getNewPKValue() 
    {
        return time() + mt_rand(1, 10000);
    }
}

Ticket::validates("description", array("presence" => true));
Ticket::belongsTo("user");
Ticket::setOrder("id");
?>
