<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\T_INFO;
use metadigit\core\context\Context,
	metadigit\core\container\Container,
	metadigit\core\container\ContainerException;
/**
 * Proxy for injected objects.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class CoreProxy {

	/** Object OID
	 * @var string */
	protected $_oid;
	/** Proxy-ed Object instance
	 * @var Object */
	protected $_Obj = null;

	/**
	 * @param string $id proxy-ed object OID
	 */
	function __construct($id) {
		$this->_oid = $id;
	}

	function __sleep() {
		return ['_oid'];
	}

	function __call($method, $args) {
		$prevTraceFn = sys::traceFn($this->_oid.'->'.$method);
		try {
			$this->_Obj || $this->_Obj = sys::cache('sys')->get($this->_oid);
			$this->_Obj || $this->_Obj = Context::factory(substr($this->_oid, 0, strrpos($this->_oid, '.')))->getContainer()->get($this->_oid, null, Container::FAILURE_SILENT);
			if($this->_Obj) {
				ACL_OBJECTS and !defined(get_class($this->_Obj).'::ACL_SKIP') and sys::acl()->onObject($this->_oid, $method, defined('SESSION_UID')? SESSION_UID : null);
				sys::trace(LOG_DEBUG, T_INFO);
				$r = call_user_func_array([$this->_Obj, $method], $args);
				sys::traceFn($prevTraceFn);
				return $r;
			}
			throw new ContainerException(4, [$this->_oid]);
		} catch (\Exception $Ex) {
			sys::traceFn($prevTraceFn);
			throw $Ex;
		}
	}
}
