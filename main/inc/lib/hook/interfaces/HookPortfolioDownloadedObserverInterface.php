<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioDownloadedObserverInterface extends HookObserverInterface
{
    public function hookPortfolioDownloaded(HookPortfolioDownloadedEventInterface $hookEvent);
}
