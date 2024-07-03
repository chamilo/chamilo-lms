<?php

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioDownloaded;
use Chamilo\UserBundle\Entity\User;

class XApiPortfolioDownloadedHookObserver extends XApiActivityHookObserver implements HookPortfolioDownloadedObserverInterface
{
    public function hookPortfolioDownloaded(HookPortfolioDownloadedEventInterface $hookEvent)
    {
        /** @var User $owner */
        $owner = $hookEvent->getEventData()['owner'];

        $statement = (new PortfolioDownloaded($owner))->generate();

        $this->saveSharedStatement($statement);
    }
}
