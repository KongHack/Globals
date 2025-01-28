<?php
namespace GCWorld\Globals;

use ForceUTF8\Encoding;
use Ramsey\Uuid\Uuid;
use stdClass;

/**
 * Globals
 *
 * @package 	GCWorld\Globals
 * @link		@link https://github.com/KongHack/Globals
 *
 * @author  	GameCharmer <admin@gamecharmer.com> | Anthony Moral <contact@coercive.fr>
 * @copyright   2018 - 2020 GameCharmer | 2016 - 2018 Anthony Moral
 * @license 	MIT
 *
 * @method 		Globals|mixed 	COOKIE($name = null, $value = null)
 * @method 		Globals|mixed 	ENV($name = null, $value = null)
 * @method 		Globals|mixed 	FILES($name = null, $value = null)
 * @method 		Globals|mixed 	GET($name = null, $value = null)
 * @method 		Globals|mixed 	POST($name = null, $value = null)
 * @method 		Globals|mixed 	REQUEST($name = null, $value = null)
 * @method 		Globals|mixed 	SERVER($name = null, $value = null)
 * @method 		Globals|mixed 	SESSION($name = null, $value = null)
 */
class Globals implements GlobalsInterface
{
    protected ?SpecialFilterTypeEnum $FilterSpecialType = null;
    protected ?DataTypeEnum          $FilterDataType    = DataTypeEnum::TYPE_STRING;

    protected int     $ArrayLevels  = 0;
    protected string  $_sGlobal     = '';
    protected bool    $_bFilter     = true;
    protected bool    $_bDefaults   = false;
    protected int     $_iFilterType = 0;
    protected bool    $_bUTF8       = true;

    /** @var callable|null */
    protected $_callback = null;

    /**
     * SET GLOBAL
     *
     * @param string $name
     * @return bool
     */
    protected function _setGlobal(string $name): bool
    {
        // PREPARE
        $name = '_'.strtoupper($name);
        global $$name;

        // VERIFY
        if (!is_array($$name)) {
            return false;
        }

        // SET
        $this->_sGlobal = $name;

        return true;
    }

