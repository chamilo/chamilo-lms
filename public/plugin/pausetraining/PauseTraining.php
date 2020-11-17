<?php

/* For licensing terms, see /license.txt */

class PauseTraining extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
                'allow_users_to_edit_pause_formation' => 'boolean',
                'cron_alert_users_if_inactive_days' => 'text', // Example: "5" or "5,10,15"
                'sender_id' => 'user',
            ]
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function updateUserPauseTraining($userId, $values)
    {
        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            throw new Exception("User #$userId does not exists");
        }

        $variables = [
            'pause_formation',
            'start_pause_date',
            'end_pause_date',
            'disable_emails',
        ];

        $valuesToUpdate = [
            'item_id' => $userId,
        ];

        // Check if variables exist.
        foreach ($variables as $variable) {
            if (!isset($values[$variable])) {
                throw new Exception("Variable '$variable' is missing. Cannot updated.");
            }

            $valuesToUpdate['extra_'.$variable] = $values[$variable];
        }

        // Clean variables
        $pause = (int) $valuesToUpdate['extra_pause_formation'];
        if (empty($pause)) {
            $valuesToUpdate['extra_pause_formation'] = 0;
        } else {
            $valuesToUpdate['extra_pause_formation'] = [];
            $valuesToUpdate['extra_pause_formation']['extra_pause_formation'] = $pause;
        }

        $notification = (int) $valuesToUpdate['extra_disable_emails'];
        if (empty($notification)) {
            $valuesToUpdate['extra_disable_emails'] = 0;
        } else {
            $valuesToUpdate['extra_disable_emails'] = [];
            $valuesToUpdate['extra_disable_emails']['extra_disable_emails'] = $notification;
        }

        $check = DateTime::createFromFormat('Y-m-d H:i', $valuesToUpdate['extra_start_pause_date']);

        if (false === $check) {
            throw new Exception("start_pause_date is not valid date time format should be: Y-m-d H:i");
        }

        $check = DateTime::createFromFormat('Y-m-d H:i', $valuesToUpdate['extra_end_pause_date']);
        if (false === $check) {
            throw new Exception("end_pause_date is not valid date time format should be: Y-m-d H:i");
        }

        if (api_strtotime($valuesToUpdate['extra_start_pause_date']) > api_strtotime($valuesToUpdate['extra_end_pause_date'])) {
            throw new Exception("end_pause_date must be bigger than start_pause_date");
        }

        $extraField = new ExtraFieldValue('user');
        $extraField->saveFieldValues($valuesToUpdate, true, false, [], [], true);

        return (int) $userId;
    }

    public function runCron($date = '', $isTest = false)
    {
        $enable = $this->get('tool_enable');
        $senderId = $this->get('sender_id');
        $enableDays = $this->get('cron_alert_users_if_inactive_days');

        if ('true' !== $enable) {
            echo 'Plugin not enabled';

            return false;
        }

        if (empty($senderId)) {
            echo 'Sender id not configured';

            return false;
        }

        $senderInfo = api_get_user_info($senderId);

        if (empty($senderInfo)) {
            echo "Sender #$senderId not found in Chamilo";

            return false;
        }

        $enableDaysList = explode(',', $enableDays);
        rsort($enableDaysList);

        $track = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $usersNotificationPerDay = [];
        $now = api_get_utc_datetime();
        if (!empty($date)) {
            $now = $date;
        }

        if ($isTest) {
            echo "-------------------------------------------".PHP_EOL;
            echo "----- Testing date $now ----".PHP_EOL;
            echo "-------------------------------------------".PHP_EOL;
        }

        $extraFieldValue = new ExtraFieldValue('user');
        $sql = "SELECT u.id
                FROM $userTable u
                WHERE u.status <> ".ANONYMOUS." AND u.active = 1";
        $rs = Database::query($sql);

        $users = [];
        while ($user = Database::fetch_array($rs)) {
            $userId = $user['id'];

            $sql = "SELECT
                        MAX(t.logout_course_date) max_course_date,
                        MAX(l.logout_date) max_login_date
                    FROM $userTable u
                    LEFT JOIN $track t
                    ON (u.id = t.user_id)
                    LEFT JOIN $login l
                    ON (u.id = l.login_user_id)
                    WHERE
                        u.id = $userId
                    LIMIT 1
                    ";
            $result = Database::query($sql);
            $data = Database::fetch_array($result);
            $maxCourseDate = '';
            $maxLoginDate = '';

            // Take max date value.
            if ($data) {
                $maxCourseDate = $data['max_course_date'];
                $maxLoginDate = $data['max_login_date'];
            }

            $maxEndPause = null;
            $pause = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'pause_formation');
            if (!empty($pause) && isset($pause['value']) && 1 == $pause['value']) {
                $endDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $userId,
                    'end_pause_date'
                );
                if (!empty($endDate) && isset($endDate['value']) && !empty($endDate['value'])) {
                    $maxEndPause = $endDate['value'];
                }
            }

            $maxDate = $maxCourseDate;
            if ($maxLoginDate > $maxCourseDate) {
                $maxDate = $maxLoginDate;
            }

            if ($maxEndPause > $maxDate) {
                $maxDate = $maxEndPause;
            }

            if (empty($maxDate)) {
                // Nothing found for that user, skip.
                continue;
            }
            $users[$userId] = $maxDate;
        }

        $extraFieldValue = new ExtraFieldValue('user');
        foreach ($enableDaysList as $day) {
            $day = (int) $day;

            if (0 === $day) {
                echo 'Day = 0 avoided '.PHP_EOL;
                continue;
            }
            $dayToCheck = $day + 1;
            $hourStart = $dayToCheck * 24;
            $hourEnd = ($dayToCheck - 1) * 24;

            $date = new DateTime($now);
            $date->sub(new DateInterval('PT'.$hourStart.'H'));
            $hourStart = $date->format('Y-m-d H:i:s');

            $date = new DateTime($now);
            $date->sub(new DateInterval('PT'.$hourEnd.'H'));
            $hourEnd = $date->format('Y-m-d H:i:s');

            echo "Processing day $day: $hourStart - $hourEnd ".PHP_EOL.PHP_EOL;

            foreach ($users as $userId => $maxDate) {
                if (!($maxDate > $hourStart && $maxDate < $hourEnd)) {
                    //echo "Message skipped for user #$userId because max date found: $maxDate not in range $hourStart - $hourEnd ".PHP_EOL;
                    continue;
                }

                // Check if user has selected to pause formation.
                $pause = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'pause_formation');
                if (!empty($pause) && isset($pause['value']) && 1 == $pause['value']) {
                    $startDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $userId,
                        'start_pause_date'
                    );
                    $endDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $userId,
                        'end_pause_date'
                    );

                    if (
                        !empty($startDate) && isset($startDate['value']) && !empty($startDate['value']) &&
                        !empty($endDate) && isset($endDate['value']) && !empty($endDate['value'])
                    ) {
                        $startDate = $startDate['value'];
                        $endDate = $endDate['value'];

                        if ($startDate > $hourStart && $startDate < $hourStart) {
                            //echo "Message skipped for user #$userId because process date $hourStart is in start pause in $startDate - $endDate ".PHP_EOL;
                            continue;
                        }

                        if ($endDate > $hourEnd && $endDate < $hourEnd) {
                            //echo "Message skipped for user #$userId because process date $hourEnd is in start pause in $startDate - $endDate ".PHP_EOL;
                            continue;
                        }
                    }
                }

                echo "User #$userId added to message queue because latest login is $maxDate between $hourStart AND $hourEnd".PHP_EOL;
                $users[] = $userId;
                $usersNotificationPerDay[$day][] = $userId;
            }
        }

        if (!empty($usersNotificationPerDay)) {
            echo PHP_EOL.'Now processing messages ...'.PHP_EOL;

            ksort($usersNotificationPerDay);
            foreach ($usersNotificationPerDay as $day => $userList) {
                $template = new Template(
                    '',
                    true,
                    true,
                    false,
                    false,
                    true,
                    false
                );
                $title = sprintf($this->get_lang('InactivityXDays'), $day);

                foreach ($userList as $userId) {
                    $userInfo = api_get_user_info($userId);
                    $template->assign('days', $day);
                    $template->assign('user', $userInfo);
                    $content = $template->fetch('pausetraining/view/notification_content.tpl');
                    echo 'Ready to send a message "'.$title.'" to user #'.$userId.' '.$userInfo['complete_name'].PHP_EOL;
                    if (false === $isTest) {
                        MessageManager::send_message_simple($userId, $title, $content, $senderId);
                    } else {
                        echo 'Message not send because is in test mode.'.PHP_EOL;
                    }
                }
            }
        }
    }

    public function runCronTest()
    {
        $now = api_get_utc_datetime();
        $days = 15;
        $before = new DateTime($now);
        $before->sub(new DateInterval('P'.$days.'D'));

        $after = new DateTime($now);
        $after->add(new DateInterval('P'.$days.'D'));

        $period = new DatePeriod(
            $before,
            new DateInterval('P1D'),
            $after
        );

        foreach ($period as $key => $value) {
            self::runCron($value->format('Y-m-d H:i:s'), true);
        }
    }

    public function install()
    {
        UserManager::create_extra_field(
            'pause_formation',
            ExtraField::FIELD_TYPE_CHECKBOX,
            $this->get_lang('PauseFormation'),
            ''
        );

        UserManager::create_extra_field(
            'start_pause_date',
            ExtraField::FIELD_TYPE_DATETIME,
            $this->get_lang('StartPauseDateTime'),
            ''
        );

        UserManager::create_extra_field(
            'end_pause_date',
            ExtraField::FIELD_TYPE_DATETIME,
            $this->get_lang('EndPauseDateTime'),
            ''
        );

        UserManager::create_extra_field(
            'disable_emails',
            ExtraField::FIELD_TYPE_CHECKBOX,
            $this->get_lang('DisableEmails'),
            ''
        );
    }

    public function uninstall()
    {
    }
}
