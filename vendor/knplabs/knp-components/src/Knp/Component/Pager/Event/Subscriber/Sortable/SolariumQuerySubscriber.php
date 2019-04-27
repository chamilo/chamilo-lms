<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solarium query sorting
 *
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (is_array($event->target) && 2 == count($event->target)) {
            $event->setCustomPaginationParameter('sorted', true);

            $values = array_values($event->target);
            list($client, $query) = $values;

            if ($client instanceof \Solarium\Client && $query instanceof \Solarium\QueryType\Select\Query\Query) {
                if (isset($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]])) {
                    if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                        if (!in_array($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]], $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                            throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]]}] this field is not in whitelist");
                        }
                    }

                    $query->addSort($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]], $this->getSortDirection($event));
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // trigger before the pagination subscriber
            'knp_pager.items' => array('items', 1),
        );
    }

    private function getSortDirection($event)
    {
        return isset($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) &&
            strtolower($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) === 'asc' ? 'asc' : 'desc';
    }
}
