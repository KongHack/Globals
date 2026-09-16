<?php
namespace GCWorld\Globals;

use Ramsey\Uuid\Uuid;
use stdClass;

/**
 * Globals
 *
 * @package 	GCWorld\Globals
 * @link		@link https://github.com/KongHack/Globals
 *
 * @author  	GameCharmer <admin@gamecharmer.com> | Anthony Moral <contact@coercive.fr>
 * @copyright   2018 - 2026 GameCharmer | 2016 - 2018 Anthony Moral
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
    protected ?DataTypeEnum          $FilterDataType    = null;

    protected int     $ArrayLevels  = 0;
    protected string  $_sGlobal     = '';
    protected bool    $_bFilter     = true;
    protected bool    $_bDefaults   = false;
    protected int     $_iFilterType  = 0;
    protected int     $_iFilterFlags = 0;
    protected bool    $_bUTF8        = true;

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
     * @param string|int $name
     * @return null|mixed
     */
    protected function _get(string|int $name): mixed
    {
        global ${$this->_sGlobal};

        try {
            // EXIT
            if (!isset(${$this->_sGlobal}[$name])) {
                return $this->returnDefault();
            }

            $incoming = ${$this->_sGlobal}[$name];

            if($this->ArrayLevels > 0) {
                $incoming = $this->executeArrayFilter($incoming);
                if(!is_array($incoming)) {
                    $incoming = [];
                }
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

            return $incoming;
        } finally {
            $this->reset();
        }
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
            SpecialFilterTypeEnum::FILTER_OCTAL     => octdec($var),
            SpecialFilterTypeEnum::FILTER_TAGS,
            SpecialFilterTypeEnum::FILTER_STRING_STRICT,
            SpecialFilterTypeEnum::FILTER_DATE,
            SpecialFilterTypeEnum::FILTER_DATE_TIME => trim(strip_tags($var)),
            SpecialFilterTypeEnum::FILTER_BASE64    => trim($var),
            default                                 => $var,
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
            SpecialFilterTypeEnum::FILTER_BASE64 => (function($var) {
                $decoded = \base64_decode($var, true);

                return $decoded === false ? null : $decoded;
            })($var),
            SpecialFilterTypeEnum::FILTER_STRING_STRICT => (function($var) {
                $var = \preg_replace("/[^[:alnum:] '\\-]/", '', $var);
                $var = \preg_replace('/ +/', ' ', $var);
                return \trim($var);
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
            $var = filter_var($var, $this->_iFilterType, ['options' => $this->_callback]);
        } elseif ($this->_iFilterType > 0) {
            if(!is_scalar($var)) {
                $var = null;
            } else {
                $var = filter_var($var, $this->_iFilterType, $this->_iFilterFlags);
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

        if(json_last_error() !== JSON_ERROR_NONE) {
            return $obj ? new stdClass : [];
        }

        if($obj) {
            return $out instanceof stdClass ? $out : new stdClass;
        }

        return \is_array($out) ? $out : [];
    }

    /**
     * SETTER
     *
     * @param string|int $name
     * @param mixed  $value
     * @return bool
     */
    protected function _set(string|int $name, mixed $value): bool
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

        // Select the global for batch access when no key was supplied.
        if (!array_key_exists(0, $arguments)) {
            return $this;
        }

        // SET
        if (array_key_exists(1, $arguments)) {
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
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_OCTAL);

        return $this;
    }

    /**
     * FILTER_VALIDATE_INT
     *
     * @return static
     */
    public function int(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_INT, DataTypeEnum::TYPE_INT);

        return $this;
    }

    /**
     * FILTER_VALIDATE_FLOAT
     *
     * @return static
     */
    public function float(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_FLOAT, DataTypeEnum::TYPE_FLOAT);

        return $this;
    }

    /**
     * FILTER_VALIDATE_BOOLEAN
     *
     * @return static
     */
    public function bool(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_BOOLEAN, DataTypeEnum::TYPE_BOOL);

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP
     *
     * @return static
     */
    public function ip(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_IP, DataTypeEnum::TYPE_STRING);

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP with FILTER_FLAG_IPV4
     *
     * @return static
     */
    public function ipv4(): static
    {
        $this->selectStandardFilter(
            FILTER_VALIDATE_IP,
            DataTypeEnum::TYPE_STRING,
            FILTER_FLAG_IPV4,
        );

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP with FILTER_FLAG_IPV6
     *
     * @return static
     */
    public function ipv6(): static
    {
        $this->selectStandardFilter(
            FILTER_VALIDATE_IP,
            DataTypeEnum::TYPE_STRING,
            FILTER_FLAG_IPV6,
        );

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
        $this->selectStandardFilter(FILTER_CALLBACK, null, callback: $callback);

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
        $this->selectStandardFilter(FILTER_VALIDATE_EMAIL, DataTypeEnum::TYPE_STRING);

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function url(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_URL, DataTypeEnum::TYPE_STRING);

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function mac(): static
    {
        $this->selectStandardFilter(FILTER_VALIDATE_MAC, DataTypeEnum::TYPE_STRING);

        return $this;
    }

    /**
     * FILTER_TAGS
     *
     * @return static
     */
    public function string(): static
    {
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_TAGS);

        return $this;
    }

    /**
     * Strip non-"name safe" characters
     *
     * @return static
     */
    public function stringStrict(): static
    {
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_STRING_STRICT);

        return $this;
    }

    /**
     * FILTER_TAGS
     *
     * @return static
     */
    public function base64(): static
    {
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_BASE64);

        return $this;
    }

    /**
     * FILTER_DATE
     *
     * @return static
     */
    public function date(): static
    {
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_DATE);

        return $this;
    }

    /**
     * FILTER_DATE_TIME
     *
     * @return static
     */
    public function dateTime(): static
    {
        $this->selectSpecialFilter(SpecialFilterTypeEnum::FILTER_DATE_TIME);

        return $this;
    }

    /**
     * FILTER_SANITIZE_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringSpecial(): static
    {
        $this->selectStandardFilter(FILTER_SANITIZE_SPECIAL_CHARS, DataTypeEnum::TYPE_STRING);

        return $this;
    }

    /**
     * FILTER_SANITIZE_FULL_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringFull(): static
    {
        $this->selectStandardFilter(FILTER_SANITIZE_FULL_SPECIAL_CHARS, DataTypeEnum::TYPE_STRING);

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
        $this->selectSpecialFilter(
            $asArray ? SpecialFilterTypeEnum::FILTER_JSON_ARRAY : SpecialFilterTypeEnum::FILTER_JSON_OBJ,
        );

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
        $this->selectSpecialFilter(
            $asBytes ? SpecialFilterTypeEnum::FILTER_UUID_BINARY : SpecialFilterTypeEnum::FILTER_UUID_STRING,
        );

        return $this;
    }

    /**
     * FILTER_DEFAULT
     *
     * @return static
     */
    public function noFilter(): static
    {
        $this->selectStandardFilter(FILTER_DEFAULT, null);

        return $this;
    }

    /**
     * Configure a filter_var filter and clear incompatible special-filter state.
     */
    protected function selectStandardFilter(
        int $filterType,
        ?DataTypeEnum $dataType,
        int $flags = 0,
        ?callable $callback = null,
    ): void
    {
        $this->FilterSpecialType = null;
        $this->FilterDataType    = $dataType;
        $this->_iFilterType      = $filterType;
        $this->_iFilterFlags     = $flags;
        $this->_callback         = $callback;
    }

    /**
     * Configure a special filter and clear incompatible filter_var state.
     */
    protected function selectSpecialFilter(SpecialFilterTypeEnum $filterType): void
    {
        $this->FilterSpecialType = $filterType;
        $this->FilterDataType    = $filterType->dataType();
        $this->_iFilterType      = 0;
        $this->_iFilterFlags     = 0;
        $this->_callback         = null;
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
        $this->_iFilterFlags     = 0;
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
            case FILTER_VALIDATE_IP:
                if($this->_iFilterFlags === FILTER_FLAG_IPV6) {
                    return '::/0';
                }
                return '0.0.0.0';
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
