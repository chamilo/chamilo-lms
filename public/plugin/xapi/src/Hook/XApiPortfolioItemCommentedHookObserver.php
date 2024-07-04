<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemCommented;

class XApiPortfolioItemCommentedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemCommentedObserverInterface
{
    public function hookItemCommented(HookPortfolioItemCommentedEventInterface $hookEvent): void
    {
        $comment = $hookEvent->getEventData()['comment'];

        $portfolioItemCommented = new PortfolioItemCommented($comment);

        $statement = $portfolioItemCommented->generate();

        $this->saveSharedStatement($statement);
    }
}
