<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\Portfolio;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class XApiPortfolioItemHighlightedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemHighlightedObserverInterface
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function hookItemHighlighted(HookPortfolioItemHighlightedEventInterface $hookEvent): void
    {
        /** @var Portfolio $item */
        $item = $hookEvent->getEventData()['item'];

        $statement = (new PortfolioItemHighlighted($item))->generate();

        $this->saveSharedStatement($statement);
    }
}
