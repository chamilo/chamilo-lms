<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemShared;

/**
 * Class XApiPortfolioItemAddedHookObserver.
 */
class XApiPortfolioItemAddedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemAddedObserverInterface
{
    /**
     * {@inheritDoc}
     */
    public function hookItemAdded(HookPortfolioItemAddedEventInterface $hookEvent)
    {
        $item = $hookEvent->getEventData()['portfolio'];

        $portfolioItemShared = new PortfolioItemShared($item);

        $statement = $portfolioItemShared->generate();

        $this->saveSharedStatement($statement);
    }
}
