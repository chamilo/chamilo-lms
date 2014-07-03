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

namespace JMS\SecurityExtraBundle\Tests\Metadata;

use Doctrine\Common\Annotations\AnnotationReader;

use JMS\SecurityExtraBundle\Metadata\Driver\AnnotationDriver;

use Metadata\MetadataFactory;

class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
    * @expectedException \RuntimeException
    * @expectedMessage You have overridden a secured method "differentMethodSignature" in "SubService". Please copy over the applicable security metadata, and also add @SatisfiesParentSecurityPolicy.
    */
    public function testAnalyzeThrowsExceptionWhenSecureMethodIsOverridden()
    {
        $this->getFactory()->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\SubService');
    }

    public function testAnalyzeThrowsNoExceptionWhenAbstractMethodIsNotOverridenInDirectChildClass()
    {
        $metadata = $this
            ->getFactory()
            ->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\AbstractMethodNotDirectlyOverwrittenInDirectChildService')
        ;

        $this->assertTrue(isset($metadata->methodMetadata['abstractMethod']));

        $metadata = $metadata->methodMetadata['abstractMethod'];
        $this->assertEquals(array('VIEW'), $metadata->returnPermissions);
    }

    public function testAnalyzeThrowsNoExceptionWhenSatisfiesParentSecurityPolicyIsDefined()
    {
        $metadata = $this
            ->getFactory()
            ->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\CorrectSubService')
        ;

        $methods = $metadata->methodMetadata;
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(), $metadata->roles);
        $this->assertEquals(array(), $metadata->paramPermissions);
        $this->assertEquals(array('VIEW'), $metadata->returnPermissions);
    }

    public function testAnalyzeWithComplexHierarchy()
    {
        $metadata = $this
            ->getFactory()
            ->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\ComplexService')
        ;

        $methods = $metadata->methodMetadata;
        $this->assertTrue(isset($methods['delete'], $methods['retrieve'], $methods['abstractMethod']));

        $metadata = $methods['delete'];
        $this->assertEquals(array(0 => array('MASTER', 'EDIT'), 2 => array('OWNER')), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->roles);

        $metadata = $methods['retrieve'];
        $this->assertEquals(array('VIEW', 'UNDELETE'), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->roles);

        $metadata = $methods['abstractMethod'];
        $this->assertEquals(array('ROLE_FOO', 'IS_AUTHENTICATED_FULLY'), $metadata->roles);
        $this->assertEquals(array(1 => array('FOO')), $metadata->paramPermissions);
        $this->assertEquals(array('WOW'), $metadata->returnPermissions);
    }

    public function testAnalyze()
    {
        $metadata = $this
            ->getFactory()
            ->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\MainService')
        ;

        $methods = $metadata->methodMetadata;
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(array('EDIT')), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->roles);
        $this->assertFalse($metadata->isDeclaredOnInterface());
    }

    public function testSerializeUnserialize()
    {
        $metadata = $this
            ->getFactory()
            ->getMetadataForClass('JMS\SecurityExtraBundle\Tests\Fixtures\ComplexService')
        ;

        $this->assertEquals($metadata, unserialize(serialize($metadata)));
    }

    private function getFactory()
    {
        $factory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));
        $factory->setIncludeInterfaces(true);

        return $factory;
    }
}
