<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\mail;
use Swift_Message;
/**
 * Wrapper for Swift_Mailer 4.3.0
 *
 * Usage example:
 * {@example mail/Mailer.php 2}
 * Configuration file example: project/services/context.xml
 * {@example mail/Mailer.xml}
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @link http://swiftmailer.org/
 */
class Mailer {
	use \metadigit\core\CoreTrait;

	/** default transport type to be used */
	const DEFAULT_TRANSPORT = 'smtp';
	/** Array of failed recipients after a call to Mailer->send() or Mailer->batchSend()
	 * @var array */
	protected $failedRecipients = [];
	/** Swift_Mailer instance
	 * @var \Swift_Mailer */
	protected $Mailer;
	/** Swift_Transport instance
	 * @var \Swift_Transport */
	protected $Transport;
	/** SMTP params array
	 * @var array */
	protected $transportOptions = [
		'server'	=> 'localhost',
		'port'		=> 25,
		'encryption'=> false,
		'user'		=> null,
		'password'	=> null
	];
	/** mail transport type to be used, can be: mail | smtp | sendmail (default: mail)
	 * @var string */
	protected $transportType = self::DEFAULT_TRANSPORT;
	/** Swift directory
	 * @var string */
	protected $swiftDirectory;

	/**
	 * @param string|null $swiftDirectory
	 * @param string $transportType
	 * @param array|null $transportOptions
	 */
	function __construct($swiftDirectory=null, $transportType=self::DEFAULT_TRANSPORT, array $transportOptions=null) {
		if(is_null($swiftDirectory) && defined('SWIFT_DIR')) $swiftDirectory = SWIFT_DIR;
		if(!is_dir($swiftDirectory)) trigger_error('SWIFT_DIR not defined');
		$this->swiftDirectory = $swiftDirectory;
		$this->transportType = $transportType;
		if($transportType == 'smtp' && !is_null($transportOptions)) $this->transportOptions = $transportOptions;
	}

	function __call($method, $args) {
		if(is_null($this->Mailer)) $this->initMailer();
		$this->trace(LOG_DEBUG, 1, $method);
		return call_user_func_array([$this->Mailer, $method], $args);
	}

	function __sleep() {
		return ['_oid', 'transportOptions', 'transportType', 'swiftDirectory'];
	}

	/**
	 * Initialization method.
	 * It creates:
	 * - an appropriate Swift_Transport instance, one of Swift_SendmailTransport | Swift_SmtpTransport | Swift_MailTransport
	 * - Swift_Mailer instance
	 */
	protected function initMailer() {
		$this->trace(LOG_DEBUG, 1,  __FUNCTION__);
		require $this->swiftDirectory.'dependency_maps/cache_deps.php';
		require $this->swiftDirectory.'dependency_maps/message_deps.php';
		require $this->swiftDirectory.'dependency_maps/mime_deps.php';
		require $this->swiftDirectory.'dependency_maps/transport_deps.php';
		// Sets the default charset so that setCharset() is not needed elsewhere
		\Swift_Preferences::getInstance()->setCharset('utf-8');
		// Without these lines the default caching mechanism is "array" but this uses a lot of memory.
		// If possible, use a disk cache to enable attaching large attachments etc
		\Swift_Preferences::getInstance()->setTempDir(\metadigit\core\TMP_DIR)->setCacheType('disk');
		\Swift_Preferences::getInstance()->setQPDotEscape(false);
		//Create the Transport
		switch($this->transportType) {
			case 'sendmail':
				$this->Transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
				break;
			case 'smtp':
				$port = (isset($this->transportOptions['port'])) ? (int) $this->transportOptions['port'] : 25;
				$this->Transport = \Swift_SmtpTransport::newInstance($this->transportOptions['server'], $port);
				if(!empty($this->transportOptions['encryption'])) $this->Transport->setEncryption($this->transportOptions['encryption']);
				if(!empty($this->transportOptions['user'])) $this->Transport->setUsername($this->transportOptions['user'])->setPassword($this->transportOptions['password']);
				break;
			default:
				$this->Transport = \Swift_MailTransport::newInstance();
		}
		//Create the Mailer using created Transport
		$this->Mailer = \Swift_Mailer::newInstance($this->Transport);
	}

	/**
	 * Create a Swift_Message instance
	 * @return Swift_Message
	 */
	function newMessage() {
		if(is_null($this->Mailer)) $this->initMailer();
		$this->trace(LOG_DEBUG, 1, __FUNCTION__);
		return Swift_Message::newInstance();
	}

	/**
	 * Wrapper for Swift_Mailer->batchSend().
	 * It add debug support.
	 * @param Swift_Message $Message
	 * @return integer the number of successful recipients
	 * @see Swift_Mailer::batchSend()
	 */
	function batchSend(Swift_Message $Message) {
		if(is_null($this->Mailer)) $this->initMailer();
		$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'START');
		$n = $this->Mailer->batchSend($Message, $this->failedRecipients);
		$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'END: Mail successfully sent! Recipients OK: '.$n.' FAILED: '.count($this->failedRecipients));
		return $n;
	}

	/**
	 * Wrapper for Swift_Mailer->send().
	 * It add debug support.
	 * @param Swift_Message $Message
	 * @return integer the number of successful recipients
	 * @see Swift_Mailer::send()
	 */
	function send(Swift_Message $Message) {
		if(is_null($this->Mailer)) $this->initMailer();
		$this->trace(LOG_DEBUG, 1, __FUNCTION__);
		$n = $this->Mailer->send($Message, $this->failedRecipients);
		$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'END: Mail successfully sent! Recipients OK: '.$n.' FAILED: '.count($this->failedRecipients));
		return $n;
	}
}