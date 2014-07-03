<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\DiExtraBundle\DependencyInjection\Compiler\AnnotationConfigurationPass;
use JMS\DiExtraBundle\DependencyInjection\JMSDiExtraExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

class AnnotationConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = $this->getContainer(array(), array(
            __DIR__.'/../../Fixture/',
        ));
        $container->set('doctrine.entity_manager', $em = new \stdClass);
        $container->set('session', $session = new \stdClass);
        $container->set('database_connection', $dbCon = new \stdClass);
        $container->set('router', $router = new \stdClass);
        $container->setParameter('table_name', 'foo');
        $this->process($container);

        $this->assertTrue($container->hasDefinition('j_m_s.di_extra_bundle.tests.fixture.request_listener'));
        $service = $container->get('j_m_s.di_extra_bundle.tests.fixture.request_listener');
        $this->assertAttributeEquals($em, 'em', $service);
        $this->assertAttributeEquals($session, 'session', $service);
        $this->assertAttributeEquals($dbCon, 'con', $service);
        $this->assertAttributeEquals($router, 'router', $service);
        $this->assertAttributeEquals('foo', 'table', $service);
    }

    public function testProcessValidator()
    {
        $container = $this->getContainer(array(), array(
            __DIR__.'/../../Fixture/Validator',
        ));
        $container->set('foo', $foo = new \stdClass);
        $this->process($container);

        $this->assertTrue($container->hasDefinition('j_m_s.di_extra_bundle.tests.fixture.validator.validator'));

        $def = $container->getDefinition('j_m_s.di_extra_bundle.tests.fixture.validator.validator');
        $this->assertEquals(array(
            'validator.constraint_validator' => array(
                array('alias' => 'foobar'),
            )
        ), $def->getTags());

        $v = $container->get('j_m_s.di_extra_bundle.tests.fixture.validator.validator');
        $this->assertAttributeEquals($foo, 'foo', $v);
    }

    public function testConstructorWithInheritance()
    {
        $container = $this->getContainer(array(), array(
            __DIR__.'/../../Functional/Bundle/TestBundle/Inheritance',
        ));
        $container->set('foo', $foo = new \stdClass);
        $container->set('bar', $bar = new \stdClass);
        $this->process($container);

        $this->assertTrue($container->hasDefinition('concrete_class'));
        $this->assertTrue($container->hasDefinition('abstract_class'));

        $def = new DefinitionDecorator('abstract_class');
        $def->setClass('JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Inheritance\ConcreteClass');
        $def->addArgument(new Reference('foo'));
        $def->addArgument(new Reference('bar'));

        $this->assertEquals($def, $container->getDefinition('concrete_class'));
    }

    protected function setUp()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSDiExtraBundle-Test-AnnotationCFG');
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSDiExtraBundle-Test-AnnotationCFG');
    }

    private function getContainer(array $bundles = array(), array $directories = array())
    {
        $container = new ContainerBuilder();
        $container->set('annotation_reader', new AnnotationReader());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir().'/JMSDiExtraBundle-Test-AnnotationCFG');

        $extension = new JMSDiExtraExtension();
        $extension->load(array(array(
            'locations' => array(
                'bundles' => $bundles,
                'directories' => $directories,
            ),
            'metadata' => array(
                'cache' => 'none',
            )
        )), $container);

        return $container;
    }

    private function process(ContainerBuilder $container, array $bundles = array())
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue($bundles))
        ;

        $pass = new AnnotationConfigurationPass($kernel);
        $pass->process($container);
    }
}
