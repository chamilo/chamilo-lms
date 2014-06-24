<?php

namespace CG\Tests\Proxy;

use CG\Proxy\LazyInitializerInterface;
use CG\Proxy\InterceptionGenerator;
use CG\Proxy\LazyInitializerGenerator;
use CG\Proxy\Enhancer;
use CG\Tests\Proxy\Fixture\TraceInterceptor;

class EnhancerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getGenerationTests
     */
    public function testGenerateClass($class, $generatedClass, array $interfaces, array $generators)
    {
        $enhancer = new Enhancer(new \ReflectionClass($class), $interfaces, $generators);
        $enhancer->setNamingStrategy($this->getNamingStrategy($generatedClass));

        $this->assertEquals($this->getContent(substr($generatedClass, strrpos($generatedClass, '\\') + 1)), $enhancer->generateClass());
    }

    public function getGenerationTests()
    {
        return array(
            array('CG\Tests\Proxy\Fixture\SimpleClass', 'CG\Tests\Proxy\Fixture\SimpleClass__CG__Enhanced', array('CG\Tests\Proxy\Fixture\MarkerInterface'), array()),
            array('CG\Tests\Proxy\Fixture\SimpleClass', 'CG\Tests\Proxy\Fixture\SimpleClass__CG__Sluggable', array('CG\Tests\Proxy\Fixture\SluggableInterface'), array()),
            array('CG\Tests\Proxy\Fixture\Entity', 'CG\Tests\Proxy\Fixture\Entity__CG__LazyInitializing', array(), array(
                new LazyInitializerGenerator(),
            ))
        );
    }

    public function testInterceptionGenerator()
    {
        $enhancer = new Enhancer(new \ReflectionClass('CG\Tests\Proxy\Fixture\Entity'), array(), array(
            $generator = new InterceptionGenerator()
        ));
        $enhancer->setNamingStrategy($this->getNamingStrategy('CG\Tests\Proxy\Fixture\Entity__CG__Traceable_'.sha1(microtime(true))));
        $generator->setPrefix('');

        $traceable = $enhancer->createInstance();
        $traceable->setLoader($this->getLoader(array(
            $interceptor1 = new TraceInterceptor(),
            $interceptor2 = new TraceInterceptor(),
        )));

        $this->assertEquals('foo', $traceable->getName());
        $this->assertEquals('foo', $traceable->getName());
        $this->assertEquals(2, count($interceptor1->getLog()));
        $this->assertEquals(2, count($interceptor2->getLog()));
    }

    public function testLazyInitializerGenerator()
    {
        $enhancer = new Enhancer(new \ReflectionClass('CG\Tests\Proxy\Fixture\Entity'), array(), array(
            $generator = new LazyInitializerGenerator(),
        ));
        $generator->setPrefix('');

        $entity = $enhancer->createInstance();
        $entity->setLazyInitializer($initializer = new Initializer());
        $this->assertEquals('foo', $entity->getName());
        $this->assertSame($entity, $initializer->getLastObject());
    }

    private function getLoader(array $interceptors)
    {
        $loader = $this->getMock('CG\Proxy\InterceptorLoaderInterface');
        $loader
            ->expects($this->any())
            ->method('loadInterceptors')
            ->will($this->returnValue($interceptors))
        ;

        return $loader;
    }

    private function getContent($file)
    {
        return file_get_contents(__DIR__.'/Fixture/generated/'.$file.'.php.gen');
    }

    private function getNamingStrategy($name)
    {
        $namingStrategy = $this->getMock('CG\Core\NamingStrategyInterface');
        $namingStrategy
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue($name))
        ;

        return $namingStrategy;
    }
}

class Initializer implements LazyInitializerInterface
{
    private $lastObject;

    public function initializeObject($object)
    {
        $this->lastObject = $object;
    }

    public function getLastObject()
    {
        return $this->lastObject;
    }
}