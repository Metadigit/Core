<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\session\handler;
use metadigit\core\Kernel,
	metadigit\core\session\SessionException;
/**
 * HTTP Session Handler implementation with an Sqlite database.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Sqlite implements \SessionHandlerInterface {

	const SQL_INIT = '
		CREATE TABLE IF NOT EXISTS `%s` (
			id			char(32) NOT NULL,
			ip			char(15) NOT NULL,
			startTime	integer,
			lastTime	integer,
			expireTime	integer,
			uid			int(10) NULL DEFAULT NULL,
			locked		tinyint(1) NOT NULL DEFAULT "0",
			data		text NOT NULL,
			PRIMARY KEY (id)
		);
		CREATE INDEX IF NOT EXISTS k_ip ON `%s` (ip);
	';
	const SQL_READ = 'SELECT ip, uid, locked, data FROM `%s` WHERE id = :id AND expireTime > :expireTime';
	const SQL_INSERT = 'INSERT INTO `%s` (id, ip, startTime, lastTime, expireTime, uid, locked, data) VALUES (:id, :ip, :startTime, :lastTime, :expireTime, :uid, :locked, :data)';
	const SQL_UPDATE = 'UPDATE `%s` SET ip = :ip, lastTime = :lastTime, expireTime = :expireTime, uid = :uid, locked = :locked, data = :data WHERE id = :id';
	const SQL_DESTROY = 'DELETE FROM `%s` WHERE id = :id';
	const SQL_GC = 'DELETE FROM `%s` WHERE expireTime < :time';

	/** database table name
	 * @var string */
	protected $table;
	/** PDO instance ID
	 * @var \PDO */
	protected $pdo;
	/** session ID on read(), to support session_regenerate_id()
	 * @var string */
	static protected $id;

	/**
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 */
	function __construct($pdo, $table='sessions') {
		$this->pdo = $pdo;
		$this->table = $table;
		TRACE and Kernel::trace(LOG_DEBUG, 1, __METHOD__, 'initialize session storage');
		Kernel::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table, $table));
	}

	/**
	 * Session open handler
	 * Is first function called by PHP when a session is started
	 * @param string $p save path
	 * @param string $n session name
	 * @throws \metadigit\core\session\SessionException
	 * @return boolean TRUE on success
	 */
	function open($p, $n) {
		if(!Kernel::pdo($this->pdo)) throw new SessionException(13);
		return true;
	}

	/**
	 * Session close handler
	 * @return boolean TRUE on success
	 */
	function close() {
		return true;
	}

	/**
	 * Session read handler
	 * Manage normal PHP Session & xSessions
	 * @param string $id session ID
	 * @return string session data, EMPTY string if non session data!
	 */
	function read($id) {
		try {
			$st = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_READ, $this->table));
			$st->execute(['id'=>$id, 'expireTime'=>time()]);
			list($ip, $uid, $locked, $data) = $raw = $st->fetch(\PDO::FETCH_NUM);
			if(empty($ip)) define('SESSION_UID', null);
			else {
				self::$id = $id;
				define('SESSION_UID', $uid);
				define('SESSION_LOCKED', (boolean) $locked);
			}
			return (string) $data;
		} catch(\Exception $Ex) {
			trigger_error($Ex->getMessage());
			return '';
		}
	}

	/**
	 * Session write handler (remember: it is executed after the output stream is closed!)
	 * @param string $id session ID
	 * @param string $data session data
	 * @return boolean TRUE on success
	 */
	function write($id, $data) {
		try {
			$uid = (defined('SESSION_NEW_UID')) ? SESSION_NEW_UID : (defined('SESSION_UID') ? SESSION_UID : null);
			$locked = (defined('SESSION_LOCKED')) ? (int)SESSION_LOCKED : 0;
			$params = [
				'id'	=> $id,
				'ip'	=> $_SERVER['REMOTE_ADDR'],
				'lastTime'=>time(),
				'expireTime'=>time()+86400,
				'uid'	=> $uid,
				'locked'=> $locked,
				'data'	=> $data
			];
			if(self::$id != $id) { // can be a new session OR a regenerated session
				$params['startTime'] = time();
				$st = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_INSERT, $this->table));
			} else $st = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_UPDATE, $this->table));
			$st->execute($params);
			return true;
		} catch(\Exception $Ex) {
			trigger_error($Ex->getMessage());
			return false;
		}
	}

	/**
	 * Session destroy handler
	 * @param string $id session ID
	 * @return boolean TRUE on success
	 */
	function destroy($id) {
		try {
			$st = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_DESTROY, $this->table));
			$st->execute(['id'=>$id]);
			return (boolean) $st->rowCount();
		} catch(\Exception $Ex) {
			return false;
		}
	}

	/**
	 * garbage collection handler
	 * @param integer $maxlifetime
	 * @return boolean TRUE on success
	 */
	function gc($maxlifetime) {
		try {
			return Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_GC, $this->table))->execute(['time'=>time()]);
		} catch(\Exception $Ex) {
			return false;
		}
	}
}