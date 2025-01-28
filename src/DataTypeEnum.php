<?php
namespace GCWorld\Globals;

/**
 * DataTypeEnum Enumeration
 */
enum DataTypeEnum: int
{
    case TYPE_STRING   = 1;
    case TYPE_DATE     = 2;
    case TYPE_DATETIME = 3;
    case TYPE_INT      = 4;
    case TYPE_FLOAT    = 5;
    case TYPE_BOOL     = 6;

    /**
     * @param mixed $incoming
     * @return mixed
     */
    function cast(mixed $incoming): mixed
    {
        return match($this) {
            self::TYPE_STRING,
            self::TYPE_DATE,
            self::TYPE_DATETIME => (string) $incoming,
            self::TYPE_INT      => (int)    $incoming,
            self::TYPE_FLOAT    => (float)  $incoming,
            self::TYPE_BOOL     => (bool)   $incoming,
        };
    }
}
