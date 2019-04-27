<?php

namespace Test\Mock;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\PaginationEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;

class PaginationSubscriber implements EventSubscriberInterface
{
    static function getSubscribedEvents()
    {
        return array(
            'knp_pager.pagination' => array('pagination', 0)
        );
    }

    function pagination(PaginationEvent $e)
    {
        $e->setPagination(new SlidingPagination);
        $e->stopPropagation();
    }
}