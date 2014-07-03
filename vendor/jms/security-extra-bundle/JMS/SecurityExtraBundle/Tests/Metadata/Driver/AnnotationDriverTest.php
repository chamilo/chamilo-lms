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

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Metadata\Driver\AnnotationDriver;

require_once __DIR__.'/Fixtures/services.php';

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataWithClassPreAuthorize()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\Controller\AllActionsSecuredController'));
        $this->assertEquals(array('fooAction', 'barAction', 'bazAction'), array_keys($metadata->methodMetadata));
        $this->assertEquals(array(new Expression("hasRole('foo')")), $metadata->methodMetadata['fooAction']->roles);
        $this->assertEquals(array(new Expression("hasRole('foo')")), $metadata->methodMetadata['barAction']->roles);
        $this->assertEquals(array(new Expression("hasRole('bar')")), $metadata->methodMetadata['bazAction']->roles);
    }

    public function testLoadMetadataWithClassSecureParam()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooSecureService'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array(), $method->roles);
        $this->assertEquals(array(), $method->returnPermissions);
        $this->assertEquals(array(0 => array('VIEW'), 1 => array('EDIT')), $method->paramPermissions);

        $this->assertTrue(isset($metadata->methodMetadata['baz']));
        $method = $metadata->methodMetadata['baz'];
        $this->assertEquals(array(), $method->roles);
        $this->assertEquals(array(), $method->returnPermissions);
        $this->assertEquals(array(0 => array('VIEW')), $method->paramPermissions);

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooMultipleSecureService'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array(), $method->roles);
        $this->assertEquals(array(), $method->returnPermissions);
        $this->assertEquals(array(0 => array('VIEW'), 1 => array('EDIT')), $method->paramPermissions);
    }

    public function testLoadMetadataFromClass()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'), $method->roles);
        $this->assertEquals(array(), $method->returnPermissions);
        $this->assertEquals(array(0 => array('VIEW')), $method->paramPermissions);

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooInterface'));
        $this->assertTrue(isset($metadata->methodMetadata['foo']));
        $method = $metadata->methodMetadata['foo'];
        $this->assertEquals(array(), $method->roles);
        $this->assertEquals(array(0 => array('OWNER'), 1 => array('EDIT')), $method->paramPermissions);
        $this->assertEquals(array('MASTER'), $method->returnPermissions);
    }

    public function testLoadMetadataFromClassWithShortNotation()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue(isset($metadata->methodMetadata['shortNotation']));
        $method = $metadata->methodMetadata['shortNotation'];
        $this->assertEquals(array('ROLE_FOO', 'ROLE_BAR'), $method->roles);
    }        

    public function testLoadMetadataFromClassDoesNotProcessMethodsForWhichNoSecurityMetadataExists()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Fixtures\MainService'));
        $this->assertTrue(class_exists('JMS\SecurityExtraBundle\Tests\Fixtures\Annotation\NonSecurityAnnotation', false));
        $this->assertFalse(isset($metadata->methodMetadata['foo']));
    }
    
    public function testLoadMetadataFromClassWithRolesAndPermissionsArrayNotation()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Mapping\Driver\FooService'));
        $this->assertTrue(isset($metadata->methodMetadata['bar']));
        $method = $metadata->methodMetadata['bar'];
        $this->assertEquals(array('ROLE_FOO', 'ROLE_BAR'), $method->roles);        
        $this->assertEquals(array(0 => array('OWNER')), $method->paramPermissions);        
        $this->assertEquals(array('MASTER'), $method->returnPermissions);
    }
}