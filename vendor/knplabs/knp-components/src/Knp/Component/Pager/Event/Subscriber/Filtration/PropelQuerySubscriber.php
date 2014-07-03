<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            if (!empty($_GET[$event->options['filterFieldParameterName']])
                && !empty($_GET[$event->options['filterValueParameterName']])) {

                $value   = $_GET[$event->options['filterValueParameterName']];
                $columns = $_GET[$event->options['filterFieldParameterName']];

                if (isset($event->options['filterFieldWhitelist'])) {
                    if (!in_array($_GET[$event->options['filterFieldParameterName']], $event->options['filterFieldWhitelist'])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options['filterFieldParameterName']]}] this field is not in whitelist");
                    }
                }

                $criteria = \Criteria::EQUAL;
                if (false !== strpos($value, '*')) {
                    $value = str_replace('*', '%', $value);
                    $criteria = \Criteria::LIKE;
                }

                foreach ((array) $columns as $column) {
                    if (false !== strpos($column, '.')) {
                        $query->where($column . $criteria . '?', $value);
                    } else {
                        $query->{'filterBy' . $column}($value, $criteria);
                    }
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
