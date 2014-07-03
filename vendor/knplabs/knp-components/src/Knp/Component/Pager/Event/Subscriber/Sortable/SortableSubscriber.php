<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\BeforeEvent;

class SortableSubscriber implements EventSubscriberInterface
{
    public function before(BeforeEvent $event)
    {
        $disp = $event->getEventDispatcher();
        // hook all standard sortable subscribers
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber());
        $disp->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber());
        $disp->addSubscriber(new ElasticaQuerySubscriber());
        $disp->addSubscriber(new PropelQuerySubscriber());
        $disp->addSubscriber(new SolariumQuerySubscriber());
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.before' => array('before', 1)
        );
    }
}