    /**
     * AUTO FILTER
     *
     * Note: It's probably best to avoid this whenever possible
     *
     * @param mixed $item
     * @return mixed
     */
    protected function _autoFilter(mixed $item): mixed
    {
        // NULL
        if (null === $item) {
            return null;
        }

        // WHAT ELSE
        if (is_object($item) || is_resource($item)) {
            return $item;
        }

        // BOOL
        $tmp = is_scalar($item) ? strtolower($item) : $item;
        if (is_bool($item) || in_array($tmp, ['false', 'true', 'y', 'n'], true)) {
            if (in_array($tmp, ['false', 'n'], true)) {
                $item = false;
            } elseif (in_array($tmp, ['true', 'y'], true)) {
                $item = true;
            }

            return $item;
        }
        unset($tmp);

        // ARRAY
        if (is_array($item)) {
            foreach ($item as $k => $v) {
                $item[$k] = $this->_autoFilter($v);
            }

            return $item;
        }

        // INT
        $int = filter_var($item, FILTER_VALIDATE_INT);
        if ($int !== false) {
            return $int;
        }

        // FLOAT
        if (preg_match('#^([\+\-])?(?!0[0-9]+)[0-9]+\.[0-9]+$#', $item)) {
            return floatval($item);
        }

        // STRING
        return filter_var($item, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * GETTER
     *
     * @param string $name
     * @return null|mixed
     */
    protected function _get(string $name): mixed
    {
        global ${$this->_sGlobal};

        // EXIT
        if (!isset(${$this->_sGlobal}[$name])) {
            $return = $this->returnDefault();
            $this->reset();

            return $return;
        }

        $incoming = ${$this->_sGlobal}[$name];

        if($this->ArrayLevels > 0) {
            $incoming = $this->executeArrayFilter($incoming);
        } elseif(is_array($incoming)) {
            if($this->FilterSpecialType) {
                $incoming = $this->FilterSpecialType->defaultVal();
            } elseif($this->FilterDataType) {
                $incoming = $this->FilterDataType->cast('');
            } else {
                $incoming = null;
            }
        } else {
            $incoming = $this->executeFilter($incoming);
        }

        if($this->FilterDataType && !is_array($incoming)) {
            $incoming = $this->FilterDataType->cast($incoming);
        }

        if($this->_bDefaults && $incoming === null) {
            $incoming = $this->returnDefault();
        }

        $this->reset($name == 'content_ind_logged_in');

        return $incoming;
    }

    /**
     * @param mixed $var
     * @param int $level
     * @return mixed
     */
    protected function executeArrayFilter(mixed $var, int $level = 0): mixed
    {
        if($level > $this->ArrayLevels) {
            return null;
        }

        if(!is_array($var)) {
            return $this->executeFilter($var);
        }

        foreach($var as $k => $v) {
            $var[$k] = $this->executeArrayFilter($v, $level+1);
        }

        return $var;
    }


    /**
     * @param scalar $var
     * @return mixed
     */
    protected function executeFilter(float|bool|int|string $var): mixed
    {
        $ignoreFilter = [];

        // Base filtration up front
        $var = match($this->FilterSpecialType) {
            SpecialFilterTypeEnum::FILTER_OCTAL => octdec($var),
            SpecialFilterTypeEnum::FILTER_TAGS,
            SpecialFilterTypeEnum::FILTER_DATE,
            SpecialFilterTypeEnum::FILTER_DATE_TIME => trim(strip_tags($var)),
            default => $var,
        };

        // More complex filtration
        $var = match($this->FilterSpecialType) {
            SpecialFilterTypeEnum::FILTER_DATE => (function($var) {
                if(!empty($var) && strtotime($var) !== false) {
                    return date('Y-m-d', strtotime($var));
                }
                return '0000-00-00';
            })($var),
            SpecialFilterTypeEnum::FILTER_DATE_TIME => (function($var) {
                if(!empty($var) && strtotime($var) !== false) {
                    return date('Y-m-d H:i:s', strtotime($var));
                }
                return '0000-00-00 00:00:00';
            })($var),
            default => $var,
        };

        // JSON Object vs Array
        if($this->FilterSpecialType?->isJson()) {
            return $this->safeJsonDecode($var, $this->FilterSpecialType == SpecialFilterTypeEnum::FILTER_JSON_OBJ);
        }

        if($this->FilterSpecialType?->isUuid()) {
            try {
                $cUuid = Uuid::fromString($var);
            } catch (\Exception) {
                return $this->FilterSpecialType === SpecialFilterTypeEnum::FILTER_UUID_STRING ? '' : null;
            }

            if(empty($var)) {
                return $this->FilterSpecialType === SpecialFilterTypeEnum::FILTER_UUID_STRING ? '' : null;
            }

            if($this->FilterSpecialType === SpecialFilterTypeEnum::FILTER_UUID_STRING) {
                return $cUuid->toString();
            }

            return $cUuid->getBytes();
        }


        // filter_var Filters
        if($this->_iFilterType === FILTER_CALLBACK) {
            $var = filter_var($var, $this->_iFilterType, $this->_callback);
        } elseif ($this->_iFilterType > 0) {
            if(!is_scalar($var)) {
                $var = null;
            } else {
                $var = filter_var($var, $this->_iFilterType);
            }
        }

        // Last chance filter
        if(!$this->FilterSpecialType && !$this->_iFilterType) {
            $var = $this->_bFilter ? $this->_autoFilter($var) : $var;
        }

        if($this->FilterSpecialType && $this->FilterSpecialType->dataType()) {
            $var = $this->FilterSpecialType->dataType()->cast($var);
        }

        // UTF8 Fix
        if($this->_bUTF8) {
            $var = $this->fixUTF8($var, $ignoreFilter);
        }

        return $var;
    }

    /**
     * @param mixed $json
     * @param bool $obj
     * @return array|stdClass
     */
    protected function safeJsonDecode(mixed $json, bool $obj = false): array|stdClass
    {
        if (empty($json)) {
            return $obj ? new stdClass : [];
        }

        if (\is_array($json)) {
            if(!$obj) {
                return $json;
            }

            return (object) $json;
        }

        if (!\is_string($json)) {
            return $obj ? new stdClass : [];
        }

        $out = \json_decode($json, !$obj);

        if(!json_last_error()) {
            return $out;
        }

        return $obj ? new stdClass : [];
    }

    /**
     * SETTER
     *
     * @param string $name
     * @param mixed  $value
     * @return bool
     */
    protected function _set(string $name, mixed $value): bool
    {
        global ${$this->_sGlobal};

        // SKIP
        if (!is_array(${$this->_sGlobal})) {
            return false;
        }

        // ASSIGN
        ${$this->_sGlobal}[$name] = $value;

        return true;
    }

    /**
     * @param string $name
     * @return array|null
     */
    public function getKeys(string $name): ?array
    {
        if (!str_starts_with($name, '_')) {
            $name = '_'.$name;
        }

        global ${$name};

        // EXIT
        if (!isset(${$name}) || !is_array(${$name})) {
            return null;
        }

        return array_keys(${$name});
    }

    /**
     * PUBLIC AUTO FILTER VAR
     *
     * @param mixed $var
     * @return mixed
     */
    public function autoFilterManualVar(mixed $var): mixed
    {
        return $this->_autoFilter($var);
    }

    /**
     * (DE)ACTIVATE FILTER
     *
     * @param bool $state
     * @return static
     */
    public function filter(bool $state): static
    {
        $this->_bFilter = (bool)$state;

        return $this;
    }

    /**
     * (DE)ACTIVATE DEFAULTS
     *
     * @param bool $state
     * @return void
     */
    public function defaults(bool $state): void
    {
        $this->_bDefaults = $state;
    }

    /**
     * (DE)ACTIVATE UEF8
     *
     * @param bool $state
     * @return void
     */
    public function utf8(bool $state): void
    {
        $this->_bUTF8 = $state;
    }

    /**
     * FILTER ALL
     *
     * @return array
     */
    public function filterAll(): array
    {
        global ${$this->_sGlobal};
        if (!is_array(${$this->_sGlobal})) {
            return [];
        }

        // Processing
        return array_map(function ($value) {
            return $this->_autoFilter($value);
        }, ${$this->_sGlobal});
    }

    /**
     * FILTER NONE
     *
     * @return array
     */
    public function filterNone(): array
    {
        global ${$this->_sGlobal};
        if (!is_array(${$this->_sGlobal})) {
            return [];
        }

        return ${$this->_sGlobal};
    }

    /**
     * AUTO CALL $_[ITEM]
     *
     * @param string $name      Global Name
     * @param array  $arguments [0] Field Name, [1] Set Value [optional]
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        // EXIT
        if (!$name || !$this->_setGlobal($name)) {
            return null;
        }

        // Super cheap hack! GAH!
        if (empty($arguments[0])) {
            return $this;
        }

        // SET
        if (isset($arguments[1])) {
            return $this->_set($arguments[0], $arguments[1]);
        }

        // GET
        return $this->_get($arguments[0]);
    }

    /**
     * FILTER_OCTAL
     *
     * @return static
     */
    public function octal(): static
    {
        $this->FilterSpecialType = SpecialFilterTypeEnum::FILTER_OCTAL;
        $this->FilterDataType    = $this->FilterSpecialType->dataType();

        return $this;
    }

    /**
     * FILTER_VALIDATE_INT
     *
     * @return static
     */
    public function int(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_INT;
        $this->FilterDataType = DataTypeEnum::TYPE_INT;

        return $this;
    }

    /**
     * FILTER_VALIDATE_FLOAT
     *
     * @return static
     */
    public function float(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_FLOAT;
        $this->FilterDataType = DataTypeEnum::TYPE_FLOAT;

        return $this;
    }

    /**
     * FILTER_VALIDATE_BOOLEAN
     *
     * @return static
     */
    public function bool(): static
    {
        $this->_iFilterType   = FILTER_VALIDATE_BOOLEAN;
        $this->FilterDataType = DataTypeEnum::TYPE_BOOL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP
     *
     * @return static
     */
    public function ip(): static
    {
        $this->_iFilterType   = FILTER_VALIDATE_IP;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_FLAG_IPV4
     * CAUTION: Does not fucking work
     *
     * @return static
     */
    public function ipv4(): static
    {
        $this->_iFilterType   = FILTER_FLAG_IPV4;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_FLAG_IPV6
     * CAUTION: Does not fucking work
     *
     * @return static
     */
    public function ipv6(): static
    {
        $this->_iFilterType   = FILTER_FLAG_IPV6;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_CALLBACK
     *
     * @param $callback callable
     *
     * @return static
     */
    public function callback(callable $callback): static
    {
        $this->_iFilterType = FILTER_CALLBACK;
        $this->_callback    = $callback;

        return $this;
    }

    /**
     * @param int $levels
     * @return $this
     */
    public function array(int $levels = 1): static
    {
        $this->ArrayLevels = $levels;

        return $this;
    }

    /**
     * FILTER_VALIDATE_EMAIL
     *
     * @return static
     */
    public function email(): static
    {
        $this->_iFilterType   = FILTER_VALIDATE_EMAIL;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function url(): static
    {
        $this->_iFilterType   = FILTER_VALIDATE_URL;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function mac(): static
    {
        $this->_iFilterType   = FILTER_VALIDATE_MAC;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_TAGS
     *
     * @return static
     */
    public function string(): static
    {
        $this->FilterSpecialType = SpecialFilterTypeEnum::FILTER_TAGS;
        $this->FilterDataType    =  $this->FilterSpecialType->dataType();

        return $this;
    }

    /**
     * FILTER_DATE
     *
     * @return static
     */
    public function date(): static
    {
        $this->FilterSpecialType = SpecialFilterTypeEnum::FILTER_DATE;
        $this->FilterDataType    =  $this->FilterSpecialType->dataType();

        return $this;
    }

    /**
     * FILTER_DATE_TIME
     *
     * @return static
     */
    public function dateTime(): static
    {
        $this->FilterSpecialType = SpecialFilterTypeEnum::FILTER_DATE_TIME;
        $this->FilterDataType    =  $this->FilterSpecialType->dataType();

        return $this;
    }

    /**
     * FILTER_SANITIZE_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringSpecial(): static
    {
        $this->_iFilterType   = FILTER_SANITIZE_SPECIAL_CHARS;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_SANITIZE_FULL_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringFull(): static
    {
        $this->_iFilterType   = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
        $this->FilterDataType = DataTypeEnum::TYPE_STRING;

        return $this;
    }

    /**
     * FILTER_JSON_ARRAY | FILTER_JSON_OBJ
     *
     * @param bool $asArray
     * @return static
     */
    public function json(bool $asArray): static
    {
        $this->FilterSpecialType = $asArray ? SpecialFilterTypeEnum::FILTER_JSON_ARRAY : SpecialFilterTypeEnum::FILTER_JSON_OBJ;

        return $this;
    }

    /**
     * FILTER_UUID_BINARY | FILTER_UUID_STRING
     *
     * @param bool $asBytes
     *
     * @return static
     */
    public function uuid(bool $asBytes = false): static
    {
        $this->FilterSpecialType = $asBytes ? SpecialFilterTypeEnum::FILTER_UUID_BINARY : SpecialFilterTypeEnum::FILTER_UUID_STRING;

        return $this;
    }

    /**
     * FILTER_DEFAULT
     *
     * @return static
     */
    public function noFilter(): static
    {
        $this->_iFilterType      = FILTER_DEFAULT;
        $this->FilterSpecialType = null;
        $this->FilterDataType    = null;

        return $this;
    }

    /**
     * Used to reset any filter variables
     */
    protected function reset(): void
    {
        $this->FilterSpecialType = null;
        $this->FilterDataType    = null;
        $this->ArrayLevels       = 0;
        $this->_iFilterType      = 0;
        $this->_callback         = null;
    }

    /**
     * @return mixed
     */
    protected function returnDefault(): mixed
    {
        if (!$this->_bDefaults) {
            return null;
        }

        if($this->ArrayLevels > 0) {
            return [];
        }

        if($this->FilterSpecialType) {
            return $this->FilterSpecialType->defaultVal();
        }

        switch ($this->_iFilterType) {
            case FILTER_CALLBACK:
            case FILTER_DEFAULT:
                return null; // Could be anything
            case FILTER_VALIDATE_INT:
                return 0;
            case FILTER_VALIDATE_FLOAT:
                return 0.0;
            case FILTER_VALIDATE_BOOLEAN:
                return false;
            case FILTER_FLAG_IPV4:
            case FILTER_VALIDATE_IP:
                return '0.0.0.0';
            case FILTER_FLAG_IPV6:
                return '::/0';
            case FILTER_VALIDATE_URL:
            case FILTER_SANITIZE_SPECIAL_CHARS:
            case FILTER_SANITIZE_FULL_SPECIAL_CHARS:
            case FILTER_VALIDATE_EMAIL:
                return '';
            case FILTER_VALIDATE_MAC:
                return '00-00-00-00-00-00';
        }

        return null;
    }

    /**
     * @param mixed $input
     * @param array $ignoreFilter
     * @return mixed
     */
    protected function fixUTF8(mixed $input, array $ignoreFilter = [])
    {
        if(is_numeric($input)) {
            return $input;
        }

        if(is_array($input)) {
            foreach($input as $k => $v) {
                if(in_array($k, $ignoreFilter)) {
                    continue;
                }
                $input[$k] = $this->fixUTF8($v);
            }

            return $input;
        }

        if(is_string($input)) {
            return \mb_convert_encoding($input, 'UTF-8');
        }

        return $input;
    }

    /**
     * Never call this.  It's necessary in some PHP instances where
     * the super globals are not loaded at all unless they are actually seen
     * in code.  I have no idea why, please enlighten me oh PHP gurus
     */
    protected function loadGlobals(): void
    {
        print_r($_GET);
        print_r($_POST);
        print_r($_REQUEST);
        print_r($_SERVER);
        print_r($_FILES);
        print_r($_ENV);
        print_r($_COOKIE);
    }
}
