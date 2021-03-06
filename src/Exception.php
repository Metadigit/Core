<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
/**
 * Base Exception class
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends \Exception {

	const LEVEL = E_USER_ERROR;

	protected $data;

	/**
	 * @param int $code
	 * @param string|array $message
	 * @param mixed $data
	 */
	final function __construct($code=0, $message=null, $data=null) {
		$this->data = $data;
		if($tpl = @constant(get_class($this)."::COD$code")) {
			if(is_array($message)) {
				array_unshift($message, $tpl);
				$message = call_user_func_array('sprintf', $message);
			} elseif(is_null($message)) {
				$message = $tpl;
			}
		}
		parent::__construct((string)$message, (int)$code);
		TRACE and Kernel::trace(TRACE_DEFAULT, 1, get_class($this), '[CODE '.$this->getCode().'] '.$this->getMessage());
	}

	/**
	 * @return mixed|null
	 */
	function getData() {
		return $this->data;
	}

	/**
	 * can be overridden by subclass to provide extended debug information
	 */
	function getInfo() {
	}

	function trace() {
		KernelDebugger::traceException($this);
	}
}
