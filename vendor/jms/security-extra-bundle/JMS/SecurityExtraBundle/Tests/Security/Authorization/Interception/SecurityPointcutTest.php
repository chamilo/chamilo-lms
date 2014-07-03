<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception;

use JMS\SecurityExtraBundle\Security\Authorization\Interception\SecurityPointcut;

class SecurityPointcutTest extends \PHPUnit_Framework_TestCase
{
    private $metadataFactory;

    public function testMatchesAllClassesIfNotExplicitlyGiven()
    {
        $pointcut = new SecurityPointcut($this->metadataFactory, false, array(
            'Foo::bar' => 'foo',
            'login$'   => 'foo',
        ));

        $this->assertTrue($pointcut->matchesClass(new \ReflectionClass('stdClass')));
    }

    public function testMatchesClassReturnsFalseForControllerNotation()
    {
        $pointcut = new SecurityPointcut($this->metadataFactory, false, array(
            'AcmeFooBundle:Foo:foo' => 'foo',
        ));

        $this->assertFalse($pointcut->matchesClass(new \ReflectionClass('stdClass')));
    }

    protected function setUp()
    {
        $this->metadataFactory = $this->getMock('Metadata\MetadataFactoryInterface');
    }
}