<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            $event->setCustomPaginationParameter('sorted', true);

            if (isset($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]])) {
                $part = $_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]];
                $directionParam = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];

                $direction = (isset($_GET[$directionParam]) && strtolower($_GET[$directionParam]) === 'asc')
                                ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                    if (!in_array($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]], $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]]}] this field is not in whitelist");
                    }
                }

                $query->orderBy($part, $direction);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 1)
        );
    }
}
