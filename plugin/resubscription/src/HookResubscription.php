<?php
/* For licensing terms, see /license.txt */

/**
 * Hook to limit session resubscriptions.
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 *
 * @package chamilo.plugin.resubscription
 */
class HookResubscription extends HookObserver implements HookResubscribeObserverInterface
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'plugin/resubscription/src/Resubscription.php',
            'resubscription'
        );
    }

    /**
     * Limit session resubscription when a Chamilo user is resubscribed to a session.
     *
     * @param HookCreateUserEventInterface $hook The hook
     */
    public function hookResubscribe(HookResubscribeEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            $resubscriptionLimit = Resubscription::create()->get('resubscription_limit');

            // Initialize variables as a calendar year by default
            $limitDateFormat = 'Y-01-01';
            $limitDate = gmdate($limitDateFormat);
            $resubscriptionOffset = "1 year";

            // No need to use a 'switch' with only two options so an 'if' is enough.
            // However this could change if the number of options increases
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
                            $data['session_id'],
                        ],
                    ],
                ]
            );

            // Check if current course code matches with one of the users
            foreach ($currentSessionCourseResult as $currentSessionCourse) {
                if (isset($userSessionCourses[$currentSessionCourse['c_id']])) {
                    $endDate = $userSessionCourses[$currentSessionCourse['c_id']];
                    $resubscriptionDate = gmdate($limitDateFormat, strtotime($endDate." +$resubscriptionOffset"));
                    $icon = Display::return_icon('students.gif', get_lang('Student'));
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
