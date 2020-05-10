<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\acl;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\acl\provider\PdoProvider,
	renovant\core\acl\provider\ProviderInterface;

class AclService {
	use \renovant\core\CoreTrait;

	/** ACL modules to activate
	 * @var array */
	protected $modules = [];
	/** ACL Provider ID
	 * @var string */
	protected $provider = 'sys.AclProvider';

	/**
	 * @param array $modules ACL modules to activate
	 */
	function __construct(array $modules) {
		$prevTraceFn = sys::traceFn('sys.ACL');
		try {
			$this->modules = $modules;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Initialize ACL modules.
	 * To be invoked via event listener before HTTP Routing execution (HTTP:INIT or HTTP:ROUTE).
	 */
	function init() {
		sys::trace(LOG_DEBUG, T_INFO, 'activating modules '.implode(', ', $this->modules), null, $this->_.'->init');
		foreach ($this->modules as $mod)
			define('SYS_ACL_'.strtoupper($mod), true);
	}

	/**
	 * @return ProviderInterface
	 */
	function provider() {
		static $Provider;
		if(!$Provider) {
			try {
				$Provider = sys::context()->get($this->provider, ProviderInterface::class);
			} catch (\Exception $Ex) {
				$Provider = new PdoProvider;
			}
		}
		return $Provider;
	}
}
