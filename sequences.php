<?php
/**
 * Sequences operations
 *
 * PHP version 5.5
 *
 * @category Traits
 * @package  TORM
 * @author   Eustáquio Rangel <taq@bluefish.com.br>
 * @license  http://www.gnu.org/copyleft/gpl.html GPL
 * @link     http://github.com/taq/torm
 */
namespace TORM;

trait Sequences
{
    private static $_sequence        = array();
    private static $_sequence_exists = array();

    /**
     * Set the sequence name, if any
     *
     * @param string $name of the sequence
     *
     * @return null
     */
    public static function setSequenceName($name)
    {
        $cls = get_called_class();
        self::$_sequence[$cls] = $name;
    }

    /**
     * Returns the sequence name, if any
     *
     * @return name of the sequence
     */
    public static function getSequenceName()
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, self::$_sequence)) {
            return null;
        }
        return self::$_sequence[$cls];
    }

    /**
     * Resolve the sequence name, if any
     *
     * @return name of the sequence
     */
    public static function resolveSequenceName()
    {
        if (Driver::$primary_key_behaviour!=Driver::PRIMARY_KEY_SEQUENCE) {
            return null;
        }

        $suffix  = "_sequence";
        $table   = strtolower(self::getTableName());
        $name    = self::getSequenceName();

        if ($name) {
            return $name;
        }
        return $table.$suffix;
    }

    /**
     * Check if a sequence exists
     *
     * @return exists or not
     */
    private static function _sequenceExists()
    {
        if (Driver::$primary_key_behaviour != Driver::PRIMARY_KEY_SEQUENCE) {
            return null;
        }

        // caching if the sequence exists
        $cls  = get_called_class();
        $name = self::resolveSequenceName();

        if (array_key_exists($cls, self::$_sequence_exists)
            && array_key_exists($name, self::$_sequence_exists[$cls])
        ) {
            return true;
        }

        // checking if the sequence exists
        $escape = Driver::$escape_char;
        $sql    = "select count(sequence_name) as $escape"."CNT"."$escape from user_sequences where sequence_name='$name' or sequence_name='".strtolower($name)."' or sequence_name='".strtoupper($name)."'";
        $stmt   = self::resolveConnection()->query($sql);
        $rst    = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rtn    = intval($rst["CNT"])>0;
        self::_closeCursor($stmt);

        // if exists, cache result
        if ($rtn) {
            if (!array_key_exists($cls, self::$_sequence_exists)) {
                self::$_sequence_exists[$cls] = array();
            }
            self::$_sequence_exists[$cls][$name] = true;
        }
        return $rtn;
    }

    /**
     * Create a sequence if not exists
     *
     * @return null
     */
    private static function _checkSequence()
    {
        if (Driver::$primary_key_behaviour!=Driver::PRIMARY_KEY_SEQUENCE) {
            return null;
        }

        if (self::_sequenceExists()) {
            return;
        }

        // create sequence
        $name = self::resolveSequenceName();
        $sql  = "create sequence $name increment by 1 start with 1 nocycle nocache";
        Log::log($sql);
        $stmt = self::resolveConnection()->query($sql);
        self::_closeCursor($stmt);
    }
}
?>
