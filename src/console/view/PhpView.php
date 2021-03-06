<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\view;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\Exception;
/**
 * Php template View.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PhpView implements \metadigit\core\console\ViewInterface {
	use \metadigit\core\CoreTrait;

	/** template suffix */
	const TEMPLATE_SUFFIX = '.phtml';
	/** Model array
	 * @var array */
	static private $model;
	/** php template path
	 * @var string */
	static private $template;

	function render(Request $Req, Response $Res, $resource) {
		self::$template = $Req->getAttribute('RESOURCES_DIR').$resource.static::TEMPLATE_SUFFIX;
		if(!file_exists(self::$template)) throw new Exception(201, ['PHP Template', self::$template]);
		$this->trace(LOG_DEBUG, 1, __FUNCTION__, 'render PHP template '.self::$template);
		self::$model = $Res->getData();
		self::execTemplate();
	}

	/**
	 * Push templates variables into scope
	 * and include php template
	 * @return void
	 */
	static private function execTemplate() {
		extract(self::$model, EXTR_REFS);
		include(self::$template);
	}
}
