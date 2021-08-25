<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Elastica\Query;
use Elastica\SearchableInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Elastica query pagination.
 *
 */
class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof SearchableInterface && end($event->target) instanceof Query) {
            list($searchable, $query) = $event->target;

            $query->setFrom($event->getOffset());
            $query->setSize($event->getLimit());
            $results = $searchable->search($query);

            $event->count = $results->getTotalHits();

            if ($results->hasAggregations()) {
                $event->setCustomPaginationParameter('aggregations', $results->getAggregations());
            }

            $event->setCustomPaginationParameter('resultSet', $results);
            $event->items = $results->getResults();
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0) /* triggers before a standard array subscriber*/
        );
    }
}
