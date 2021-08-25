<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Tools\Pagination\CountWalker;

class UsesPaginator implements EventSubscriberInterface
{
    const HINT_FETCH_JOIN_COLLECTION = 'knp_paginator.fetch_join_collection';

    public function items(ItemsEvent $event)
    {
        if (!class_exists('Doctrine\ORM\Tools\Pagination\Paginator')) {
            return;
        }
        if (!$event->target instanceof Query) {
            return;
        }
        $event->stopPropagation();

        $useOutputWalkers = false;
        if (isset($event->options['wrap-queries'])) {
            $useOutputWalkers = $event->options['wrap-queries'];
        }

        $event->target
            ->setFirstResult($event->getOffset())
            ->setMaxResults($event->getLimit())
            ->setHint(CountWalker::HINT_DISTINCT, $event->options[PaginatorInterface::DISTINCT])
        ;

        $fetchJoinCollection = true;
        if ($event->target->hasHint(self::HINT_FETCH_JOIN_COLLECTION)) {
            $fetchJoinCollection = $event->target->getHint(self::HINT_FETCH_JOIN_COLLECTION);
        } else if (isset($event->options[PaginatorInterface::DISTINCT])) {
            $fetchJoinCollection = $event->options[PaginatorInterface::DISTINCT];
        }

        $paginator = new Paginator($event->target, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        if (($count = $event->target->getHint(QuerySubscriber::HINT_COUNT)) !== false) {
            $event->count = intval($count);
        } else {
            $event->count = count($paginator);
        }
        $event->items = iterator_to_array($paginator);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
