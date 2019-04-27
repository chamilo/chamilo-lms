<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;
use Knp\Component\Pager\PaginatorInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if ($event->target instanceof Query) {
            $event->setCustomPaginationParameter('sorted', true);

            if (isset($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]])) {
                $field = $_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]];
                $dir = strtolower($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) == 'asc' ? 1 : -1;

                if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                    if (!in_array($field, $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$field}] this field is not in whitelist");
                    }
                }
                static $reflectionProperty;
                if (is_null($reflectionProperty)) {
                    $reflectionClass = new \ReflectionClass('Doctrine\MongoDB\Query\Query');
                    $reflectionProperty = $reflectionClass->getProperty('query');
                    $reflectionProperty->setAccessible(true);
                }
                $queryOptions = $reflectionProperty->getValue($event->target);

                //@todo: seems like does not support multisort ??
                $queryOptions['sort'] = array($field => $dir);
                $reflectionProperty->setValue($event->target, $queryOptions);
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
