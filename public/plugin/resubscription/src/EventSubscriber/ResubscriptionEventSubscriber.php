<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionResubscriptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResubscriptionEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::SESSION_RESUBSCRIPTION => 'onResubscribe',
        ];
    }

    /**
     * @throws \Exception
     */
    public function onResubscribe(SessionResubscriptionEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE === $event->getType()) {
            $resubscriptionLimit = Resubscription::create()->get('resubscription_limit');

            // Initialize variables as a calendar year by default
            $limitDateFormat = 'Y-01-01';
            $limitDate = gmdate($limitDateFormat);
            $resubscriptionOffset = "1 year";

            // No need to use a 'switch' with only two options so an 'if' is enough.
            // However, this could change if the number of options increases
            if ($resubscriptionLimit === 'natural_year') {
                $limitDateFormat = 'Y-m-d';
                $limitDate = gmdate($limitDateFormat);
                $limitDate = gmdate($limitDateFormat, strtotime("$limitDate -$resubscriptionOffset"));
            }

            $join = " INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." s ON s.id = su.session_id";

            // User sessions and courses
            $userSessions = Database::select(
                'su.session_id, s.access_end_date',
                Database::get_main_table(TABLE_MAIN_SESSION_USER).' su '.$join,
                [
                    'where' => [
                        'su.user_id = ? AND s.access_end_date >= ?' => [
                            api_get_user_id(),
                            $limitDate,
                        ],
                    ],
                    'order' => 'access_end_date DESC',
                ]
            );
            $userSessionCourses = [];
            foreach ($userSessions as $userSession) {
                $userSessionCourseResult = Database::select(
                    'c_id',
                    Database::get_main_table(TABLE_MAIN_SESSION_COURSE),
                    [
                        'where' => [
                            'session_id = ?' => [
                                $userSession['session_id'],
                            ],
                        ],
                    ]
                );
                foreach ($userSessionCourseResult as $userSessionCourse) {
                    if (!isset($userSessionCourses[$userSessionCourse['c_id']])) {
                        $userSessionCourses[$userSessionCourse['c_id']] = $userSession['access_end_date'];
                    }
                }
            }

            // Current session and courses
            $currentSessionCourseResult = Database::select(
                'c_id',
                Database::get_main_table(TABLE_MAIN_SESSION_COURSE),
                [
                    'where' => [
                        'session_id = ?' => [
                            $event->getSessionId(),
                        ],
                    ],
                ]
            );

            // Check if current course code matches with one of the users
            foreach ($currentSessionCourseResult as $currentSessionCourse) {
                if (isset($userSessionCourses[$currentSessionCourse['c_id']])) {
                    $endDate = $userSessionCourses[$currentSessionCourse['c_id']];
                    $resubscriptionDate = gmdate($limitDateFormat, strtotime($endDate." +$resubscriptionOffset"));
                    $icon = Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Learner'));
                    $canResubscribeFrom = sprintf(
                        get_plugin_lang('CanResubscribeFromX', 'resubscription'),
                        $resubscriptionDate
                    );

                    throw new Exception(Display::label($icon.' '.$canResubscribeFrom, "info"));
                }
            }
        }
    }
}