<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemScored;

class XApiPortfolioItemScoredHookObserver extends XApiActivityHookObserver implements HookPortfolioItemScoredObserverInterface
{
    public function hookItemScored(HookPortfolioItemScoredEventInterface $hookEvent): void
    {
        /** @var Portfolio $item */
        $item = $hookEvent->getEventData()['item'];

        $statement = (new PortfolioItemScored($item))->generate();

        $this->saveSharedStatement($statement);
    }
}
