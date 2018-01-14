<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth\provider;
/**
 * Authentication Provider interface.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface ProviderInterface {

	/**
	 * Perform login
	 * @param string $login
	 * @param string $password
	 * @return integer user ID on success, negative code on ERROR
	 */
	function checkCredentials($login, $password): int;

	/**
	 * Check Refresh Token validity
	 * @param $userId
	 * @param $token
	 * @return bool TRUE if valid
	 */
	function checkRefreshToken($userId, $token): bool;

	/**
	 * Store new Refresh Token value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setRefreshToken($userId, $token, $expireTime);
}
