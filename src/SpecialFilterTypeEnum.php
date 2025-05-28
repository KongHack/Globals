<?php
namespace GCWorld\Globals;

/**
 * SpecialFilterTypeEnum Enumeration
 */
enum SpecialFilterTypeEnum: int
{
    case FILTER_OCTAL       = 1;
    case FILTER_TAGS        = 2;
    case FILTER_DATE        = 3;
    case FILTER_DATE_TIME   = 4;
    case FILTER_JSON_OBJ    = 6;
    case FILTER_JSON_ARRAY  = 7;
    case FILTER_UUID_STRING = 8;
    case FILTER_UUID_BINARY = 9;
    case FILTER_BASE64      = 10;

    /**
     * @return DataTypeEnum|null
     */
    public function dataType(): ?DataTypeEnum
    {
        return match($this) {
            self::FILTER_OCTAL,
            self::FILTER_TAGS,
            self::FILTER_DATE,
            self::FILTER_BASE64,
            self::FILTER_DATE_TIME => DataTypeEnum::TYPE_STRING,
            default                => null,
        };
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        return match($this) {
            self::FILTER_JSON_OBJ,
            self::FILTER_JSON_ARRAY => true,
            default                 => false,
        };
    }

    /**
     * @return bool
     */
    public function isUuid(): bool
    {
        return match($this) {
            self::FILTER_UUID_STRING,
            self::FILTER_UUID_BINARY => true,
            default                  => false,
        };
    }

    /**
     * @return mixed
     */
    public function defaultVal(): mixed
    {
        return match($this) {
            self::FILTER_OCTAL      => 0,
            self::FILTER_TAGS       => '',
            self::FILTER_DATE       => '0000-00-00',
            self::FILTER_DATE_TIME  => '0000-00-00 00:00:00',
            self::FILTER_JSON_OBJ   => new \stdClass(),
            self::FILTER_JSON_ARRAY => [],
            default                 => null,
        };
    }

}