<?php
namespace GCWorld\Globals;

use Exception;
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
 * @copyright   2018 GameCharmer | 2016 - 2018 Anthony Moral
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
class Globals
{

    const FILTER_OCTAL       = 1;
    const FILTER_TAGS        = 2;
    const FILTER_DATE        = 3;
    const FILTER_DATE_TIME   = 4;
    const FILTER_ARRAY       = 5;
    const FILTER_JSON_OBJ    = 6;
    const FILTER_JSON_ARRAY  = 7;
    const FILTER_UUID_STRING = 8;
    const FILTER_UUID_BINARY = 9;

    /** @var string */
    private $_sGlobal = '';

    /** @var bool */
    private $_bFilter = true;

    /** @var bool */
    private $_bDefaults = false;

    /** @var int */
    private $_iFilterType = 0;

    /** @var int */
    private $_iSpecialFilterType = 0;

    /** @var callable|null */
    private $_callback = null;

    /**
     * @var bool
     */
    private $_bUTF8 = true;

    /**
     * SET GLOBAL
     *
     * @param string $name
     * @return bool
     */
    private function _setGlobal(string $name): bool
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
    private function _autoFilter($item)
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
        if (is_bool($item) || in_array($tmp, ['false', 'true', 'y', 'n', 'Y', 'N'], true)) {
            if (in_array($tmp, ['false', 'n', 'N'], true)) {
                $item = false;
            } elseif (in_array($tmp, ['true', 'y', 'Y'], true)) {
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
    private function _get(string $name)
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
    private function executeFiltration(array $var)
    {
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
                case in_array($this->_iSpecialFilterType, [self::FILTER_UUID_STRING,self::FILTER_UUID_BINARY]):
                    if(empty($v)) {
                        $var[$k] = '';
                        break;
                    }
                    if($v == '00000000-0000-0000-0000-000000000000') {
                        if($this->_iSpecialFilterType == self::FILTER_UUID_STRING) {
                            $var[$k] = '00000000-0000-0000-0000-000000000000';
                        } else {
                            $var[$k] = '';
                        }
                        break;
                    }

                    try {
                        $cUuid = Uuid::fromString($v);
                        if($this->_iSpecialFilterType == self::FILTER_UUID_STRING) {
                            $var[$k] = $cUuid->toString();
                            break;
                        } else {
                            $var[$k] = $cUuid->getBytes();
                            break;
                        }
                    } catch (Exception $e) {
                        // Fail over
                    }

                    try {
                        $cUuid = Uuid::fromBytes($v);
                        if($this->_iSpecialFilterType == self::FILTER_UUID_STRING) {
                            $var[$k] = $cUuid->toString();
                            break;
                        } else {
                            $var[$k] = $cUuid->getBytes();
                            break;
                        }
                    } catch (Exception $e) {
                        // Fail over
                    }

                    $var[$k] = '';
                break;
                case $this->_iFilterType === FILTER_CALLBACK:
                    $var[$k] = filter_var($v, $this->_iFilterType, $this->_callback);
                break;
                case $this->_iFilterType > 0:
                    $var[$k] = filter_var($v, $this->_iFilterType);
                break;
                case $this->_iSpecialFilterType == self::FILTER_ARRAY:
                default:
                    $var[$k] = $this->_bFilter ? $this->_autoFilter($v) : $v;
                break;
            }
        }

        if($this->_bUTF8) {
            $var = $this->fixUTF8($var);
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
    private function _set(string $name, $value): bool
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
    public function getKeys(string $name)
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
    public function autoFilterManualVar($var)
    {
        return $this->_autoFilter($var);
    }

    /**
     * (DE)ACTIVATE FILTER
     *
     * @param bool $state
     * @return Globals
     */
    public function filter(bool $state): Globals
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
    public function defaults(bool $state)
    {
        $this->_bDefaults = (bool)$state;
    }

    /**
     * (DE)ACTIVATE UEF8
     *
     * @param bool $state
     * @return void
     */
    public function utf8(bool $state)
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
    public function __call(string $name, array $arguments = [])
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
     * @return Globals
     */
    public function octal(): Globals
    {
        $this->_iSpecialFilterType = self::FILTER_OCTAL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_INT
     *
     * @return Globals
     */
    public function int(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_INT;

        return $this;
    }

    /**
     * FILTER_VALIDATE_FLOAT
     *
     * @return Globals
     */
    public function float(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_FLOAT;

        return $this;
    }

    /**
     * FILTER_VALIDATE_BOOLEAN
     *
     * @return Globals
     */
    public function bool(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_BOOLEAN;

        return $this;
    }

    /**
     * FILTER_VALIDATE_IP
     *
     * @return Globals
     */
    public function ip(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_IP;

        return $this;
    }

    /**
     * FILTER_FLAG_IPV4
     * CAUTION: Does not fucking work
     *
     * @return Globals
     */
    public function ipv4(): Globals
    {
        $this->_iFilterType = FILTER_FLAG_IPV4;

        return $this;
    }

    /**
     * FILTER_FLAG_IPV6
     * CAUTION: Does not fucking work
     *
     * @return Globals
     */
    public function ipv6(): Globals
    {
        $this->_iFilterType = FILTER_FLAG_IPV6;

        return $this;
    }

    /**
     * FILTER_CALLBACK
     *
     * @param $callback callable
     *
     * @return Globals
     */
    public function callback(callable $callback): Globals
    {
        $this->_iFilterType = FILTER_CALLBACK;
        $this->_callback    = $callback;

        return $this;
    }

    /**
     * FILTER_ARRAY
     *
     * @return Globals
     */
    public function array(): Globals
    {
        $this->_iSpecialFilterType = self::FILTER_ARRAY;

        return $this;
    }

    /**
     * FILTER_VALIDATE_EMAIL
     *
     * @return Globals
     */
    public function email(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_EMAIL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return Globals
     */
    public function url(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_URL;

        return $this;
    }

    /**
     * FILTER_VALIDATE_URL
     *
     * @return Globals
     */
    public function mac(): Globals
    {
        $this->_iFilterType = FILTER_VALIDATE_MAC;

        return $this;
    }

    /**
     * FILTER_TAGS
     *
     * @return Globals
     */
    public function string(): Globals
    {
        $this->_iSpecialFilterType = self::FILTER_TAGS;

        return $this;
    }

    /**
     * FILTER_DATE
     *
     * @return Globals
     */
    public function date(): Globals
    {
        $this->_iSpecialFilterType = self::FILTER_DATE;

        return $this;
    }

    /**
     * FILTER_DATE_TIME
     *
     * @return Globals
     */
    public function dateTime(): Globals
    {
        $this->_iSpecialFilterType = self::FILTER_DATE_TIME;

        return $this;
    }

    /**
     * FILTER_SANITIZE_SPECIAL_CHARS
     *
     * @return Globals
     */
    public function stringSpecial(): Globals
    {
        $this->_iFilterType = FILTER_SANITIZE_SPECIAL_CHARS;

        return $this;
    }

    /**
     * FILTER_SANITIZE_FULL_SPECIAL_CHARS
     *
     * @return Globals
     */
    public function stringFull(): Globals
    {
        $this->_iFilterType = FILTER_SANITIZE_FULL_SPECIAL_CHARS;

        return $this;
    }

    /**
     * FILTER_JSON_ARRAY | FILTER_JSON_OBJ
     *
     * @param bool $asArray
     * @return $this
     */
    public function json(bool $asArray)
    {
        $this->_iSpecialFilterType = $asArray ? self::FILTER_JSON_ARRAY : self::FILTER_JSON_OBJ;

        return $this;
    }

    /**
     * FILTER_UUID_BINARY | FILTER_UUID_STRING
     *
     * @param bool $asBytes
     *
     * @return $this
     */
    public function uuid(bool $asBytes = false)
    {
        $this->_iSpecialFilterType = $asBytes ? self::FILTER_UUID_BINARY : self::FILTER_UUID_STRING;

        return $this;
    }

    /**
     * FILTER_DEFAULT
     *
     * @return Globals
     */
    public function noFilter(): Globals
    {
        $this->_iFilterType = FILTER_DEFAULT;

        return $this;
    }

    /**
     * Used to reset any filter variables
     */
    protected function reset()
    {
        $this->_iSpecialFilterType = 0;
        $this->_iFilterType        = 0;
        $this->_callback           = null;
    }

    /**
     * @return mixed
     */
    protected function returnDefault()
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
     * @return mixed
     */
    protected function fixUTF8($input)
    {
        if(is_array($input)) {
            foreach($input as $k => $v) {
                $input[$k] = $this->fixUTF8($v);
            }
        }
        if(is_string($input)) {
            $input = Encoding::fixUTF8($input, Encoding::ICONV_IGNORE);
        }

        return $input;
    }
}
