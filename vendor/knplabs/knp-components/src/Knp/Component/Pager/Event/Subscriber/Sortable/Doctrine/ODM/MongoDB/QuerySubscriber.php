<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            if (isset($_GET[$event->options['sortFieldParameterName']])) {
                $field = $_GET[$event->options['sortFieldParameterName']];
                $dir = strtolower($_GET[$event->options['sortDirectionParameterName']]) == 'asc' ? 1 : -1;

                if (isset($event->options['sortFieldWhitelist'])) {
                    if (!in_array($field, $event->options['sortFieldWhitelist'])) {
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
