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

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use JMS\SecurityExtraBundle\DependencyInjection\Compiler\CollectSecuredServicesPass;

class CollectSecuredServicesPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('security.access.pointcut', 'JMS\SecurityExtraBundle\Security\Authorization\Interception\SecurityPointcut')
        ;

        $container
            ->register('a', 'stdClass')
            ->addTag('security.secure_service')
        ;
        $container
            ->register('b', 'stdClass')
            ->addTag('security.secure_service')
        ;

        $pass = new CollectSecuredServicesPass();
        $pass->process($container);

        $this->assertEquals(array(
            array('setSecuredClasses', array(array('stdClass', 'stdClass'))),
        ), $container->getDefinition('security.access.pointcut')->getMethodCalls());
    }
}