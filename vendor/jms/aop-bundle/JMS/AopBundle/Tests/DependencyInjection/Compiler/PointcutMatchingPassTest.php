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

namespace JMS\AopBundle\Tests\DependencyInjection\Compiler;

use JMS\AopBundle\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass;
use JMS\AopBundle\DependencyInjection\JMSAopExtension;
use JMS\AopBundle\DependencyInjection\Compiler\PointcutMatchingPass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PointcutMatchingPassTest extends \PHPUnit_Framework_TestCase
{
    private $cacheDir;
    private $fs;

    public function testProcess()
    {
        $container = $this->getContainer();

        $container
            ->register('pointcut', 'JMS\AopBundle\Tests\DependencyInjection\Compiler\Fixture\LoggingPointcut')
            ->addTag('jms_aop.pointcut', array('interceptor' => 'interceptor'))
        ;
        $container
            ->register('interceptor', 'JMS\AopBundle\Tests\DependencyInjection\Compiler\Fixture\LoggingInterceptor')
        ;
        $container
            ->register('test', 'JMS\AopBundle\Tests\DependencyInjection\Compiler\Fixture\TestService')
        ;

        $this->process($container);

        $service = $container->get('test');
        $this->assertInstanceOf('JMS\AopBundle\Tests\DependencyInjection\Compiler\Fixture\TestService', $service);
        $this->assertTrue($service->add());
        $this->assertTrue($service->delete());
        $this->assertEquals(array('delete'), $container->get('interceptor')->getLog());
    }

    protected function setUp()
    {
        $this->cacheDir = sys_get_temp_dir().'/jms_aop_test';
        $this->fs = new Filesystem();

        if (is_dir($this->cacheDir)) {
            $this->fs->remove($this->cacheDir);
        }

        if (false === @mkdir($this->cacheDir, 0777, true)) {
            throw new RuntimeException(sprintf('Could not create cache dir "%s".', $this->cacheDir));
        }
    }

    protected function tearDown()
    {
        $this->fs->remove($this->cacheDir);
    }

    private function getContainer()
    {
        $container = new ContainerBuilder();

        $extension = new JMSAopExtension();
        $extension->load(array(array(
            'cache_dir' => $this->cacheDir,
        )), $container);

        return $container;
    }

    private function process(ContainerBuilder $container)
    {
        $pass = new ResolveParameterPlaceHoldersPass();
        $pass->process($container);

        $pass = new PointcutMatchingPass();
        $pass->process($container);
    }
}
