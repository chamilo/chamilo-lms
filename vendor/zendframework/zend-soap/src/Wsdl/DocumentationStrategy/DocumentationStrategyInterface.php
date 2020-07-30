<?php
/**
 * @see       https://github.com/zendframework/zend-soap for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-soap/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Soap\Wsdl\DocumentationStrategy;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implement this interface to provide contents for <xsd:documentation> elements on complex types
 */
interface DocumentationStrategyInterface
{
    /**
     * Returns documentation for complex type property
     *
     * @param ReflectionProperty $property
     * @return string
     */
    public function getPropertyDocumentation(ReflectionProperty $property);

    /**
     * Returns documentation for complex type
     *
     * @param ReflectionClass $class
     * @return string
     */
    public function getComplexTypeDocumentation(ReflectionClass $class);
}
