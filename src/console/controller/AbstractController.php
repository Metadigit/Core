<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\Exception;
/**
 * Convenient superclass for controller implementations.
 * It adds interception methods and automatic request parameters on method signature.
 * Implementation classes must implement a doHandle() method.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
abstract class AbstractController implements \metadigit\core\console\ControllerInterface {
	use \metadigit\core\CoreTrait;

	/** Controller handle method configuration
	 * @var array */
	protected $_handle = [];

	function __construct() {
		$this->_handle = AbstractControllerReflection::analyzeHandle($this);
	}

	function handle(Request $Req, Response $Res) {
		if(true!==$this->preHandle($Req, $Res)) {
			$this->trace(LOG_DEBUG, 1, 'preHandle', 'FALSE returned, skip Request handling');
			return null;
		}
		$args = [$Req, $Res];
		if(isset($this->_handle['params'])) {
			$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'building action params');
			foreach($this->_handle['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					$paramClass = $param['class'];
					$args[$i] = new $paramClass($Req);
				} elseif (isset($param['type'])) {
					switch($param['type']) {
							case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
							case 'integer': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
							case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
							case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
							default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? null: $v;
					}
				}
			}
		}
		$this->trace(LOG_DEBUG, 1, 'doHandle');
		$View = call_user_func_array([$this,'doHandle'], $args);
		$this->postHandle($Req, $Res, $View);
		return $View;
	}

	/**
	 * Pre-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 * @throws Exception in case of errors
	 * @return boolean TRUE on success, FALSE on error
	 */
	protected function preHandle(Request $Req, Response $Res) {
		return true;
	}

	/**
	 * Post-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 * @param \metadigit\core\web\ViewInterface|string $View the View or view name
	 * @throws Exception in case of errors
	 */
	protected function postHandle(Request $Req, Response $Res, $View=null) {
	}
}