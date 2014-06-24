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

namespace JMS\SecurityExtraBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use JMS\SecurityExtraBundle\DependencyInjection\JMSSecurityExtraExtension;

class JMSSecurityExtraExtensionTest extends \PHPUnit_Framework_TestCase
{
    private $extension;

    public function testConfigLoad()
    {
        $config = array();
        $this->extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('security.access.method_interceptor'));
        $this->assertFalse($container->getParameter('security.access.secure_all_services'));
        $this->assertFalse($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }

    public function testConfigLoadSecureAll()
    {
        $this->extension->load(array(array('secure_all_services' => true)),
            $container = $this->getContainer());

        $this->assertTrue($container->getParameter('security.access.secure_all_services'));
    }

    public function testConfigLoadEnableIddqdAttribute()
    {
        $this->extension->load(array(array('enable_iddqd_attribute' => true)),
            $container = $this->getContainer());

        $this->assertTrue($container->getDefinition('security.extra.iddqd_voter')->hasTag('security.voter'));
    }

    public function testConfigLoadWithMethodAccessControl()
    {
        $this->extension->load(array(array(
            'expressions' => true,
            'method_access_control' => array(
                ':login$' => 'hasRole("FOO")',
            )
        )), $container = $this->getContainer());

        $this->assertEquals(array(':login$' => 'hasRole("FOO")'),
            $container->getParameter('security.access.method_access_control'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testConfigLoadThrowsExceptionWhenMethodAccessControlWithoutExpressions()
    {
        $this->extension->load(array(array(
            'expressions' => false,
            'method_access_control' => array('foo' => 'bar'),
        )), $this->getContainer());
    }

    protected function setUp()
    {
        $this->extension = new JMSSecurityExtraExtension();
    }

    private function getContainer()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.bundles', array('JMSAopBundle' => 'JMS\AopBundle\JMSAopBundle'));

        return $container;
    }
}
