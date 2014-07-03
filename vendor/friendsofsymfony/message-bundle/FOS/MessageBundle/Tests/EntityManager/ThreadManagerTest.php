<?php

namespace FOS\MessageBundle\Tests\EntityManager;
use FOS\MessageBundle\EntityManager\ThreadManager;
use FOS\MessageBundle\Model\ThreadInterface;

/**
 * Class ThreadManagerTest
 *
 * @author Tobias Nyholm
 */
class ThreadManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $user;
    protected $date;

    public function setUp()
    {
        $this->user = $this->createParticipantMock('4711');
        $this->date = new \DateTime('2013-12-25');
    }

    /**
     * Usual test case where neither createdBy or createdAt is set
     */
    public function testDoCreatedByAndAt()
    {
        $thread = $this->createThreadMock();
        $thread->expects($this->exactly(1))->method('getFirstMessage')
            ->will($this->returnValue($this->createMessageMock()));

        $threadManager = new TestThreadManager();
        $threadManager->doCreatedByAndAt($thread);
    }

    /**
     * Test where createdBy is set
     */
    public function testDoCreatedByAndAtWithCreatedBy()
    {
        $thread = $this->createThreadMock();

        $thread->expects($this->exactly(0))->method('setCreatedBy');
        $thread->expects($this->exactly(1))->method('setCreatedAt');
        $thread->expects($this->exactly(1))->method('getCreatedBy')
            ->will($this->returnValue($this->user));

        $thread->expects($this->exactly(1))->method('getFirstMessage')
            ->will($this->returnValue($this->createMessageMock()));

        $threadManager = new TestThreadManager();
        $threadManager->doCreatedByAndAt($thread);
    }

    /**
     * Test where createdAt is set
     */
    public function testDoCreatedByAndAtWithCreatedAt()
    {
        $thread = $this->createThreadMock();

        $thread->expects($this->exactly(1))->method('setCreatedBy');
        $thread->expects($this->exactly(0))->method('setCreatedAt');
        $thread->expects($this->exactly(1))->method('getCreatedAt')
            ->will($this->returnValue($this->date));

        $thread->expects($this->exactly(1))->method('getFirstMessage')
            ->will($this->returnValue($this->createMessageMock()));

        $threadManager = new TestThreadManager();
        $threadManager->doCreatedByAndAt($thread);
    }

    /**
     * Test where both craetedAt and createdBy is set
     */
    public function testDoCreatedByAndAtWithCreatedAtAndBy()
    {
        $thread = $this->createThreadMock();
        $thread->expects($this->exactly(0))->method('setCreatedBy');
        $thread->expects($this->exactly(0))->method('setCreatedAt');
        $thread->expects($this->exactly(1))->method('getCreatedAt')
            ->will($this->returnValue($this->date));

        $thread->expects($this->exactly(1))->method('getCreatedBy')
            ->will($this->returnValue($this->user));

        $thread->expects($this->exactly(1))->method('getFirstMessage')
            ->will($this->returnValue($this->createMessageMock()));

        $threadManager = new TestThreadManager();
        $threadManager->doCreatedByAndAt($thread);
    }

    /**
     * Test where thread do not have a message
     */
    public function testDoCreatedByAndNoMessage()
    {
        $thread = $this->createThreadMock();
        $thread->expects($this->exactly(0))->method('setCreatedBy');
        $thread->expects($this->exactly(0))->method('setCreatedAt');
        $thread->expects($this->exactly(0))
            ->method('getCreatedAt')
            ->will($this->returnValue($this->date));
        $thread->expects($this->exactly(0))
            ->method('getCreatedBy')
            ->will($this->returnValue($this->user));

        $threadManager = new TestThreadManager();
        $threadManager->doCreatedByAndAt($thread);
    }

    /**
     * Get a message mock
     *
     * @return mixed
     */
    protected function createMessageMock()
    {
        $message = $this->getMockBuilder('FOS\MessageBundle\Document\Message')
            ->getMock();

        $message->expects($this->any())
            ->method('getSender')
            ->will($this->returnValue($this->user));

        $message->expects($this->any())
            ->method('getCreatedAt')
            ->will($this->returnValue($this->date));

        return $message;
    }

    /**
     * Add expectations on the thread mock
     *
     * @param mock &$thread
     * @param int $createdByCalls
     * @param int $createdAtCalls
     */
    protected function addThreadExpectations(&$thread, $createdByCalls=1, $createdAtCalls=1)
    {
        $thread->expects($this->exactly($createdByCalls))
            ->method('setCreatedBy')
            ->with($this->equalTo($this->user));

        $thread->expects($this->exactly($createdAtCalls))
            ->method('setCreatedAt')
            ->with($this->equalTo($this->date));
    }

    /**
     * Get a Participant
     *
     * @param $id
     *
     * @return mixed
     */
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

    /**
     * Returns a thread mock
     *
     * @return mixed
     */
    protected function createThreadMock()
    {
        return $this->getMockBuilder('FOS\MessageBundle\Model\ThreadInterface')
            ->disableOriginalConstructor(true)
            ->getMock();
    }
}

class TestThreadManager extends ThreadManager
{
    /**
     * Empty constructor
     */
    public function __construct() { }

    /**
     * Make the function public
     *
     * @param ThreadInterface $thread
     *
     */
    public function doCreatedByAndAt(ThreadInterface $thread)
    {
        return parent::doCreatedByAndAt($thread);
    }
}
