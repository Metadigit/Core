<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\acl\provider;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
/**
 * ACL Provider via PDO.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class PdoProvider implements ProviderInterface {
	use \renovant\core\CoreTrait;

	const SQL_ADD_ACTION		= 'INSERT INTO `%s` (code, label) VALUES (:code, :label)';
	const SQL_ADD_FILTER		= 'INSERT INTO `%s` (code, label) VALUES (:code, :label)';
	const SQL_ADD_ROLE			= 'INSERT INTO `%s` (code, label) VALUES (:code, :label)';

	const SQL_GRANT_ROLE_ACTION = 'INSERT INTO t_acl_maps SET type = "ROLE_ACTION", role = (SELECT id FROM t_acl_roles WHERE code = :role), action = (SELECT id FROM t_acl_actions WHERE code = :action)';
	const SQL_GRANT_ROLE_FILTER = 'INSERT INTO t_acl_maps SET type = "USER_FILTER", role = (SELECT id FROM t_acl_roles WHERE code = :role), filter = (SELECT id FROM t_acl_filters WHERE code = :filter), filter_value = :filter_value';
	const SQL_GRANT_USER_ACTION = 'INSERT INTO t_acl_maps SET type = "USER_ACTION", user = :user, action = (SELECT id FROM t_acl_actions WHERE code = :action)';
	const SQL_GRANT_USER_FILTER = 'INSERT INTO t_acl_maps SET type = "USER_FILTER", user = :user, filter = (SELECT id FROM t_acl_filters WHERE code = :filter), filter_value = :filter_value';

	const SQL_REMOVE_ACTION		= 'DELETE FROM `%s` WHERE code = :code';
	const SQL_REMOVE_FILTER		= 'DELETE FROM `%s` WHERE code = :code';
	const SQL_REMOVE_ROLE		= 'DELETE FROM `%s` WHERE code = :code';

	const SQL_REVOKE_ROLE_ACTION= 'DELETE FROM t_acl_maps WHERE type = "ROLE_ACTION" AND role = (SELECT id FROM t_acl_roles WHERE code = :role) AND action = (SELECT id FROM t_acl_actions WHERE code = :action)';
	const SQL_REVOKE_ROLE_FILTER= 'DELETE FROM t_acl_maps WHERE type = "ROLE_FILTER" AND role = (SELECT id FROM t_acl_roles WHERE code = :role) AND filter = (SELECT id FROM t_acl_filters WHERE code = :filter) AND filter_value = :filter_value';
	const SQL_REVOKE_USER_ACTION= 'DELETE FROM t_acl_maps WHERE type = "USER_ACTION" AND user = :user AND action = (SELECT id FROM t_acl_actions WHERE code = :action)';
	const SQL_REVOKE_USER_FILTER= 'DELETE FROM t_acl_maps WHERE type = "USER_FILTER" AND user = :user AND filter = (SELECT id FROM t_acl_filters WHERE code = :filter) AND filter_value = :filter_value';

	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';
	/** DB tables
	 * @var array */
	protected $tables = [
		'acl'	=> 'sys_acl',
		'users'	=> 'users'
	];

	/**
	 * PdoProvider constructor.
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param array|null $tables
	 */
	function __construct($pdo='master', array $tables=null) {
		$prevTraceFn = sys::traceFn('sys.ACLProvider');
		if ($pdo) $this->pdo = $pdo;
		if ($tables) $this->tables = array_merge($this->tables, $tables);
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'initialize ACL storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace(
				['t_acl', 't_users'],
				[$this->tables['acl'], $this->tables['users']],
				file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function addAction($actionName, $label): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ADD_ACTION, $this->tables['acl'].'_actions'))
				->execute(['code'=>$actionName, 'label'=>$label])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function addFilter($filterName, $label): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ADD_FILTER, $this->tables['acl'].'_filters'))
				->execute(['code'=>$filterName, 'label'=>$label])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function addRole($roleName, $label): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ADD_ROLE, $this->tables['acl'].'_roles'))
				->execute(['code'=>$roleName, 'label'=>$label])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function grantRoleAction($roleName, $actionName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_GRANT_ROLE_ACTION))
				->execute(['role'=>$roleName, 'action'=>$actionName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function grantRoleFilter($roleName, $filterName, $filterValue): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_GRANT_ROLE_FILTER))
				->execute(['role'=>$roleName, 'filter'=>$filterName, 'filter_value'=>$filterValue])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function grantUserAction($userID, $actionName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_GRANT_USER_ACTION))
				->execute(['user'=>$userID, 'action'=>$actionName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function grantUserFilter($userID, $filterName, $filterValue): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_GRANT_USER_FILTER))
				->execute(['user'=>$userID, 'filter'=>$filterName, 'filter_value'=>$filterValue])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function removeAction($actionName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_REMOVE_ACTION, $this->tables['acl'].'_actions'))
				->execute(['code'=>$actionName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function removeFilter($filterName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_REMOVE_FILTER, $this->tables['acl'].'_filters'))
				->execute(['code'=>$filterName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function removeRole($roleName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_REMOVE_ROLE, $this->tables['acl'].'_roles'))
				->execute(['code'=>$roleName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function revokeRoleAction($roleName, $actionName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_REVOKE_ROLE_ACTION))
				->execute(['role'=>$roleName, 'action'=>$actionName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function revokeRoleFilter($roleName, $filterName, $filterValue): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_REVOKE_ROLE_FILTER))
				->execute(['role'=>$roleName, 'filter'=>$filterName, 'filter_value'=>$filterValue])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function revokeUserAction($userID, $actionName): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_REVOKE_USER_ACTION))
				->execute(['user'=>$userID, 'action'=>$actionName])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function revokeUserFilter($userID, $filterName, $filterValue): bool {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(str_replace('t_acl', $this->tables['acl'], self::SQL_REVOKE_USER_FILTER))
				->execute(['user'=>$userID, 'filter'=>$filterName, 'filter_value'=>$filterValue])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
