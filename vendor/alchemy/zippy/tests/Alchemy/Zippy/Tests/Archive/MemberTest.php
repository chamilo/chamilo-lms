<?php

namespace Alchemy\Zippy\Tests\Archive;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Archive\Member;
use Alchemy\Zippy\Archive\MemberInterface;

class MemberTest extends TestCase
{
    public function testNewInstance()
    {
        $member = new Member(
            $this->getResource('archive/located/here'),
             $this->getMock('Alchemy\Zippy\Adapter\AdapterInterface'),
            'location',
            1233456,
            new \DateTime("2012-07-08 11:14:15"),
            true
        );

        $this->assertTrue($member instanceof MemberInterface);

        return $member;
    }

    /**
     * @depends testNewInstance
     */
    public function testGetLocation($member)
    {
        $this->assertEquals('location', $member->getLocation());
    }

    /**
     * @depends testNewInstance
     */
    public function testIsDir($member)
    {
        $this->assertTrue($member->isDir());
    }

    /**
     * @depends testNewInstance
     */
    public function testGetLastModifiedDate($member)
    {
        $this->assertEquals(new \DateTime("2012-07-08 11:14:15"), $member->getLastModifiedDate());
    }

    /**
     * @depends testNewInstance
     */
    public function testGetSize($member)
    {
        $this->assertEquals(1233456, $member->getSize());
    }

    /**
     * @depends testNewInstance
     */
    public function testToString($member)
    {
        $this->assertEquals('location', (string) $member);
    }

    public function testExtract()
    {
        $mockAdapter =  $this->getMock('Alchemy\Zippy\Adapter\AdapterInterface');

        $mockAdapter
            ->expects($this->any())
            ->method('extractMembers');

        $member = new Member(
           $this->getResource('archive/located/here'),
           $mockAdapter,
           '/member/located/here',
           1233456,
           new \DateTime("2012-07-08 11:14:15"),
           true
        );

        $file = $member->extract();
        $this->assertEquals(sprintf('%s%s', getcwd(), '/member/located/here'), $file->getPathname());

        $file = $member->extract('/custom/location');
        $this->assertEquals('/custom/location/member/located/here', $file->getPathname());
    }
}
