<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\BeforeEvent;

class FiltrationSubscriber implements EventSubscriberInterface
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
        // hook all standard filtration subscribers
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber());
        $disp->addSubscriber(new PropelQuerySubscriber());

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.before' => array('before', 1),
        );
    }
}
