<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemShared;

class XApiPortfolioItemAddedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemAddedObserverInterface
{
    public function hookItemAdded(HookPortfolioItemAddedEventInterface $hookEvent): void
    {
        $item = $hookEvent->getEventData()['portfolio'];

        $portfolioItemShared = new PortfolioItemShared($item);

        $statement = $portfolioItemShared->generate();

        $this->saveSharedStatement($statement);
    }
}
