<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItemEdited;

class XApiPortfolioItemEditedHookObserver extends XApiActivityHookObserver implements HookPortfolioItemEditedObserverInterface
{
    public function hookItemEdited(HookPortfolioItemEditedEventInterface $hookEvent)
    {
        /** @var Portfolio $item */
        $item = $hookEvent->getEventData()['item'];

        $statement = (new PortfolioItemEdited($item))->generate();

        $this->saveSharedStatement($statement);
    }
}
