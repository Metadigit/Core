<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
/**
 * ContextException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContextException extends \metadigit\core\Exception {
	// runtime Container
	const COD1 = '%s: object OID "%s" is NOT defined';
	const COD2 = '%1$s: object OID "%2$s" NOT implementing required class/interface %2$s';
	// configuration
	const COD11 = '%s: XML config file NOT FOUND in path %s';
	const COD12 = 'Context: invalid XML configuration, XSD not validated: %s';
	const COD13 = '%s: invalid context namespace in XML: namespace=%s';
	const COD14 = '%s: invalid object ID namespace in XML: <object id="%s">, must be inside namespace "%s"';
	const COD15 = '%s: invalid object constructor reference: <arg name="%s" type="object">%s</arg>, must be inside available namespaces: %s';
	const COD16 = '%s: invalid object property reference: <property name="%s" type="object">%s</property>, must be inside available namespaces: %s';
}
