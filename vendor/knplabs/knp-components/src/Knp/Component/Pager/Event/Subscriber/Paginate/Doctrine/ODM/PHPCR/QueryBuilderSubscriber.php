<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

/**
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class QueryBuilderSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (!$event->target instanceof QueryBuilder) {
            return;
        }

        $event->target = $event->target->getQuery();
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 10/*make sure to transform before any further modifications*/)
        );
    }
}
