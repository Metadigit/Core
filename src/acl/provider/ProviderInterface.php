<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\acl\provider;
/**
 * ACL Provider interface.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
interface ProviderInterface {

	/**
	 * Add new ACL Action
	 * @param string $actionName
	 * @param string $label
	 * @return bool
	 */
	function addAction($actionName, $label): bool;

	/**
	 * Add new ACL Filter
	 * @param string $filterName
	 * @param string $label
	 * @return bool
	 */
	function addFilter($filterName, $label): bool;

	/**
	 * Add new ACL Role
	 * @param string $roleName
	 * @param string $label
	 * @return bool
	 */
	function addRole($roleName, $label): bool;

	/**
	 * @param string $roleName
	 * @param string $actionName
	 * @return bool
	 */
	function grantRoleAction($roleName, $actionName): bool;

	/**
	 * @param string $roleName
	 * @param string $filterName
	 * @param mixed $filterValue
	 * @return bool
	 */
	function grantRoleFilter($roleName, $filterName, $filterValue): bool;

	/**
	 * @param integer $userID
	 * @param string $actionName
	 * @return bool
	 */
	function grantUserAction($userID, $actionName): bool;

	/**
	 * @param integer $userID
	 * @param string $filterName
	 * @param mixed $filterValue
	 * @return bool
	 */
	function grantUserFilter($userID, $filterName, $filterValue): bool;

	/**
	 * Remove ACL Action
	 * @param string $actionName
	 * @return bool
	 */
	function removeAction($actionName): bool;

	/**
	 * Remove ACL Filter
	 * @param string $filterName
	 * @return bool
	 */
	function removeFilter($filterName): bool;

	/**
	 * Remove ACL Role
	 * @param string $roleName
	 * @return bool
	 */
	function removeRole($roleName): bool;

	/**
	 * @param string $roleName
	 * @param string $actionName
	 * @return bool
	 */
	function revokeRoleAction($roleName, $actionName): bool;

	/**
	 * @param string $roleName
	 * @param string $filterName
	 * @param mixed $filterValue
	 * @return bool
	 */
	function revokeRoleFilter($roleName, $filterName, $filterValue): bool;

	/**
	 * @param integer $userID
	 * @param string $actionName
	 * @return bool
	 */
	function revokeUserAction($userID, $actionName): bool;

	/**
	 * @param integer $userID
	 * @param string $filterName
	 * @param mixed $filterValue
	 * @return bool
	 */
	function revokeUserFilter($userID, $filterName, $filterValue): bool;
}
