<?php
namespace Coercive\Utility\Globals;

use Exception;

/**
 * Globals
 * PHP Version 	7
 *
 * @version		1
 * @package 	Coercive\Utility\Globals
 * @link		@link https://github.com/Coercive/Globals
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   2016 - 2017 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 *
 * @method 		Globals|mixed 	COOKIE($sName = null, $mValue = null)
 * @method 		Globals|mixed 	ENV($sName = null, $mValue = null)
 * @method 		Globals|mixed 	FILE($sName = null, $mValue = null)
 * @method 		Globals|mixed 	GET($sName = null, $mValue = null)
 * @method 		Globals|mixed 	POST($sName = null, $mValue = null)
 * @method 		Globals|mixed 	REQUEST($sName = null, $mValue = null)
 * @method 		Globals|mixed 	SERVER($sName = null, $mValue = null)
 * @method 		Globals|mixed 	SESSION($sName = null, $mValue = null)
 */
class Globals {

	const FILTER_OCTAL = 1;

	/** @var string */
	private $_sGlobal = '';

	/** @var bool */
	private $_bFilter = true;

	/** @var int */
	private $_iFilterType = 0;

	/** @var int */
	private $_iSpecialFilterType = 0;

	/**
	 * Globals constructor.
	 */
	public function __construct() {
		# Does nothing, but i'm happy to have a construct !
	}

	/**
	 * SET GLOBAL
	 *
	 * @param string $sName
	 * @return bool
	 */
	private function _setGlobal($sName) {

		# PREPARE
		$sGlobal = '_'.strtoupper($sName);
		global $$sGlobal;

		# VERIFY
		if(!is_array($$sGlobal)) return false;

		# SET
		$this->_sGlobal = $sGlobal;
		return true;
	}

	/**
	 * AUTO FILTER
	 *
	 * @param mixed $mItem
	 * @return mixed
	 */
	private function _autoFilter($mItem) {

		# NULL
		if(is_null($mItem)) { return null; }

		# WHAT ELSE
		if(is_object($mItem) || is_resource($mItem)) {
			return $mItem;
		}
		
		# If a class already called : crash
		try {
			if(@is_callable($mItem)) {
				return $mItem;
			}
		}
		catch(Exception $oException) {
			return $mItem;
		}

		# BOOL
		if(is_bool($mItem) || $mItem === 'false' || $mItem === 'true') {
			if($mItem === 'false') { $mItem = false; }
			elseif($mItem === 'true') { $mItem = true; }
			return $mItem;
		}

		# ARRAY
		if(is_array($mItem)) {
			foreach ($mItem as $k => $v) {
				$mItem[$k] = $this->_autoFilter($v);
			}
			return $mItem;
		}

		# INT
		$iInt = filter_var($mItem, FILTER_VALIDATE_INT);
		if($iInt !== false) { return $iInt; }

		# FLOAT
		if(preg_match('#^([\+\-])?(?!0[0-9]+)[0-9]+\.[0-9]+$#', $mItem)) {
			return filter_var($mItem, FILTER_VALIDATE_FLOAT);
		}

		# STRING
		return filter_var($mItem, FILTER_SANITIZE_SPECIAL_CHARS);

	}

	/**
	 * GETTER
	 *
	 * @param string $sName
	 * @return null|mixed
	 */
	private function _get($sName) {

		global ${$this->_sGlobal};

		# EXIT
		if(!isset(${$this->_sGlobal}[$sName])) return null;

		/** @var mixed $mVar */
		$mVar = ${$this->_sGlobal}[$sName];
		if(!is_array($mVar)) { $mVar = [$mVar]; }

		foreach ($mVar as $k => $v) {
			# INNER FILTER
			if($this->_iSpecialFilterType) {
				switch ($this->_iSpecialFilterType) {
					case self::FILTER_OCTAL:
						$mVar[$k] = octdec($v);
						break;
				}
			}
			# FILTER
			elseif($this->_iFilterType) {
				$mVar[$k] = filter_var($v, $this->_iFilterType);
			}
			# AUTO
			else {
				$mVar[$k] = $this->_bFilter ? $this->_autoFilter($v) : $v;
			}
		}

		$this->_iSpecialFilterType = 0;
		$this->_iFilterType = 0;
		return $mVar;

	}

	/**
	 * SETTER
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 * @return bool
	 */
	private function _set($sName, $mValue) {

		global ${$this->_sGlobal};

		# SKIP
		if(!is_array(${$this->_sGlobal})) { return false; }

		# ASSIGN
		${$this->_sGlobal}[$sName] = $mValue;

		return true;
	}

