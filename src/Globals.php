<?php
namespace GCWorld\Globals;

use ForceUTF8\Encoding;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
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
    /** @var string */
    protected $_sGlobal = '';

    /** @var bool */
    protected $_bFilter = true;

    /** @var bool */
    protected $_bDefaults = false;

    /** @var int */
    protected $_iFilterType = 0;

    /** @var int */
    protected $_iSpecialFilterType = 0;

    /** @var callable|null */
    protected $_callback = null;

    /**
     * @var bool
     */
    protected $_bUTF8 = true;

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

        /** @var mixed $var */
        $var = ${$this->_sGlobal}[$name];
        $arr = false;   // Have we faked an array?
        if (!is_array($var)) {
            $var = [$var];
            $arr = true;
        }

        $var = $this->executeFiltration($var);
        if ($this->_iSpecialFilterType == self::FILTER_ARRAY) {
            if ($arr) {
                $var = array_pop($var);
                $arr = false;
                if (!is_array($var)) {
                    $var = [$var];
                }
            }
        }

        $this->reset();
        if ($arr) {
            return array_pop($var);
        }

        return $var;
    }

    /**
     * @param array $var
     * @return array
     */
    protected function executeFiltration(array $var): array
    {
        $ignoreFilter = [];

        foreach ($var as $k => $v) {
            if (is_array($v)) {
                $var[$k] = $this->executeFiltration($v);
            }

            switch (true) {
                case $this->_iSpecialFilterType == self::FILTER_OCTAL:
                    $var[$k] = octdec($v);
                    break;
                case $this->_iSpecialFilterType == self::FILTER_TAGS:
                    $var[$k] = trim(strip_tags($v));
                    break;
                case $this->_iSpecialFilterType == self::FILTER_DATE:
                    $var[$k] = trim(strip_tags($v));
                    if (!empty($var[$k])) {
                        if (strtotime($var[$k]) !== false) {
                            $var[$k] = date('Y-m-d', strtotime($var[$k]));
                            break;
                        }
                    }
                    $var[$k] = '0000-00-00';
                    break;
                case $this->_iSpecialFilterType == self::FILTER_DATE_TIME:
                    $var[$k] = trim(strip_tags($v));
                    if (!empty($var[$k])) {
                        if (strtotime($var[$k]) !== false) {
                            $var[$k] = date('Y-m-d H:i:s', strtotime($var[$k]));
                            break;
                        }
                    }
                    $var[$k] = '0000-00-00 00:00:00';
                    break;
                case in_array($this->_iSpecialFilterType, [self::FILTER_JSON_OBJ, self::FILTER_JSON_ARRAY]):
                    $tmp     = json_decode($v, ($this->_iSpecialFilterType == self::FILTER_JSON_ARRAY));
                    $var[$k] = ($tmp === false ? null : $tmp);
                    break;
                case in_array($this->_iSpecialFilterType, [self::FILTER_UUID_STRING, self::FILTER_UUID_BINARY]):
                    $ignoreFilter[] = $k;
                    if(empty($v)) {
                        $var[$k] = '';
                        break;
                    }
                    if($v === Uuid::NIL) {
                        if($this->_iSpecialFilterType === self::FILTER_UUID_STRING) {
                            $var[$k] = $v;
                        } else {
                            $var[$k] = '';
                        }
                        break;
                    }

                    try {
                        $cUuid = Uuid::fromString($v);
                        if($this->_iSpecialFilterType === self::FILTER_UUID_STRING) {
                            $var[$k] = $cUuid->toString();
                            break;
                        } else {
                            $var[$k] = $cUuid->getBytes();
                            break;
                        }
                    } catch (InvalidUuidStringException $e) {
                        // Fail over
                    }

                    $var[$k] = null;
                    break;
                case $this->_iFilterType === FILTER_CALLBACK:
                    $var[$k] = filter_var($v, $this->_iFilterType, $this->_callback);
                    break;
                case $this->_iFilterType > 0:
                    if(!is_scalar($v)) {
                        $var[$k] = null;
                        break;
                    }
                    $var[$k] = filter_var($v, $this->_iFilterType);
                    break;
                case $this->_iSpecialFilterType == self::FILTER_ARRAY:
                default:
                    $var[$k] = $this->_bFilter ? $this->_autoFilter($v) : $v;
                    break;
            }
        }

        if($this->_bUTF8) {
            $var = $this->fixUTF8($var, $ignoreFilter);
        }

        return $var;
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
        if (substr($name, 0, 1) !== '_') {
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
        $this->_bDefaults = (bool)$state;
    }

    /**
     * (DE)ACTIVATE UEF8
     *
     * @param bool $state
     * @return void
     */
    public function utf8(bool $state): void
    {
        $this->_bUTF8 = (bool)$state;
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
        $global = [];
        foreach (${$this->_sGlobal} as $name => $value) {
            $global[$name] = $this->_autoFilter($value);
        }

        return $global;
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
        $this->_iSpecialFilterType = self::FILTER_OCTAL;

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

        return $this;
    }

    /**
     * FILTER_VALIDATE_BOOLEAN
     *
     * @return static
     */
    public function bool(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_BOOLEAN;

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP
     *
     * @return static
     */
    public function ip(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_IP;

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
        $this->_iFilterType = FILTER_FLAG_IPV4;

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
        $this->_iFilterType = FILTER_FLAG_IPV6;

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
     * FILTER_ARRAY
     *
     * @return static
     */
    public function array(): static
    {
        $this->_iSpecialFilterType = self::FILTER_ARRAY;

        return $this;
    }

    /**
     * FILTER_VALIDATE_EMAIL
     *
     * @return static
     */
    public function email(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_EMAIL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function url(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_URL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function mac(): static
    {
        $this->_iFilterType = FILTER_VALIDATE_MAC;

        return $this;
    }

    /**
     * FILTER_TAGS
     *
     * @return static
     */
    public function string(): static
    {
        $this->_iSpecialFilterType = self::FILTER_TAGS;

        return $this;
    }

    /**
     * FILTER_DATE
     *
     * @return static
     */
    public function date(): static
    {
        $this->_iSpecialFilterType = self::FILTER_DATE;

        return $this;
    }

    /**
     * FILTER_DATE_TIME
     *
     * @return static
     */
    public function dateTime(): static
    {
        $this->_iSpecialFilterType = self::FILTER_DATE_TIME;

        return $this;
    }

    /**
     * FILTER_SANITIZE_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringSpecial(): static
    {
        $this->_iFilterType = FILTER_SANITIZE_SPECIAL_CHARS;

        return $this;
    }

    /**
     * FILTER_SANITIZE_FULL_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringFull(): static
    {
        $this->_iFilterType = FILTER_SANITIZE_FULL_SPECIAL_CHARS;

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
        $this->_iSpecialFilterType = $asArray ? self::FILTER_JSON_ARRAY : self::FILTER_JSON_OBJ;

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
        $this->_iSpecialFilterType = $asBytes ? self::FILTER_UUID_BINARY : self::FILTER_UUID_STRING;

        return $this;
    }

    /**
     * FILTER_DEFAULT
     *
     * @return static
     */
    public function noFilter(): static
    {
        $this->_iFilterType = FILTER_DEFAULT;

        return $this;
    }

    /**
     * Used to reset any filter variables
     */
    protected function reset(): void
    {
        $this->_iSpecialFilterType = 0;
        $this->_iFilterType        = 0;
        $this->_callback           = null;
    }

    /**
     * @return mixed
     */
    protected function returnDefault(): mixed
    {
        if (!$this->_bDefaults) {
            return null;
        }

        switch ($this->_iSpecialFilterType) {
            case self::FILTER_OCTAL:
                return 0;
            case self::FILTER_TAGS:
                return '';
            case self::FILTER_DATE:
                return '0000-00-00';
            case self::FILTER_DATE_TIME:
                return '0000-00-00 00:00:00';
            case self::FILTER_ARRAY:
                return [];
            case self::FILTER_JSON_OBJ:
                return new stdClass();
            case self::FILTER_JSON_ARRAY:
                return [];
        }

        switch ($this->_iFilterType) {
            case FILTER_DEFAULT:
                return null; // Could be anything
            case FILTER_VALIDATE_INT:
                return 0;
            case FILTER_VALIDATE_FLOAT:
                return 0.0;
            case FILTER_VALIDATE_BOOLEAN:
                return false;
            case FILTER_VALIDATE_IP:
                return '0.0.0.0';
            case FILTER_FLAG_IPV4:
                return '0.0.0.0';
            case FILTER_FLAG_IPV6:
                return '::/0';
            case FILTER_CALLBACK:
                return null;
            case FILTER_VALIDATE_EMAIL:
                return '';
            case FILTER_VALIDATE_URL:
                return '';
            case FILTER_VALIDATE_MAC:
                return '00-00-00-00-00-00';
            case FILTER_SANITIZE_SPECIAL_CHARS:
                return '';
            case FILTER_SANITIZE_FULL_SPECIAL_CHARS:
                return '';
        }

        return null;
    }

    /**
     * @param mixed $input
     * @param array $ignoreFilter
     * @return mixed
     */
    protected function fixUTF8($input, array $ignoreFilter = [])
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
    protected function loadGlobals()
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
