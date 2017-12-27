<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
use const metadigit\core\ACL_ROUTES;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\view\FileView,
	metadigit\core\http\view\CsvView,
	metadigit\core\http\view\ExcelView,
	metadigit\core\http\view\JsonView,
	metadigit\core\http\view\PhpView,
	metadigit\core\http\view\PhpTALView,
//	metadigit\core\http\view\SmartyView,
//	metadigit\core\http\view\TwigView,
	metadigit\core\http\view\XSendFileView,
	metadigit\core\trace\Tracer;
/**
 * High speed implementation of HTTP Dispatcher based on URLs.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Dispatcher {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** Array of routes between Request URLs and Controllers names.
	 * @var array */
	protected $routes = [];
	/** customizable templates dir path, default to \metadigit\core\PUBLIC_DIR
	 * @var string */
	protected $resourcesDir = \metadigit\core\PUBLIC_DIR;
	/** default View engine
	 * @var string */
	protected $viewEngine = null;
	/** View engines mapping
	 * @var array */
	protected $viewEngines = [
		ENGINE_FILE			=> FileView::class,
		ENGINE_FILE_CSV		=> CsvView::class,
		ENGINE_FILE_EXCEL	=> ExcelView::class,
		ENGINE_JSON			=> JsonView::class,
		ENGINE_PHP			=> PhpView::class,
		ENGINE_PHP_TAL		=> PhpTALView::class,
//		ENGINE_SMARTY		=> SmartyView::class,
//		ENGINE_TWIG			=> TwigView::class,
		ENGINE_X_SEND_FILE	=> XSendFileView::class
	];

	function dispatch(Request $Req, Response $Res) {
		$Controller = null;
		$DispatcherEvent = new DispatcherEvent($Req, $Res);
		try {
			if(!sys::event(DispatcherEvent::EVENT_ROUTE, $DispatcherEvent)->isPropagationStopped()) {
				ACL_ROUTES and sys::acl()->onRoute($Req, defined('SESSION_UID')? SESSION_UID : null);
				$Controller = sys::context()->get($this->doRoute($Req), ControllerInterface::class);
				$DispatcherEvent->setController($Controller);
			}
			if($Controller) {
				$Res->setView(null, null, $this->viewEngine);
				if(!sys::event(DispatcherEvent::EVENT_CONTROLLER, $DispatcherEvent)->isPropagationStopped()) {
					$Controller->handle($Req, $Res);
				}
			}
			list($View, $viewResource, $viewOptions) = $this->resolveView($Req, $Res, $DispatcherEvent);
			if($View) {
				if(!sys::event(DispatcherEvent::EVENT_VIEW, $DispatcherEvent)->isPropagationStopped()) {
					$View->render($Req, $Res, $viewResource, $viewOptions);
				}
			}
			sys::event(DispatcherEvent::EVENT_RESPONSE, $DispatcherEvent);
		} catch(\Exception $Ex) {
			$DispatcherEvent->setException($Ex);
			sys::event(DispatcherEvent::EVENT_EXCEPTION, $DispatcherEvent);
			if(200 == http_response_code()) http_response_code(500);
			Tracer::onException($Ex);
		}
		$Res->send();
	}

	/**
	 * Resolve configured Controller to handle current Request
	 * @param Request $Req
	 * @return string Controller ID
	 * @throws Exception
	 */
	protected function doRoute(Request $Req) {
		foreach($this->routes as $url => $controllerID) {
			if(fnmatch($url, $Req->getAttribute('APP_URI'))) {
				sys::trace(LOG_DEBUG, T_INFO, 'matched URL: '.$url.' => Controller: '.$controllerID, null, $this->_.'->'.__FUNCTION__);
				$Req->setAttribute('APP_CONTROLLER', $controllerID);
				return $controllerID;
			}
		}
		http_response_code(404);
		throw new Exception(11, [$Req->getAttribute('APP_URI')]);
	}

	/**
	 * Resolve View name into an instantiated View object with template
	 * @param Request $Req
	 * @param Response $Res
	 * @param DispatcherEvent $DispatcherEvent
	 * @return array $View, $resource, $viewOptions
	 * @throws \Exception
	 */
	protected function resolveView(Request $Req, Response $Res, DispatcherEvent $DispatcherEvent) {
		try {
			list($view, $viewOptions, $viewEngine) = $Res->getView() ?: $DispatcherEvent->getView();
			if(!$viewEngine) return [null, null, null];
			// detect View class
			$viewClass = (array_key_exists($viewEngine, $this->viewEngines)) ? $this->viewEngines[$viewEngine] : $viewEngine;
			if(!class_exists($viewClass) || $viewClass instanceof ViewInterface) throw new Exception(12, $viewEngine);
			$View = new $viewClass;
			$DispatcherEvent->setView($View);
			// detect resource
			if(!empty($view)) {
				$resource = str_replace('//','/', (substr($view,0,1) != '/' ) ? dirname($Req->getAttribute('APP_URI').'*').'/'.$view : $view);
				$Req->setAttribute('RESOURCES_DIR', rtrim(preg_replace('/[\w-]+\/\.\.\//', '', (substr($this->resourcesDir,0,1) != '/' ) ? $Req->getAttribute('APP_DIR').$this->resourcesDir : $this->resourcesDir), '/'));
			} else $resource = null;
			sys::trace(LOG_DEBUG, T_INFO, sprintf('view "%s", resource "%s"', $view, $resource), null, $this->_.'->'.__FUNCTION__);
			return [$View, $resource, $viewOptions];
		} catch (\Exception $Ex) {
			http_response_code(500);
			throw $Ex;
		}
	}
}
