<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\reflection;

class ReflectionClass extends \ReflectionClass {

	/** DocComment instance
	 * @var DocComment */
	protected $DocComment;

	/**
	 * Replacement for the original getMethods() method which makes sure
	 * that org\metadigit\util\reflection\ReflectionMethod objects are returned instead of the
	 * original ReflectionMethod instances.
	 * @param integer $filter A filter mask
	 * @return array of org\metadigit\util\reflection\ReflectionMethod Method reflection objects of the methods in this class
	 */
	function getMethods($filter = NULL) {
		$extendedMethods = [];
		$methods = ($filter === NULL ? parent::getMethods() : parent::getMethods($filter));
		foreach ($methods as $method) {
			$extendedMethods[] = new ReflectionMethod($this->getName(), $method->getName());
		}
		return $extendedMethods;
	}

	/**
	 * Replacement for the original getMethod() method which makes sure
	 * that org\metadigit\util\reflection\ReflectionMethod objects are returned instead of the
	 * original ReflectionMethod instances.
	 * @param string $name
	 * @return ReflectionMethod Method reflection object of the named method
	 */
	function getMethod($name) {
		$parentMethod = parent::getMethod($name);
		if (!is_object($parentMethod)) return $parentMethod;
		return new ReflectionMethod($this->getName(), $parentMethod->getName());
	}

	/**
	 * Replacement for the original getProperties() method which makes sure
	 * that org\metadigit\util\reflection\ReflectionProperty objects are returned instead of the
	 * original ReflectionProperty instances.
	 * @param integer $filter A filter mask
	 * @return array of org\metadigit\util\reflection\ReflectionProperty Property reflection objects of the properties in this class
	 */
	function getProperties($filter = NULL) {
		$extendedProperties = [];
		$properties = ($filter === NULL ? parent::getProperties() : parent::getProperties($filter));
		foreach ($properties as $property) {
			$extendedProperties[] = new ReflectionProperty($this->getName(), $property->getName());
		}
		return $extendedProperties;
	}

	/**
	 * Checks if the doc comment of this method is tagged with the specified tag
	 * @param string $tagName Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 */
	function hasTag($tagName) {
		return $this->getDocComment()->hasTag($tagName);
	}

	/**
	 * Returns an array of tags and their values
	 * @return array Tags and values
	 */
	function getAllTags() {
		return $this->getDocComment()->getAllTags();
	}

	/**
	 * Return tag values at specified index, can be NULL if not exists
	 * @param $tagName
	 * @param int $index index of tag values array
	 * @return mixed|null
	 */
	function getTag($tagName, $index) {
		return $this->getDocComment()->getTag($tagName, $index);
	}

	/**
	 * Returns the values of the specified tag
	 * @param string $tagName Tag name to check for
	 * @return array Values of the given tag
	 */
	function getTagValues($tagName) {
		return $this->getDocComment()->getTagValues($tagName);
	}

	/**
	 * Returns an instance of the DocComment
	 * @return DocComment
	 */
	function getDocComment() {
		if (!is_object($this->DocComment)) {
			$this->DocComment = new DocComment(parent::getDocComment());
		}
		return $this->DocComment;
	}
}