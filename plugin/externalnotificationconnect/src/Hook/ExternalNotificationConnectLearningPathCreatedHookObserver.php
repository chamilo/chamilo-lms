<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;

class ExternalNotificationConnectLearningPathCreatedHookObserver extends ExternalNotificationConnectHookObserver implements HookLearningPathCreatedObserverInterface
{
    use RequestTrait;

    public function hookCreated(HookLearningPathCreatedEventInterface $hookEvent)
    {
        /** @var CLp $lp */
        $lp = $hookEvent->getEventData()['lp'];
        $userId = api_get_user_id();
        $courseCode = api_get_course_id();

        $cidreq = api_get_cidreq();

        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?';
        $url .= ($cidreq ? $cidreq.'&' : '');
        $url .= http_build_query(
            [
                'action' => 'view',
                'lp_id' => $lp->getIid(),
                'isStudentView' => 'true',
            ]
        );

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => $userId,
                    'course_code' => $courseCode,
                    'content_id' => $lp->getIid(),
                    'content_type' => 'lp',
                    'content_url' => $url,
                    'post_title' => $lp->getName(),
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

        error_log('ExtNotifConn: Learning path created: ID '.$json['data']['notification_id']);
    }
}
