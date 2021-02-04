<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemCommented;

/**
 * Class XApiPortfolioItemCommentedHookObserver.
 */
class XApiPortfolioItemCommentedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemCommentedObserverInterface
{
    /**
     * {@inheritDoc}
     */
    public function hookItemCommented(HookPortfolioItemCommentedEventInterface $hookEvent)
    {
        $comment = $hookEvent->getEventData()['comment'];

        $portfolioItemCommented = new PortfolioItemCommented($comment);

        $statement = $portfolioItemCommented->generate();

        $this->saveSharedStatement($statement);
    }
}
