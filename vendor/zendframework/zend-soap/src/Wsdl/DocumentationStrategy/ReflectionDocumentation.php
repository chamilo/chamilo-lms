<?php
/**
 * @see       https://github.com/zendframework/zend-soap for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-soap/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Soap\Wsdl\DocumentationStrategy;

use ReflectionClass;
use ReflectionProperty;

final class ReflectionDocumentation implements DocumentationStrategyInterface
{
    /**
     * @return string
     */
    public function getPropertyDocumentation(ReflectionProperty $property)
    {
        return $this->parseDocComment($property->getDocComment());
    }

    /**
     * @return string
     */
    public function getComplexTypeDocumentation(ReflectionClass $class)
    {
        return $this->parseDocComment($class->getDocComment());
    }

    /**
     * @param string $docComment
     * @return string
     */
    private function parseDocComment($docComment)
    {
        $documentation = [];
        foreach (explode("\n", $docComment) as $i => $line) {
            if ($i == 0) {
                continue;
            }

            $line = trim(preg_replace('/\s*\*+/', '', $line));
            if (preg_match('/^(@[a-z]|\/)/i', $line)) {
                break;
            }

            // only include newlines if we've already got documentation
            if (! empty($documentation) || $line != '') {
                $documentation[] = $line;
            }
        }

        return join("\n", $documentation);
    }
}
