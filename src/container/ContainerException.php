<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
/**
 * ContainerException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContainerException extends \metadigit\core\Exception {
	// runtime
	const COD1 = '%s: object OID "%s" is NOT defined';
	const COD2 = '%s: object OID "%2$s" NOT implementing required class/interface %2$s';
	const COD4 = 'CoreProxy `%s`: can not retrieve proxied object';
	// configuration
	const COD11 = '%s: YAML config file NOT FOUND in path %s';
	const COD12 = 'DI Container: invalid YAML configuration: %s';
}
