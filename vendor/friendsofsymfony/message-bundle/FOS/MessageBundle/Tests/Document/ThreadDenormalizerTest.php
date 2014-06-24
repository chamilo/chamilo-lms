<?php

namespace FOS\MessageBundle\Document;

use DateTime;
use FOS\MessageBundle\Model\ParticipantInterface;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\MessageBundle\Model\MessageInterface;

class ThreadDenormalizerTest extends \PHPUnit_Framework_TestCase
{
    protected $dates;

    protected function setUp()
    {
        $this->markTestIncomplete('Broken, needs to be fixed');

        $this->dates = array(
            new DateTime('- 3 days'),
            new DateTime('- 2 days'),
            new DateTime('- 1 days'),
            new DateTime('- 1 hour')
        );
    }

    public function testDenormalize()
    {
        $thread = new TestThread();
        $user1 = $this->createParticipantMock('u1');
        $user2 = $this->createParticipantMock('u2');

        /**
         * First message
         */
        $message = $this->createMessageMock($user1, $user2, $this->dates[0]);
        $thread->setSubject('Test thread subject');
        $thread->addParticipant($user2);
        $thread->addMessage($message);

        $this->assertSame(array($user1, $user2), $thread->getParticipants());
        $this->assertSame(array('u2' => $this->dates[0]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByOtherParticipant());
        $this->assertSame(array('u1' => $this->dates[0]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByParticipant());

        /**
         * Second message
         */
        $message = $this->createMessageMock($user2, $user1, $this->dates[1]);
        $thread->addMessage($message);

        $this->assertSame(array($user1, $user2), $thread->getParticipants());
        $this->assertSame(array('u1' => $this->dates[1]->getTimestamp(), 'u2' => $this->dates[0]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByOtherParticipant());
        $this->assertSame(array('u1' => $this->dates[0]->getTimestamp(), 'u2' => $this->dates[1]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByParticipant());

        /**
         * Third message
         */
        $message = $this->createMessageMock($user2, $user1, $this->dates[2]);
        $thread->addMessage($message);

        $this->assertSame(array($user1, $user2), $thread->getParticipants());
        $this->assertSame(array('u1' => $this->dates[2]->getTimestamp(), 'u2' => $this->dates[0]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByOtherParticipant());
        $this->assertSame(array('u1' => $this->dates[0]->getTimestamp(), 'u2' => $this->dates[2]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByParticipant());

        /**
         * Fourth message
         */
        $message = $this->createMessageMock($user1, $user2, $this->dates[3]);
        $thread->addMessage($message);

        $this->assertSame(array($user1, $user2), $thread->getParticipants());
        $this->assertSame(array('u1' => $this->dates[2]->getTimestamp(), 'u2' => $this->dates[3]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByOtherParticipant());
        $this->assertSame(array('u1' => $this->dates[3]->getTimestamp(), 'u2' => $this->dates[2]->getTimestamp()), $thread->getDatesOfLastMessageWrittenByParticipant());

        $this->assertEquals('test thread subject hi dude', $thread->getKeywords());
        $this->assertSame(array('u1' => false, 'u2' => false), $thread->getIsDeletedByParticipant());
    }

    protected function createMessageMock($sender, $recipient, DateTime $date)
    {
        $message = $this->getMockBuilder('FOS\MessageBundle\Document\Message')
            ->getMock();

        $message->expects($this->atLeastOnce())
            ->method('getSender')
            ->will($this->returnValue($sender));
        $message->expects($this->atLeastOnce())
            ->method('getTimestamp')
            ->will($this->returnValue($date->getTimestamp()));
        $message->expects($this->atLeastOnce())
            ->method('ensureIsReadByParticipant');
        $message->expects($this->atLeastOnce())
            ->method('getBody')
            ->will($this->returnValue('hi dude'));

        return $message;
    }

    protected function createParticipantMock($id)
    {
        $user = $this->getMockBuilder('FOS\MessageBundle\Model\ParticipantInterface')
            ->disableOriginalConstructor(true)
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $user;
    }
}

class TestThread extends Thread
{
    public function getDatesOfLastMessageWrittenByParticipant()
    {
        return $this->datesOfLastMessageWrittenByParticipant;
    }

    public function getDatesOfLastMessageWrittenByOtherParticipant()
    {
        return $this->datesOfLastMessageWrittenByOtherParticipant;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getIsDeletedByParticipant()
    {
        return $this->isDeletedByParticipant;
    }

    public function addMessage(MessageInterface $message)
    {
        parent::addMessage($message);

        $this->sortDenormalizedProperties();
    }

    /**
     * Sort denormalized properties to ease testing
     */
    protected function sortDenormalizedProperties()
    {
        ksort($this->isDeletedByParticipant);
        ksort($this->datesOfLastMessageWrittenByParticipant);
        ksort($this->datesOfLastMessageWrittenByOtherParticipant);
        $participants = $this->participants->toArray();
        usort($participants, function(ParticipantInterface $p1, ParticipantInterface $p2) {
            return $p1->getId() > $p2->getId();
        });
        $this->participants = new ArrayCollection($participants);
    }
}
