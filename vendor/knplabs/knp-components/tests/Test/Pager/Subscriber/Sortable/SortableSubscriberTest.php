<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Event\BeforeEvent;

class SortableSubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldRegisterExpectedSubscribersOnlyOnce()
    {
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $dispatcher->expects($this->exactly(6))->method('addSubscriber');

        $subscriber = new SortableSubscriber;

        $beforeEvent = new BeforeEvent($dispatcher);
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}