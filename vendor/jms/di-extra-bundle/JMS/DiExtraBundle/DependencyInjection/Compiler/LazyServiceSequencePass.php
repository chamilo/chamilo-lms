<?php

namespace JMS\DiExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Serializable;

class LazyServiceSequencePass implements CompilerPassInterface, Serializable
{
    private $tagName;
    private $callable;

    public function __construct($tagName, $callable)
    {
        $this->tagName = $tagName;
        $this->callable = $callable;
    }

    public function process(ContainerBuilder $container)
    {
        if ( ! is_callable($this->callable)) {
            throw new \RuntimeException('The callable is invalid. If you had serialized this pass, the original callable might not be available anymore.');
        }

        $serviceIds = array();
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $attrs) {
            $serviceIds[] = $id;
        }

        $seqDef = new Definition('JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceSequence');
        $seqDef->addArgument(new Reference('service_container'));
        $seqDef->addArgument($serviceIds);

        call_user_func($this->callable, $container, $seqDef);
    }

    public function serialize()
    {
        return $this->tagName;
    }

    public function unserialize($tagName)
    {
        $this->tagName = $tagName;
    }
}