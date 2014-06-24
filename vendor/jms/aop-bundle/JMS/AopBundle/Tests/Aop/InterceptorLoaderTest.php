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

namespace JMS\AopBundle\Tests\Aop;

use JMS\AopBundle\Aop\InterceptorLoader;

class InterceptorLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadInterceptors()
    {
        $interceptor = $this->getMock('CG\Proxy\MethodInterceptorInterface');

        list($loader, $container) = $this->getLoader(array(
            'JMS\AopBundle\Tests\Aop\InterceptorLoaderTestClass' => array(
                'foo' => array('foo'),
            ),
        ));

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue($interceptor))
        ;

        $method = new \ReflectionMethod('JMS\AopBundle\Tests\Aop\InterceptorLoaderTestClass', 'foo');

        $this->assertSame(array($interceptor), $loader->loadInterceptors($method));
        // yes, twice
        $this->assertSame(array($interceptor), $loader->loadInterceptors($method));
    }

    private function getLoader(array $interceptors = array())
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        return array(new InterceptorLoader($container, $interceptors), $container);
    }
}

class InterceptorLoaderTestClass
{
    public function foo()
    {
    }
}
