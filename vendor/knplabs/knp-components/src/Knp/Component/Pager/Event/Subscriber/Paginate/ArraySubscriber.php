<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use ArrayObject;

class ArraySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target)) {
            $event->count = count($event->target);
            $event->items = array_slice(
                $event->target,
                $event->getOffset(),
                $event->getLimit()
            );
            $event->stopPropagation();
        } elseif ($event->target instanceof ArrayObject) {
            $event->count = $event->target->count();
            $event->items = new ArrayObject(array_slice(
                $event->target->getArrayCopy(),
                $event->getOffset(),
                $event->getLimit()
            ));
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', -1/* other data arrays should be analized first*/)
        );
    }
}
