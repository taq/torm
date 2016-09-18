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
        if (Driver::$primary_key_behaviour != Driver::PRIMARY_KEY_SEQUENCE) {
            return null;
        }

        $name = self::getSequenceName();
        if ($name) {
            return $name;
        }
        
        $table = strtolower(self::getTableName());
        $pk    = self::getPK();

        // need to deal with specific databases here. can't open the Driver
        // class and rewrite the method like we do in Ruby
        switch (Driver::$name) {
        case "oracle":
            $suffix  = "_sequence";
            return $table.$suffix;
        case "postgresql":
            return "{$table}_{$pk}_seq";
        }
        return null;
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

        $rtn = false;

        switch (Driver::$name) {
        case "oracle":
            $rtn = self::_oracleSequenceExists($name);
            break;
        case "postgresql":
            $rtn = self::_postgresqlSequenceExists($name);
            break;
        }

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
     * Check if an Oracle sequence exists
     *
     * @param string $name sequence name
     *
     * @return exists or not
     */
    private function _oracleSequenceExists($name)
    {
        $escape = Driver::$escape_char;
        $sql    = "select count(sequence_name) as $escape"."CNT"."$escape from user_sequences where sequence_name='$name' or sequence_name='".strtolower($name)."' or sequence_name='".strtoupper($name)."'";
        $stmt   = self::resolveConnection()->query($sql);
        $rst    = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rtn    = intval($rst["CNT"]) > 0;
        self::closeCursor($stmt);
        return $rtn;
    }

    /**
     * Check if an PostgreSQL sequence exists
     *
     * @param string $name sequence name
     *
     * @return exists or not
     */
    private function _postgresqlSequenceExists($name)
    {
        $escape = Driver::$escape_char;
        $sql    = "select count(*) as {$escape}CNT{$escape} from information_schema.sequences where sequence_name = '$name'";
        $stmt   = self::resolveConnection()->query($sql);
        $rst    = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rtn    = intval($rst["CNT"]) > 0;
        self::closeCursor($stmt);
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

        switch (Driver::$name) {
        case "oracle":
            self::_createOracleSequence();
            break;
        case "postgresql":
            self::_createPostgresqlSequence();
            break;
        }
    }

    /**
     * Create an Oracle sequence
     *
     * @return null
     */
    private static function _createOracleSequence()
    {
        $name = self::resolveSequenceName();
        $sql  = "create sequence $name increment by 1 start with 1 nocycle nocache";
        Log::log($sql);
        $stmt = self::resolveConnection()->query($sql);
        self::closeCursor($stmt);
    }

    /**
     * Create a PostgreSQL sequence
     *
     * @return null
     */
    private static function _createPostgresqlSequence()
    {
        $name = self::resolveSequenceName();
        $sql  = "create sequence $name increment by 1 start with 1 no cycle";
        Log::log($sql);
        $stmt = self::resolveConnection()->query($sql);
        self::closeCursor($stmt);
    }

    /**
     * Get the next value from a sequence
     *
     * @param string $name sequence name
     *
     * @return mixed next value
     */
    public static function sequenceNextVal($name)
    {
        $sql = null;

        switch (Driver::$name) {
        case "oracle":
            $sql = "select $name.nextval from dual";
            break;
        case "postgresql":
            $sql = "select nextval('$name') as nextval";
            break;
        }

        if ($sql == null) {
            return null;
        }

        $stmt = self::executePrepared($sql);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rtn  = null;

        if (!$data) {
            return null;
        }

        $seq_keys = array("nextval", "NEXTVAL");
        foreach ($seq_keys as $seq_key) {
            if (array_key_exists($seq_key, $data)) {
                $rtn = $data[$seq_key];
                break;
            }
        }
        return $rtn;
    }
}
?>
