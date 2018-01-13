<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use const metadigit\core\DATA_DIR;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\Event as HttpEvent,
	Firebase\JWT\BeforeValidException,
	Firebase\JWT\ExpiredException,
	Firebase\JWT\JWT;
/**
 * Authentication Manager.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class AUTH {
	use \metadigit\core\CoreTrait;

	const MODULES = [
		'COOKIE',
		'JWT',
		'SESSION'
	];
	const JWT_KEY = DATA_DIR.'JWT.key';

	/** Pending commit  flag
	 * @var bool */
	protected $_commit = false;
	/** User custom data
	 * @var array */
	protected $_data = [];
	/** Group ID
	 * @var integer|null */
	protected $_GID = null;
	/** Group name
	 * @var string|null */
	protected $_GROUP = null;
	/** User name (full-name)
	 * @var string|null */
	protected $_NAME = null;
	/** User ID
	 * @var integer|null */
	protected $_UID = null;
	/** XSRF-TOKEN value
	 * @var string|null */
	protected $_XSRF_TOKEN = null;

	/** active module
	 * @var string */
	protected $module = 'SESSION';

	/** APPs to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthApps = [];
	/** URLs to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthUrls = [];
	/** APPs to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFApps = [];
	/** URLs to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFUrls = [];

	/**
	 * AUTH constructor.
	 * @param string $module
	 * @throws Exception
	 */
	function __construct($module='SESSION') {
		if(!in_array($module, self::MODULES)) throw new Exception(1, [$module, implode(', ', self::MODULES)]);
		$this->module = $module;
		switch ($this->module) {
			case 'JWT':
				if(!class_exists('Firebase\JWT\JWT')) throw new Exception(12);
				if(!file_exists(self::JWT_KEY))
					file_put_contents(self::JWT_KEY, base64_encode(openssl_random_pseudo_bytes(64)));
				break;
		}
	}

	function __sleep() {
		return ['_', 'module', 'skipAuthApps', 'skipAuthUrls', 'skipXSRFApps', 'skipXSRFUrls'];
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws Exception
	 */
	function init(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$Req = $Event->getRequest();
			$APP = $Req->getAttribute('APP');
			$URI = $Req->URI();

			// AUTH & backend XSRF-TOKEN
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					if (isset($_COOKIE['JWT'])) {
						try {
							$token = (array)JWT::decode($_COOKIE['JWT'], file_get_contents(self::JWT_KEY), ['HS512']);
							$this->_XSRF_TOKEN = $token['XSRF-TOKEN'] ?? null;
							if (isset($token['data']) && $token['data'] = (array)$token['data']) {
								foreach ($token['data'] as $k => $v)
									$this->set($k, $v);
								$this->_commit = false;
								sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH OK', $token['data']);
							}
						} catch (ExpiredException $Ex) {
							throw new AuthException(23);
						} catch (BeforeValidException $Ex) {
							throw new AuthException(22);
						} catch (\Exception $Ex) { // include SignatureInvalidException, UnexpectedValueException
							throw new AuthException(21);
						}
					}
					break;
				case 'SESSION':
					if (session_status() != PHP_SESSION_ACTIVE) throw new Exception(23);
					$this->_XSRF_TOKEN = $_SESSION['XSRF-TOKEN'] ?? null;
					if (isset($_SESSION['__AUTH__']) && is_array($_SESSION['__AUTH__'])) {
						foreach ($_SESSION['__AUTH__'] as $k => $v)
							$this->set($k, $v);
						$this->_commit = false;
						sys::trace(LOG_DEBUG, T_INFO, 'SESSION AUTH OK', $_SESSION['__AUTH__']);
					}
					break;
			}
			if (!$this->_UID && $URI != '/' && !in_array($APP, $this->skipAuthApps) && !$this->checkAUTH($URI))
				throw new AuthException(101, [$this->module]);

			// XSRF-TOKEN
			if (!$this->_XSRF_TOKEN)
				$this->_commit = true;
			$XSRFToken = $Req->getHeader('X-XSRF-TOKEN');
			if ($XSRFToken && $XSRFToken === $this->_XSRF_TOKEN)
				sys::trace(LOG_DEBUG, T_INFO, 'XSRF-TOKEN OK');
			elseif ($XSRFToken && $XSRFToken != $this->_XSRF_TOKEN)
				throw new AuthException(50, [$this->module]);
			elseif ($URI != '/' && !in_array($APP, $this->skipXSRFApps) && !$this->checkXSRF($URI))
				throw new AuthException(102, [$this->module]);

		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @param $URI
	 * @return boolean
	 */
	protected function checkAUTH($URI) {
		foreach ($this->skipAuthUrls as $url)
			if(preg_match($url, $URI)) return true;
		return false;
	}

	/**
	 * @param $URI
	 * @return boolean
	 */
	protected function checkXSRF($URI) {
		foreach ($this->skipXSRFUrls as $url)
			if(preg_match($url, $URI)) return true;
		return false;
	}

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 */
	function commit() {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');
		try {
			if(!$this->_XSRF_TOKEN) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
				$this->_XSRF_TOKEN = md5(uniqid(rand(1,999)));
			}
			sys::trace(LOG_DEBUG, T_INFO, 'set XSRF-TOKEN cookie');
			setcookie('XSRF-TOKEN', $this->_XSRF_TOKEN, 0, '/', null, false, false);
			$data = array_merge([
				'GID'	=> $this->_GID,
				'GROUP'	=> $this->_GROUP,
				'NAME'	=> $this->_NAME,
				'UID'	=> $this->_UID
			], $this->_data);
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'set JWT cookie');
					$token = [
						//'aud' => 'http://example.com',
						'exp' => time()+30,
						'iat' => time()-1,
						//'iss' => 'http://example.org',
						'nbf' => time()-1,
						'data' => $this->_UID ? $data : null,
						'XSRF-TOKEN'=>$this->_XSRF_TOKEN
					];
					setcookie('JWT', JWT::encode($token, file_get_contents(self::JWT_KEY), 'HS512'), 0, '/', '', true, true);
					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'update SESSION data');
					$_SESSION['__AUTH__'] = $this->_UID ? $data : null;
					$_SESSION['XSRF-TOKEN'] = $this->_XSRF_TOKEN;
			}
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 */
	function erase() {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			$this->_data = [];
			$this->_GID = $this->_GROUP = $this->_NAME = $this->_UID = null;
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'erase JWT cookie data');
					$token = [
						//'aud' => 'http://example.com',
						'exp' => time()+3600,
						'iat' => time()-1,
						//'iss' => 'http://example.org',
						'nbf' => time()-1,
						'data' => null,
						'XSRF-TOKEN'=>$this->_XSRF_TOKEN
					];
					setcookie('JWT', JWT::encode($token, file_get_contents(self::JWT_KEY), 'HS512'), 0, '/', '', true, true);

					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'erase SESSION data');
					//$token = $_SESSION['XSRF-TOKEN'];
					session_regenerate_id(false);
					unset($_SESSION['__AUTH__']);
					//$_SESSION['XSRF-TOKEN'] = $token;
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function get($key=null) {
		return (is_null($key)) ? $this->_data : ($this->_data[$key] ?? null);
	}

	/**
	 * Get group ID
	 * @return integer|null
	 */
	function GID() {
		return $this->_GID;
	}

	/**
	 * Get group name
	 * @return string|null
	 */
	function GROUP() {
		return $this->_GROUP;
	}

	/**
	 * Get User name
	 * @return string|null
	 */
	function NAME() {
		return $this->_NAME;
	}

	/**
	 * Set User data, also special values GID, GROUP, NAME, UID
	 * @param string $key
	 * @param mixed $value
	 * @return AUTH
	 */
	function set($key, $value) {
		switch ($key) {
			case 'GID': $this->_GID = (integer) $value; break;
			case 'GROUP': $this->_GROUP = (string) $value; break;
			case 'NAME': $this->_NAME = (string) $value; break;
			case 'UID': $this->_UID = (integer) $value; break;
			default: $this->_data[$key] = $value;
		}
		$this->_commit = true;
		return $this;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID() {
		return $this->_UID;
	}
}
