<?php

namespace Test\Mock;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;

class CustomParameterSubscriber extends ArraySubscriber
{
    static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 10)
        );
    }

    function items(ItemsEvent $e)
    {
        $e->setCustomPaginationParameter('test', 'val');
        parent::items($e);
    }
}
