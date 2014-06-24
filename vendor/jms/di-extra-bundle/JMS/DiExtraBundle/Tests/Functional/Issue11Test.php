<?php

namespace JMS\DiExtraBundle\Tests\Functional;

class Issue11Test extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testConstructorInjectionWithInheritance()
    {
        $this->createClient();

        $container = self::$kernel->getContainer();
        $foo = $container->get('foo');
        $bar = $container->get('bar');
        $templating = $container->get('templating');

        $concreteService = $container->get('concrete_class');
        $this->assertSame($templating, $concreteService->getTemplating());
        $this->assertSame($foo, $concreteService->getFoo());
        $this->assertSame($bar, $concreteService->getBar());
    }
}
