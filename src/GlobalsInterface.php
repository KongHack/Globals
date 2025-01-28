<?php
namespace GCWorld\Globals;

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
interface GlobalsInterface
{
    /**
     * PUBLIC AUTO FILTER VAR
     *
     * @param mixed $var
     * @return mixed
     */
    public function autoFilterManualVar(mixed $var): mixed;

    /**
     * (DE)ACTIVATE FILTER
     *
     * @param bool $state
     * @return static
     */
    public function filter(bool $state): static;

    /**
     * (DE)ACTIVATE DEFAULTS
     *
     * @param bool $state
     * @return void
     */
    public function defaults(bool $state): void;

    /**
     * (DE)ACTIVATE UEF8
     *
     * @param bool $state
     * @return void
     */
    public function utf8(bool $state): void;

    /**
     * FILTER ALL
     *
     * @return array
     */
    public function filterAll(): array;

    /**
     * FILTER NONE
     *
     * @return array
     */
    public function filterNone(): array;

    /**
     * AUTO CALL $_[ITEM]
     *
     * @param string $name      Global Name
     * @param array  $arguments [0] Field Name, [1] Set Value [optional]
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed;

    /**
     * FILTER_OCTAL
     *
     * @return static
     */
    public function octal(): static;

    /**
     * FILTER_VALIDATE_INT
     *
     * @return static
     */
    public function int(): static;

    /**
     * FILTER_VALIDATE_FLOAT
     *
     * @return static
     */
    public function float(): static;

    /**
     * FILTER_VALIDATE_BOOLEAN
     *
     * @return static
     */
    public function bool(): static;

    /**
     * FILTER_VALIDATE_IP
     *
     * @return static
     */
    public function ip(): static;

    /**
     * FILTER_FLAG_IPV4
     * CAUTION: Does not fucking work
     *
     * @return static
     */
    public function ipv4(): static;

    /**
     * FILTER_FLAG_IPV6
     * CAUTION: Does not fucking work
     *
     * @return static
     */
    public function ipv6(): static;

    /**
     * FILTER_CALLBACK
     *
     * @param $callback callable
     *
     * @return static
     */
    public function callback(callable $callback): static;

    /**
     * FILTER_ARRAY
     *
     * @return static
     */
    public function array(): static;

    /**
     * FILTER_VALIDATE_EMAIL
     *
     * @return static
     */
    public function email(): static;

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function url(): static;

    /**
     * FILTER_VALIDATE_URL
     *
     * @return static
     */
    public function mac(): static;

    /**
     * FILTER_TAGS
     *
     * @return static
     */
    public function string(): static;

    /**
     * FILTER_DATE
     *
     * @return static
     */
    public function date(): static;

    /**
     * FILTER_DATE_TIME
     *
     * @return static
     */
    public function dateTime(): static;

    /**
     * FILTER_SANITIZE_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringSpecial(): static;

    /**
     * FILTER_SANITIZE_FULL_SPECIAL_CHARS
     *
     * @return static
     */
    public function stringFull(): static;

    /**
     * FILTER_JSON_ARRAY | FILTER_JSON_OBJ
     *
     * @param bool $asArray
     * @return static
     */
    public function json(bool $asArray): static;

    /**
     * FILTER_UUID_BINARY | FILTER_UUID_STRING
     *
     * @param bool $asBytes
     *
     * @return static
     */
    public function uuid(bool $asBytes = false): static;

    /**
     * FILTER_DEFAULT
     *
     * @return static
     */
    public function noFilter(): static;
}
