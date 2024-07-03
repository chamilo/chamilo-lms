<?php

declare(strict_types=1);

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioDownloaded;
use Chamilo\UserBundle\Entity\User;

class XApiPortfolioDownloadedHookObserver extends XApiActivityHookObserver implements HookPortfolioDownloadedObserverInterface
{
    public function hookPortfolioDownloaded(HookPortfolioDownloadedEventInterface $hookEvent): void
    {
        /** @var User $owner */
        $owner = $hookEvent->getEventData()['owner'];

        $statement = (new PortfolioDownloaded($owner))->generate();

        $this->saveSharedStatement($statement);
    }
}
