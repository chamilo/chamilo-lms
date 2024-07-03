<?php

/* For licensing terms, see /license.txt */

class HookPortfolioDownloaded extends HookEvent implements HookPortfolioDownloadedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioDownloaded');
    }

    public function notifyPortfolioDownloaded(): void
    {
        /** @var HookPortfolioDownloadedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookPortfolioDownloaded($this);
        }
    }
}
