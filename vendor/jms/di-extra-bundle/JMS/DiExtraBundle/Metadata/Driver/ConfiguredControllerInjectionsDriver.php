<?php

namespace JMS\DiExtraBundle\Metadata\Driver;

use JMS\DiExtraBundle\Metadata\ClassMetadata;

use Metadata\Driver\DriverInterface;

class ConfiguredControllerInjectionsDriver implements DriverInterface
{
    private $delegate;
    private $propertyInjections;
    private $methodInjections;

    public function __construct(DriverInterface $driver, array $propertyInjections, array $methodInjections)
    {
        $this->delegate = $driver;
        $this->propertyInjections = $propertyInjections;
        $this->methodInjections = $methodInjections;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $metadata = $this->delegate->loadMetadataForClass($class);

        if (!preg_match('/Controller\\\(.+)Controller$/', $class->name)) {
            return $metadata;
        }

        if (null === $metadata) {
            $metadata = new ClassMetadata($class->name);
        }

        foreach ($metadata->reflection->getProperties() as $property) {
            // explicit injection configured?
            if (isset($metadata->properties[$property->name])) {
                continue;
            }

            // automatic injection configured?
            if (!isset($this->propertyInjections[$property->name])) {
                continue;
            }

            if ($property->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $metadata->properties[$property->name] = $this->propertyInjections[$property->name];
        }

        foreach ($metadata->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            // explicit injection configured?
            foreach ($metadata->methodCalls as $call) {
                if ($call[0] === $method->name) {
                    continue 2;
                }
            }

            // automatic injection configured?
            if (!isset($this->methodInjections[$method->name])) {
                continue;
            }

            if ($method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $metadata->methodCalls[] = array($method->name, $this->methodInjections[$method->name]);
        }

        return $metadata->properties || $metadata->methodCalls || $metadata->lookupMethods ? $metadata : null;
    }
}
