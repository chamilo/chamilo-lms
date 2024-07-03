<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;

class ExternalNotificationConnectPortfolioItemAddedHookObserver extends ExternalNotificationConnectHookObserver implements HookPortfolioItemAddedObserverInterface
{
    use RequestTrait;

    public function hookItemAdded(HookPortfolioItemAddedEventInterface $hookEvent)
    {
        /** @var Portfolio $item */
        $item = $hookEvent->getEventData()['portfolio'];
        $userId = api_get_user_id();
        $courseCode = api_get_course_id();

        $cidreq = api_get_cidreq();

        $url = api_get_path(WEB_CODE_PATH).'portfolio/index.php?';
        $url .= ($cidreq ? $cidreq.'&' : '');
        $url .= http_build_query(['action' => 'view', 'id' => $item->getId()]);

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => $userId,
                    'course_code' => $courseCode,
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'content_url' => $url,
                    'post_title' => $item->getTitle(),
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

        error_log('ExtNotifConn: Portfolio item created: ID '.$json['data']['notification_id']);
    }
}
