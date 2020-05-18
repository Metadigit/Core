<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth;
/**
 * Authentication data.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Auth {

	/** User custom data */
	protected array $data = [];
	/** Group ID
	 * @var integer|null */
	protected ?int $GID = null;
	/** Group name
	 * @var string|null */
	protected ?string $GROUP = null;
	/** User name (full-name)
	 * @var string|null */
	protected ?string $NAME = null;
	/** User ID
	 * @var integer|null */
	protected ?int $UID = null;

	static function instance(): Auth {
		static $Auth;
		if(!isset($Auth)) $Auth = new Auth;
		return $Auth;
	}

	private function __construct(?int $UID=null, ?int $GID=null, ?string $name=null, ?string $group=null, array $data=[]) {
		$this->GID = $GID;
		$this->GROUP = $group;
		$this->NAME = $name;
		$this->UID = $UID;
		$this->data = $data;
	}

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function data(?string $key=null) {
		return (is_null($key)) ? $this->data : ($this->data[$key] ?? null);
	}

	/**
	 * Get group ID
	 * @return integer|null
	 */
	function GID(): ?int {
		return $this->GID;
	}

	/**
	 * Get group name
	 * @return string|null
	 */
	function GROUP(): ?string {
		return $this->GROUP;
	}

	/**
	 * Get User name
	 * @return string|null
	 */
	function NAME(): ?string {
		return $this->NAME;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID(): ?int {
		return $this->UID;
	}
}
