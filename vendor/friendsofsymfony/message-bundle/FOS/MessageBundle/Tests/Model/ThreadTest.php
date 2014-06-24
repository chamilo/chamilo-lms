<?php

namespace FOS\MessageBundle\Tests\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

class ThreadTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOtherParticipants()
    {
        $u1 = $this->createParticipantMock('u1');
        $u2 = $this->createParticipantMock('u2');
        $u3 = $this->createParticipantMock('u3');

        $thread = $this->getMockForAbstractClass('FOS\MessageBundle\Model\Thread');
        $thread->expects($this->atLeastOnce())
            ->method('getParticipants')
            ->will($this->returnValue(array($u1, $u2, $u3)));

        $toIds = function(array $participants) {
            return array_map(function (ParticipantInterface $participant) {
                return $participant->getId();
            }, $participants);
        };

        $this->assertSame($toIds(array($u2, $u3)), $toIds($thread->getOtherParticipants($u1)));
        $this->assertSame($toIds(array($u1, $u3)), $toIds($thread->getOtherParticipants($u2)));
        $this->assertSame($toIds(array($u1, $u2)), $toIds($thread->getOtherParticipants($u3)));
    }

    protected function createParticipantMock($id)
    {
        $participant = $this->getMockBuilder('FOS\MessageBundle\Model\ParticipantInterface')
            ->disableOriginalConstructor(true)
            ->getMock();

        $participant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $participant;
    }
}
