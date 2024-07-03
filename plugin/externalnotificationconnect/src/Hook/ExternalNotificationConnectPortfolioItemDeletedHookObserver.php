<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;

class ExternalNotificationConnectPortfolioItemDeletedHookObserver extends ExternalNotificationConnectHookObserver implements HookPortfolioItemDeletedHookObserverInterface
{
    use RequestTrait;

    public function hookItemDeleted(HookPortfolioItemDeletedEventInterface $hookEvent)
    {
        /** @var Portfolio $item */
        $item = $hookEvent->getEventData()['item'];

        try {
            $json = $this->doDeleteRequest($item->getId(), 'eportfolio');
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item deleted: Status '.((int) $json['status']));
    }
}
