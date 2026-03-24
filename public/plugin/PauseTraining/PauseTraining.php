<?php

/* For licensing terms, see /license.txt */

class PauseTraining extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '0.2',
            'Julio Montoya',
            [
                'allow_users_to_edit_pause_formation' => 'boolean',
                'cron_alert_users_if_inactive_days' => 'text',
                'sender_id' => 'user',
            ]
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function updateUserPauseTraining($userId, $values)
    {
        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            throw new Exception("User #$userId does not exist");
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

        foreach ($variables as $variable) {
            if (!array_key_exists($variable, $values)) {
                throw new Exception("Variable '$variable' is missing. Cannot update.");
            }

            $valuesToUpdate['extra_'.$variable] = $values[$variable];
        }

        $pauseEnabled = !empty($valuesToUpdate['extra_pause_formation']);
        $disableEmails = !empty($valuesToUpdate['extra_disable_emails']);

        $valuesToUpdate['extra_pause_formation'] = $pauseEnabled
            ? ['extra_pause_formation' => '1']
            : '0';

        $valuesToUpdate['extra_disable_emails'] = $disableEmails
            ? ['extra_disable_emails' => '1']
            : '0';

        $startPauseDate = trim((string) $valuesToUpdate['extra_start_pause_date']);
        $endPauseDate = trim((string) $valuesToUpdate['extra_end_pause_date']);

        if ($pauseEnabled) {
            $check = DateTime::createFromFormat('Y-m-d H:i', $startPauseDate);
            if (false === $check) {
                throw new Exception('start_pause_date is not valid. Expected format: Y-m-d H:i');
            }

            $check = DateTime::createFromFormat('Y-m-d H:i', $endPauseDate);
            if (false === $check) {
                throw new Exception('end_pause_date is not valid. Expected format: Y-m-d H:i');
            }

            if (api_strtotime($startPauseDate) > api_strtotime($endPauseDate)) {
                throw new Exception('end_pause_date must be greater than or equal to start_pause_date');
            }
        }

        $extraField = new ExtraFieldValue('user');
        $extraField->saveFieldValues($valuesToUpdate, true, false, [], [], true);

        return (int) $userId;
    }

    public function runCron($date = '', $isTest = false)
    {
        $enable = (string) $this->get('tool_enable');
        $senderId = (int) $this->get('sender_id');
        $enableDays = (string) $this->get('cron_alert_users_if_inactive_days');

        if ('true' !== $enable) {
            echo 'Plugin not enabled'.PHP_EOL;

            return false;
        }

        if (empty($senderId)) {
            echo 'Sender id not configured'.PHP_EOL;

            return false;
        }

        $senderInfo = api_get_user_info($senderId);
        if (empty($senderInfo)) {
            echo "Sender #$senderId not found in Chamilo".PHP_EOL;

            return false;
        }

        $enableDaysList = $this->getNotificationDays($enableDays);
        if (empty($enableDaysList)) {
            echo 'No inactivity days configured'.PHP_EOL;

            return false;
        }

        $trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $loginTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $usersNotificationPerDay = [];
        $now = !empty($date) ? $date : api_get_utc_datetime();

        if ($isTest) {
            echo "-------------------------------------------".PHP_EOL;
            echo "----- Testing date $now ----".PHP_EOL;
            echo "-------------------------------------------".PHP_EOL;
        }

        $extraFieldValue = new ExtraFieldValue('user');

        $sql = "SELECT u.id
                FROM $userTable u
                WHERE u.status <> ".ANONYMOUS." AND u.active = 1";
        $result = Database::query($sql);

        $users = [];
        while ($user = Database::fetch_array($result)) {
            $userId = (int) $user['id'];

            $sql = "SELECT
                        MAX(t.logout_course_date) AS max_course_date,
                        MAX(l.logout_date) AS max_login_date
                    FROM $userTable u
                    LEFT JOIN $trackTable t ON (u.id = t.user_id)
                    LEFT JOIN $loginTable l ON (u.id = l.login_user_id)
                    WHERE u.id = $userId
                    LIMIT 1";
            $activityResult = Database::query($sql);
            $activityData = Database::fetch_array($activityResult);

            $maxCourseDate = !empty($activityData['max_course_date']) ? (string) $activityData['max_course_date'] : '';
            $maxLoginDate = !empty($activityData['max_login_date']) ? (string) $activityData['max_login_date'] : '';

            $maxDate = $maxCourseDate;
            if (!empty($maxLoginDate) && (empty($maxDate) || api_strtotime($maxLoginDate) > api_strtotime($maxDate))) {
                $maxDate = $maxLoginDate;
            }

            $pauseEndDate = $this->getExtraFieldScalarValue($extraFieldValue, $userId, 'end_pause_date');
            $pauseEnabled = $this->isEnabledCheckboxField($extraFieldValue, $userId, 'pause_formation');

            if ($pauseEnabled && !empty($pauseEndDate) && (empty($maxDate) || api_strtotime($pauseEndDate) > api_strtotime($maxDate))) {
                $maxDate = $pauseEndDate;
            }

            if (empty($maxDate)) {
                continue;
            }

            $users[$userId] = $maxDate;
        }

        foreach ($enableDaysList as $day) {
            $hoursStart = ($day + 1) * 24;
            $hoursEnd = $day * 24;

            $windowStartDate = new DateTime($now);
            $windowStartDate->sub(new DateInterval('PT'.$hoursStart.'H'));
            $windowStart = $windowStartDate->format('Y-m-d H:i:s');

            $windowEndDate = new DateTime($now);
            $windowEndDate->sub(new DateInterval('PT'.$hoursEnd.'H'));
            $windowEnd = $windowEndDate->format('Y-m-d H:i:s');

            echo "Processing day $day: $windowStart - $windowEnd".PHP_EOL.PHP_EOL;

            $windowStartTs = api_strtotime($windowStart);
            $windowEndTs = api_strtotime($windowEnd);
            $nowTs = api_strtotime($now);

            foreach ($users as $userId => $maxDate) {
                $maxDateTs = api_strtotime($maxDate);
                if (false === $maxDateTs || false === $windowStartTs || false === $windowEndTs || false === $nowTs) {
                    continue;
                }

                if ($maxDateTs <= $windowStartTs || $maxDateTs > $windowEndTs) {
                    continue;
                }

                if ($this->isEnabledCheckboxField($extraFieldValue, $userId, 'disable_emails')) {
                    echo "User #$userId skipped because automatic emails are disabled".PHP_EOL;
                    continue;
                }

                if ($this->isUserInPauseWindow($extraFieldValue, $userId, $nowTs)) {
                    echo "User #$userId skipped because pause training is active on $now".PHP_EOL;
                    continue;
                }

                echo "User #$userId added to message queue because latest activity is $maxDate between $windowStart AND $windowEnd".PHP_EOL;
                $usersNotificationPerDay[$day][$userId] = $userId;
            }
        }

        if (empty($usersNotificationPerDay)) {
            echo PHP_EOL.'No messages to process.'.PHP_EOL;

            return true;
        }

        echo PHP_EOL.'Now processing messages ...'.PHP_EOL;

        ksort($usersNotificationPerDay);
        foreach ($usersNotificationPerDay as $day => $userList) {
            $title = $this->getCronNotificationTitle((int) $day);

            foreach ($userList as $userId) {
                $userInfo = api_get_user_info($userId);
                if (empty($userInfo)) {
                    continue;
                }

                $content = $this->buildNotificationContent($userInfo, (int) $day);

                echo 'Ready to send a message "'.$title.'" to user #'.$userId.' '.$this->getUserDisplayName($userInfo).PHP_EOL;

                if (false === $isTest) {
                    MessageManager::send_message_simple($userId, $title, $content, $senderId);
                } else {
                    echo 'Message not sent because test mode is enabled.'.PHP_EOL;
                }
            }
        }

        return true;
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

        foreach ($period as $value) {
            $this->runCron($value->format('Y-m-d H:i:s'), true);
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

    private function getNotificationDays($rawValue)
    {
        $items = preg_split('/\s*,\s*/', (string) $rawValue);
        $items = array_map('intval', (array) $items);
        $items = array_filter($items, static function ($day) {
            return $day > 0;
        });
        $items = array_values(array_unique($items));
        rsort($items);

        return $items;
    }

    private function getExtraFieldScalarValue(ExtraFieldValue $extraFieldValue, $userId, $variable)
    {
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, $variable);
        if (empty($value)) {
            return '';
        }

        if (isset($value['value'])) {
            return trim((string) $value['value']);
        }

        if (isset($value['field_value'])) {
            return trim((string) $value['field_value']);
        }

        return '';
    }

    private function isEnabledCheckboxField(ExtraFieldValue $extraFieldValue, $userId, $variable)
    {
        return '1' === $this->getExtraFieldScalarValue($extraFieldValue, $userId, $variable);
    }

    private function isUserInPauseWindow(ExtraFieldValue $extraFieldValue, $userId, $currentTimestamp)
    {
        if (!$this->isEnabledCheckboxField($extraFieldValue, $userId, 'pause_formation')) {
            return false;
        }

        $startDate = $this->getExtraFieldScalarValue($extraFieldValue, $userId, 'start_pause_date');
        $endDate = $this->getExtraFieldScalarValue($extraFieldValue, $userId, 'end_pause_date');

        if (empty($startDate) || empty($endDate)) {
            return false;
        }

        $startTimestamp = api_strtotime($startDate);
        $endTimestamp = api_strtotime($endDate);

        if (false === $startTimestamp || false === $endTimestamp) {
            return false;
        }

        return $currentTimestamp >= $startTimestamp && $currentTimestamp <= $endTimestamp;
    }

    private function getCronNotificationTitle($day)
    {
        return sprintf('Inactivity reminder after %d day(s)', (int) $day);
    }

    private function buildNotificationContent($userInfo, $day)
    {
        $platformName = (string) api_get_setting('platform.institution');
        if (empty($platformName)) {
            $platformName = 'Chamilo';
        }

        $platformUrl = api_get_path(WEB_PATH);
        $completeName = $this->getUserDisplayName($userInfo);

        $message = sprintf(
            'You have not connected to %s during the last %d day(s). Please visit %s to continue your training.',
            $platformName,
            (int) $day,
            $platformUrl
        );

        return 'Dear '.htmlspecialchars($completeName, ENT_QUOTES, 'UTF-8').'<br><br>'
            .htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'<br><br>'
            .'Regards';
    }

    private function getUserDisplayName($userInfo)
    {
        if (!empty($userInfo['complete_name'])) {
            return (string) $userInfo['complete_name'];
        }

        if (!empty($userInfo['firstname']) || !empty($userInfo['lastname'])) {
            return trim((string) ($userInfo['firstname'].' '.$userInfo['lastname']));
        }

        if (!empty($userInfo['username'])) {
            return (string) $userInfo['username'];
        }

        return 'User';
    }
}
