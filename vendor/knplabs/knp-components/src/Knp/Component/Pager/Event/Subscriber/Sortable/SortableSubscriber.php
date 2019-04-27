<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\BeforeEvent;

class SortableSubscriber implements EventSubscriberInterface
{
    /**
     * Lazy-load state tracker
     * @var bool
     */
    private $isLoaded = false;

    public function before(BeforeEvent $event)
    {
        // Do not lazy-load more than once
        if ($this->isLoaded) {
            return;
        }

        $disp = $event->getEventDispatcher();
        // hook all standard sortable subscribers
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber());
        $disp->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber());
        $disp->addSubscriber(new ElasticaQuerySubscriber());
        $disp->addSubscriber(new PropelQuerySubscriber());
        $disp->addSubscriber(new SolariumQuerySubscriber());
        $disp->addSubscriber(new ArraySubscriber());

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.before' => array('before', 1)
        );
    }
}
