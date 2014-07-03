<?php

namespace JMS\SecurityExtraBundle\Metadata\Driver;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;

/**
 * Uses Symfony2 DI configuration for metadata.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ConfigDriver implements DriverInterface
{
    private $bundles;
    private $config;

    public function __construct(array $bundles, array $config)
    {
        uasort($bundles, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($bundles as $name => $namespace) {
            $bundles[$name] = substr($namespace, 0, strrpos($namespace, '\\'));
        }

        $this->bundles = $bundles;
        $this->config = $config;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = new ClassMetadata($class->name);

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED) as $method) {
            if ($method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $expression = null;
            if (null !== $notation = $this->getControllerNotation($method)) {
                $expression = $this->getExpressionForSignature($notation);
            }

            if (null === $expression && null === $expression =
                    $this->getExpressionForSignature($method->class.'::'.$method->name)) {
                continue;
            }

            $methodMetadata = new MethodMetadata($method->class, $method->name);
            $methodMetadata->roles = array(new Expression($expression));
            $metadata->addMethodMetadata($methodMetadata);
        }

        if (!$metadata->methodMetadata) {
            return null;
        }

        return $metadata;
    }

    private function getExpressionForSignature($signature)
    {
        foreach ($this->config as $pattern => $expr) {
            if (!preg_match('#'.$pattern.'#i', $signature)) {
                continue;
            }

            return $expr;
        }

        return null;
    }

    // TODO: Is it feasible to reverse-engineer the notation for service controllers?
    private function getControllerNotation(\ReflectionMethod $method)
    {
        $signature = $method->class.'::'.$method->name;

        // check if class is a controller
        if (0 === preg_match('#\\\\Controller\\\\([^\\\\]+)Controller::(.+)Action$#', $signature, $match)) {
            return null;
        }

        foreach ($this->bundles as $name => $namespace) {
            if (0 !== strpos($method->class, $namespace)) {
                continue;
            }

            // controller notation (AcmeBundle:Foo:foo)
            return $name.':'.$match[1].':'.$match[2];
        }

        return null;
    }
}
