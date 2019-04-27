<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Doctrine\ORM\Query;
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
                $dir = isset($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) && strtolower($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) === 'asc' ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                    if (!in_array($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]], $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]]}] this field is not in whitelist");
                    }
                }

                $sortFieldParameterNames = $_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]];
                $fields = array();
                $aliases = array();
                foreach (explode('+', $sortFieldParameterNames) as $sortFieldParameterName) {
                    $parts = explode('.', $sortFieldParameterName, 2);

                    // We have to prepend the field. Otherwise OrderByWalker will add
                    // the order-by items in the wrong order
                    array_unshift($fields, end($parts));
                    array_unshift($aliases, 2 <= count($parts) ? reset($parts) : false);
                }

                $event->target
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_DIRECTION, $dir)
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_FIELD, $fields)
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_ALIAS, $aliases)
                ;

                QueryHelper::addCustomTreeWalker($event->target, 'Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker');
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
