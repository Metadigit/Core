<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use metadigit\core\Kernel,
	metadigit\core\depinjection\Container,
	metadigit\core\depinjection\ContainerException,
	metadigit\core\event\Event,
	metadigit\core\event\EventDispatcherInterface,
	metadigit\core\util\xml\XMLValidator;
/**
 * Context
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Context implements EventDispatcherInterface {
	use \metadigit\core\CoreTrait;

	const FAILURE_EXCEPTION	= 1;
	const FAILURE_SILENT	= 2;

	/** instantiated contexts
	 * @var array */
	static protected $_instances = [];

	/**
	 * Factory method to build a Context
	 * @param string $namespace Context namespace
	 * @param boolean $useCache default TRUE, set FALSE to rebuild Context from XML skipping system cache
	 * @return Context
	 */
	static function factory($namespace, $useCache=true) {
		if($useCache && isset(self::$_instances[$namespace]))
			return self::$_instances[$namespace];
		elseif($useCache && $Context = Kernel::getCache()->get($namespace.'.Context'))
			return self::$_instances[$namespace] = $Context;
		else {
			TRACE and Kernel::trace(LOG_DEBUG, TRACE_DEPINJ, __METHOD__, $namespace);
			list($namespace2, $className, $dirName, $fileName) = Kernel::parseClassName(str_replace('.','\\', $namespace.'.Context'));
			if(empty($dirName))
				$xmlPath = \metadigit\core\BASE_DIR.$namespace.'-context.xml';
			else
				$xmlPath = $dirName.DIRECTORY_SEPARATOR.'context.xml';
			self::$_instances[$namespace] = $Context = new Context($namespace, $xmlPath);
			Kernel::getCache()->set($namespace.'.Context', $Context);
			return $Context;
		}
	}

	/** Map of available objects & their classes
	 * @var array */
	protected $id2classMap = [];
	/** Included Contexts namespaces
	 * @var array */
	protected $includedNamespaces = [];
	/** registered listeners (callbacks)
	 * @var array */
	protected $listeners = [];
	/** Context namespace
	 * @var string */
	protected $namespace;
	/** Array of instantiated objects (to avoid replication)
	 * @var array */
	protected $objects = [];
	/** XML Parser
	 * @var ContextXmlParser */
	protected $XmlParser;
	/** Context XML path
	 * @var string */
	protected $xmlPath;

	/**
	 * Constructor
	 * @param string		$namespace	Context namespace
	 * @param string|null	$xmlPath	optional XML path
	 * @throws ContextException
	 */
	function __construct($namespace, $xmlPath=null) {
		$this->_oid = $namespace.'.Context';
		$this->namespace = $namespace;
		$this->xmlPath = $xmlPath;
		if(!is_null($xmlPath)) {
			if(!file_exists($xmlPath)) throw new ContextException(11, [$this->_oid, $xmlPath]);
			if(!XMLValidator::schema($xmlPath, __DIR__.'/Context.xsd')) throw new ContextException(12, [$xmlPath]);
			TRACE and $this->trace(LOG_DEBUG, TRACE_DEPINJ, __FUNCTION__, '[START] parsing Context XML');
			$this->getXmlParser()->verify();
			$this->includedNamespaces = $this->getXmlParser()->getIncludes();
			$this->getXmlParser()->parseEventListeners($this);
			TRACE and $this->trace(LOG_DEBUG, TRACE_DEPINJ, __FUNCTION__, '[END] Context ready');
			$XML = simplexml_load_file($xmlPath);
		}
		// create Container
		$containerXmlPath = \metadigit\core\CACHE_DIR.$namespace.'.Container'.'.xml';
		file_put_contents($containerXmlPath, $XML->xpath('/context/objects')[0]->asXML());
		$Container = new Container($namespace, $containerXmlPath, $this->includedNamespaces, $this->_oid);
		Kernel::getCache()->set($namespace.'.Container', $Container);
		$ReflProp = new \ReflectionProperty($Container, 'id2classMap');
		$ReflProp->setAccessible(true);
		$this->id2classMap = $ReflProp->getValue($Container);
	}

	function __sleep() {
		return ['_oid', 'id2classMap', 'includedNamespaces', 'listeners', 'namespace', 'xmlPath'];
	}

	/**
	 * @see metadigit\core\event\EventDispatcherInterface
	 */
	function listen($eventName, $callback, $priority=1) {
		$this->listeners[$eventName][(int)$priority][] = $callback;
		krsort($this->listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 *  Return TRUE if contains object (optionally verifiyng class)
	 * @param string $id object OID
	 * @param string $class class/interface that object must extend/implement (optional)
	 * @return boolean
	 */
	function has($id, $class=null) {
		return ( isset($this->id2classMap[$id]) && ( is_null($class) || (in_array($class,$this->id2classMap[$id])) ) ) ? true : false;
	}

	/**
	 * Get an object.
	 * @param string $id object identifier
	 * @param string $class required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContextException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		TRACE and $this->trace(LOG_DEBUG, TRACE_DEPINJ, __FUNCTION__, $id);
		if(isset($this->objects[$id]) && (is_null($class) || $this->objects[$id] instanceof $class)) return $this->objects[$id];
		try {
			$Obj = null;
			if($this->has($id, $class)) {
				if(Kernel::getCache()->has($id)) $Obj = Kernel::getCache()->get($id);
				else Kernel::getCache()->set($id, $Obj = $this->getContainer()->get($id, $class));
				if($Obj instanceof ContextAwareInterface) $Obj->setContext($this);
			} else {
				$ctxNamespace = null;
				foreach($this->includedNamespaces as $namespace) {
					if(strpos($id, $namespace)===0) $ctxNamespace = $namespace;
				}
				if(!is_null($ctxNamespace))
					$Obj = self::factory($ctxNamespace)->get($id, $class);
			}
			if(is_null($Obj)) throw new ContextException(1, [$this->_oid, $id]);
			$this->objects[$id] = $Obj;
			return $Obj;
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw new ContextException($Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * return Dependency Injector Container
	 * @return \metadigit\core\depinjection\Container
	 */
	protected function getContainer() {
		return Kernel::getCache()->get($this->namespace.'.Container');
	}

	/**
	 * @see metadigit\core\event\EventDispatcherInterface
	 */
	function trigger($eventName, $target=null, array $params=null, $Event=null) {
		if(TRACE) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
			$func = ((isset($trace['object'])) ? $trace['object']->_oid().'->' : $trace['class'].'::').$trace['function'];
			Kernel::trace(LOG_DEBUG, TRACE_EVENT, $func, strtoupper($eventName));
		}
		$params['Context'] = $this;
		if(is_null($Event)) $Event = new Event($target, $params);
		$Event->setName($eventName);
		if(!isset($this->listeners[$eventName])) return $Event;
		foreach($this->listeners[$eventName] as $listeners) {
			foreach($listeners as $callback) {
				if(is_string($callback) && strpos($callback,'->')>0) {
					$callback = explode('->', $callback);
					$callback[0] = $this->get($callback[0]);
				}
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
	}

	/**
	 * @return ContextXmlParser
	 */
	protected function getXmlParser() {
		return (!is_null($this->XmlParser)) ? $this->XmlParser : $this->Parser = new ContextXmlParser($this->namespace, $this->xmlPath);
	}
}
