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

    public function runCron()
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
            echo "Sender #$senderId not found";

            return false;
        }

        $enableDaysList = explode(',', $enableDays);
        rsort($enableDaysList);

        $track = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $now = api_get_utc_datetime();
        $usersNotificationPerDay = [];
        $users = [];
        foreach ($enableDaysList as $day) {
            $day = (int) $day;

            $sql = "SELECT
                    t.user_id,
                    MAX(t.logout_course_date) max_course_date,
                    MAX(l.logout_date) max_login_date
                    FROM $track t
                    INNER JOIN $userTable u
                    ON (u.id = t.user_id)
                    INNER JOIN $login l
                    ON (u.id = l.login_user_id)
                    WHERE
                        u.status <> ".ANONYMOUS." AND
                        u.active = 1
                    GROUP BY t.user_id
                    HAVING
                        DATE_SUB('$now', INTERVAL '$day' DAY) > max_course_date AND
                        DATE_SUB('$now', INTERVAL '$day' DAY) > max_login_date
                    ";

            $rs = Database::query($sql);
            while ($user = Database::fetch_array($rs)) {
                $userId = $user['user_id'];

                if (in_array($userId, $users)) {
                    continue;
                }
                $users[] = $userId;
                $usersNotificationPerDay[$day][] = $userId;
            }
        }

        if (!empty($usersNotificationPerDay)) {
            ksort($usersNotificationPerDay);
            foreach ($usersNotificationPerDay as $day => $userList) {
                $template = new Template();
                $title = sprintf($this->get_lang('InactivityXDays'), $day);

                foreach ($userList as $userId) {
                    $userInfo = api_get_user_info($userId);
                    $template->assign('days', $day);
                    $template->assign('user', $userInfo);
                    $content = $template->fetch('pausetraining/view/notification_content.tpl');
                    MessageManager::send_message_simple($userId, $title, $content, $senderId);
                    echo 'Sending message "'.$title.'" to user #'.$userId.' '.$userInfo['complete_name'].PHP_EOL;
                }
            }
        }
        exit;
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
