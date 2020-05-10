<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\acl;

class ACL {
	use \renovant\core\CoreTrait;

	/** User ID
	 * @var integer|null */
	protected $UID = null;
	/** ACL actions
	 * @var array */
	protected $actions = [];
	/** ACL filters
	 * @var array */
	protected $filters = [];
	/** ACL roles
	 * @var array */
	protected $roles = [];

	/**
	 * @param integer|null $UID
	 * @param array $actions
	 * @param array $filters
	 * @param array $roles
	 */
	function __construct($UID=null, array $actions=[], array $filters=[], array $roles=[]) {
		$this->UID = $UID;
		$this->actions = $actions;
		$this->filters = $filters;
		$this->roles = $roles;
	}

	/**
	 * @param string $action
	 * @return bool
	 */
	function action($action) {
		return in_array($action, $this->actions);
	}

	/**
	 * @param string $filter
	 * @param mixed $value
	 * @return bool
	 */
	function filter($filter, $value) {
		if(!isset($this->filters[$filter])) return false;
		return in_array($value, $this->filters[$filter]);
	}

	/**
	 * @param string $role
	 * @return bool
	 */
	function role($role) {
		return in_array($role, $this->roles);
	}
}