	/**
	 * PUBLIC AUTO FILTER VAR
	 * 
	 * @param mixed $mVar
	 * @return mixed
	 */
	public function autoFilterManualVar($mVar) {
		return $this->_autoFilter($mVar);
	}

	/**
	 * (DES)ACTIVATE FILTER
	 *
	 * @param $bBool
	 * @return Globals
	 */
	public function filter($bBool) {
		$this->_bFilter = (bool) $bBool;
		return $this;
	}

	/**
	 * FILTER ALL
	 *
	 * @return array
	 */
	public function filterAll() {

		global ${$this->_sGlobal};
		if(!is_array(${$this->_sGlobal})) { return []; }

		# Processing
		$aGlobal = [];
		foreach (${$this->_sGlobal} as $sParamName => $mParamValue) {
			$aGlobal[$sParamName] = $this->_autoFilter($mParamValue);
		}

		return $aGlobal;
	}

	/**
	 * AUTO CALL $_[ITEM]
	 *
	 * @param string $sName Global Name
	 * @param array $aArguments [0] Field Name, [1] Set Value [optional]
	 * @return mixed
	 */
	public function __call($sName, $aArguments = []) {

		# EXIT
		if(empty($sName) || !$this->_setGlobal($sName)) { return null; }

		# CHAINABILITY
		if(empty($aArguments[0])) { return $this; }

		# SET
		if(isset($aArguments[1])) { return $this->_set($aArguments[0], $aArguments[1]); }

		# GET
		return $this->_get($aArguments[0]);

	}

	/**
	 * FILTER_OCTAL
	 *
	 * @return Globals
	 */
	public function octal() {
		$this->_iSpecialFilterType = self::FILTER_OCTAL;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_INT
	 *
	 * @return Globals
	 */
	public function int() {
		$this->_iFilterType = FILTER_VALIDATE_INT;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_FLOAT
	 *
	 * @return Globals
	 */
	public function float() {
		$this->_iFilterType = FILTER_VALIDATE_FLOAT;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_BOOLEAN
	 *
	 * @return Globals
	 */
	public function bool() {
		$this->_iFilterType = FILTER_VALIDATE_BOOLEAN;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_IP
	 *
	 * @return Globals
	 */
	public function ip() {
		$this->_iFilterType = FILTER_VALIDATE_IP;
		return $this;
	}

	/**
	 * FILTER_FLAG_IPV4
	 *
	 * @return Globals
	 */
	public function ipv4() {
		$this->_iFilterType = FILTER_FLAG_IPV4;
		return $this;
	}

	/**
	 * FILTER_FLAG_IPV6
	 *
	 * @return Globals
	 */
	public function ipv6() {
		$this->_iFilterType = FILTER_FLAG_IPV6;
		return $this;
	}

	/**
	 * FILTER_CALLBACK
	 *
	 * @return Globals
	 */
	public function callback() {
		$this->_iFilterType = FILTER_CALLBACK;
		return $this;
	}

	/**
	 * FILTER_REQUIRE_ARRAY
	 *
	 * @return Globals
	 */
	public function rArray() {
		$this->_iFilterType = FILTER_REQUIRE_ARRAY;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_EMAIL
	 *
	 * @return Globals
	 */
	public function email() {
		$this->_iFilterType = FILTER_VALIDATE_EMAIL;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_URL
	 *
	 * @return Globals
	 */
	public function url() {
		$this->_iFilterType = FILTER_VALIDATE_URL;
		return $this;
	}

	/**
	 * FILTER_VALIDATE_URL
	 *
	 * @return Globals
	 */
	public function mac() {
		$this->_iFilterType = FILTER_VALIDATE_MAC;
		return $this;
	}

	/**
	 * FILTER_SANITIZE_SPECIAL_CHARS
	 *
	 * @return Globals
	 */
	public function string() {
		$this->_iFilterType = FILTER_SANITIZE_SPECIAL_CHARS;
		return $this;
	}

	/**
	 * FILTER_SANITIZE_FULL_SPECIAL_CHARS
	 *
	 * @return Globals
	 */
	public function stringFull() {
		$this->_iFilterType = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
		return $this;
	}

	/**
	 * FILTER_DEFAULT
	 *
	 * @return Globals
	 */
	public function noFilter() {
		$this->_iFilterType = FILTER_DEFAULT;
		return $this;
	}

}
