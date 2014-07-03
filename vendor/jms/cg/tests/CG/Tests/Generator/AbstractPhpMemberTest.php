<?php

namespace CG\Tests\Generator;

class AbstractPhpMemberTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetStatic()
    {
        $member = $this->getMember();

        $this->assertFalse($member->isStatic());
        $this->assertSame($member, $member->setStatic(true));
        $this->assertTrue($member->isStatic());
        $this->assertSame($member, $member->setStatic(false));
        $this->assertFalse($member->isStatic());
    }

    public function testSetGetVisibility()
    {
        $member = $this->getMember();

        $this->assertEquals('public', $member->getVisibility());
        $this->assertSame($member, $member->setVisibility('private'));
        $this->assertEquals('private', $member->getVisibility());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetVisibilityThrowsExOnInvalidValue()
    {
        $member = $this->getMember();
        $member->setVisibility('foo');
    }

    public function testSetGetName()
    {
        $member = $this->getMember();

        $this->assertNull($member->getName());
        $this->assertSame($member, $member->setName('foo'));
        $this->assertEquals('foo', $member->getName());
    }

    public function testSetGetDocblock()
    {
        $member = $this->getMember();

        $this->assertNull($member->getDocblock());
        $this->assertSame($member, $member->setDocblock('foo'));
        $this->assertEquals('foo', $member->getDocblock());
    }

    private function getMember()
    {
        return $this->getMockForAbstractClass('CG\Generator\AbstractPhpMember');
    }
}