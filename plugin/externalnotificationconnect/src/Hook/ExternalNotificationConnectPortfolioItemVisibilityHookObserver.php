<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;

class ExternalNotificationConnectPortfolioItemVisibilityHookObserver extends ExternalNotificationConnectHookObserver implements HookPortfolioItemVisibilityObserverInterface
{
    use RequestTrait;

    /**
     * {@inheritDoc}
     */
    public function hookItemVisibility(HookPortfolioItemVisibilityEventInterface $event)
    {
        /** @var Portfolio $item */
        $item = $event->getEventData()['item'];
        $recipients = $event->getEventData()['recipients'];

        try {
            $json = $this->doVisibilityRequest(
                [
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'visibility' => $item->getVisibility(),
                    'user_list' => $recipients,
                ]
            );
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item visibility: ID '.$json['data']['notification_id']);
    }
}
