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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use JMS\DiExtraBundle\DependencyInjection\Compiler\ResourceOptimizationPass;
use Symfony\Component\Config\Resource\DirectoryResource;

class ResourceOptimizationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setParameter('jms_di_extra.disable_grep', false);
        $container->addResource(new DirectoryResource(__DIR__.'/Fixtures/a'));
        $container->addResource(new DirectoryResource(__DIR__.'/Fixtures/a/b'));
        $container->addResource(new DirectoryResource(__DIR__.'/Fixtures/c'));
        $this->process($container);

        $resources = $container->getResources();
        $this->assertEquals(1, count($resources));
        $this->assertInstanceOf('JMS\DiExtraBundle\Config\FastDirectoriesResource', $resources[0]);
        $this->assertEquals(array(
            __DIR__.'/Fixtures/a',
            __DIR__.'/Fixtures/c'
        ), $resources[0]->getResource());
        $this->assertAttributeEquals('*', 'filePattern', $resources[0]);
    }

    private function process(ContainerBuilder $container)
    {
        $pass = new ResourceOptimizationPass();
        $pass->process($container);
    }
}
