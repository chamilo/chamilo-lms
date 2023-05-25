<?php
/* For licensing terms, see /license.txt */

/**
 * Notification class
 * This class provides methods for the Notification management.
 * Include/require it in your code to use its features.
 */
class Notification extends Model
{
    // mail_notify_message ("At once", "Daily", "No")
    public const NOTIFY_MESSAGE_AT_ONCE = 1;
    public const NOTIFY_MESSAGE_DAILY = 8;
    public const NOTIFY_MESSAGE_WEEKLY = 12;
    public const NOTIFY_MESSAGE_NO = 0;

    // mail_notify_invitation ("At once", "Daily", "No")
    public const NOTIFY_INVITATION_AT_ONCE = 1;
    public const NOTIFY_INVITATION_DAILY = 8;
    public const NOTIFY_INVITATION_WEEKLY = 12;
    public const NOTIFY_INVITATION_NO = 0;

    // mail_notify_group_message ("At once", "Daily", "No")
    public const NOTIFY_GROUP_AT_ONCE = 1;
    public const NOTIFY_GROUP_DAILY = 8;
    public const NOTIFY_GROUP_WEEKLY = 12;
    public const NOTIFY_GROUP_NO = 0;

    // Notification types
    public const NOTIFICATION_TYPE_MESSAGE = 1;
    public const NOTIFICATION_TYPE_INVITATION = 2;
    public const NOTIFICATION_TYPE_GROUP = 3;
    public const NOTIFICATION_TYPE_WALL_MESSAGE = 4;
    public const NOTIFICATION_TYPE_DIRECT_MESSAGE = 5;
    public $table;
    public $columns = [
        'id',
        'dest_user_id',
        'dest_mail',
        'title',
        'content',
        'send_freq',
        'created_at',
        'sent_at',
    ];

    //Max length of the notification.content field
    public $max_content_length = 254;
    public $debug = false;

    /* message, invitation, group messages */
    public $type;
    public $adminName;
    public $adminEmail;
    public $titlePrefix;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_NOTIFICATION);
        if (!empty(api_get_mail_configuration_value('SMTP_FROM_EMAIL'))) {
            $this->adminEmail = api_get_mail_configuration_value('SMTP_FROM_EMAIL');
            if (!empty(api_get_mail_configuration_value('SMTP_FROM_NAME'))) {
                $this->adminName = api_get_mail_configuration_value('SMTP_FROM_NAME');
            }
        } else {
            // Default no-reply email
            $this->adminEmail = api_get_setting('noreply_email_address');
            $this->adminName = api_get_setting('siteName');
            $this->titlePrefix = '['.api_get_setting('siteName').'] ';

            // If no-reply email doesn't exist use the admin name/email
            if (empty($this->adminEmail)) {
                $this->adminEmail = api_get_setting('emailAdministrator');
                $this->adminName = api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname'),
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
            }
        }
    }

    /**
     * @return string
     */
    public function getTitlePrefix()
    {
        return $this->titlePrefix;
    }

    /**
     * @return string
     */
    public function getDefaultPlatformSenderEmail()
    {
        return $this->adminEmail;
    }

    /**
     * @return string
     */
    public function getDefaultPlatformSenderName()
    {
        return $this->adminName;
    }

    /**
     *  Send the notifications.
     *
     *  @param int $frequency notification frequency
     */
    public function send($frequency = 8)
    {
        $notifications = $this->find(
            'all',
            ['where' => ['sent_at IS NULL AND send_freq = ?' => $frequency]]
        );

        if (!empty($notifications)) {
            foreach ($notifications as $item_to_send) {
                // Sending email
                api_mail_html(
                    $item_to_send['dest_mail'],
                    $item_to_send['dest_mail'],
                    Security::filter_terms($item_to_send['title']),
                    Security::filter_terms($item_to_send['content']),
                    $this->adminName,
                    $this->adminEmail
                );
                if ($this->debug) {
                    error_log('Sending message to: '.$item_to_send['dest_mail']);
                }

                // Updating
                $item_to_send['sent_at'] = api_get_utc_datetime();
                $this->update($item_to_send);
                if ($this->debug) {
                    error_log('Updating record : '.print_r($item_to_send, 1));
                }
            }
        }
    }

    /**
     * @param string $title
     * @param array  $senderInfo
     * @param bool   $forceTitleWhenSendingEmail force the use of $title as subject instead of "You have a new message"
     *
     * @return string
     */
    public function formatTitle($title, $senderInfo, $forceTitleWhenSendingEmail = false)
    {
        $hook = HookNotificationTitle::create();
        if (!empty($hook)) {
            $hook->setEventData(['title' => $title]);
            $data = $hook->notifyNotificationTitle(HOOK_EVENT_TYPE_PRE);
            if (isset($data['title'])) {
                $title = $data['title'];
            }
        }

        $newTitle = $this->getTitlePrefix();

        switch ($this->type) {
            case self::NOTIFICATION_TYPE_MESSAGE:
                if (!empty($senderInfo)) {
                    $senderName = api_get_person_name(
                        $senderInfo['firstname'],
                        $senderInfo['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $newTitle .= sprintf(get_lang('YouHaveANewMessageFromX'), $senderName);
                }
                break;
            case self::NOTIFICATION_TYPE_DIRECT_MESSAGE:
                $newTitle = $title;
                break;
            case self::NOTIFICATION_TYPE_INVITATION:
                if (!empty($senderInfo)) {
                    $senderName = api_get_person_name(
                        $senderInfo['firstname'],
                        $senderInfo['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $newTitle .= sprintf(get_lang('YouHaveANewInvitationFromX'), $senderName);
                }
                break;
            case self::NOTIFICATION_TYPE_GROUP:
                if (!empty($senderInfo)) {
                    $senderName = $senderInfo['group_info']['name'];
                    $newTitle .= sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $senderName);
                    $senderName = api_get_person_name(
                        $senderInfo['user_info']['firstname'],
                        $senderInfo['user_info']['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $newTitle .= $senderName;
                }
                break;
        }

        // The title won't be changed, it will be used as is
        if ($forceTitleWhenSendingEmail) {
            $newTitle = $title;
        }

        if (!empty($hook)) {
            $hook->setEventData(['title' => $newTitle]);
            $data = $hook->notifyNotificationTitle(HOOK_EVENT_TYPE_POST);
            if (isset($data['title'])) {
                $newTitle = $data['title'];
            }
        }

        return $newTitle;
    }

    /**
     * Save message notification.
     *
     * @param int    $type                       message type
     *                                           NOTIFICATION_TYPE_MESSAGE,
     *                                           NOTIFICATION_TYPE_INVITATION,
     *                                           NOTIFICATION_TYPE_GROUP
     * @param int    $messageId
     * @param array  $userList                   recipients: user list of ids
     * @param string $title
     * @param string $content
     * @param array  $senderInfo                 result of api_get_user_info() or GroupPortalManager:get_group_data()
     * @param array  $attachments
     * @param array  $smsParameters
     * @param bool   $forceTitleWhenSendingEmail force the use of $title as subject instead of "You have a new message"
     * @param bool   $checkUrls                  It checks access url of user when multiple_access_urls = true
     */
    public function saveNotification(
        $messageId,
        $type,
        $userList,
        $title,
        $content,
        $senderInfo = [],
        $attachments = [],
        $smsParameters = [],
        $forceTitleWhenSendingEmail = false,
        $checkUrls = false,
        $courseId = null
    ) {
        $this->type = (int) $type;
        $messageId = (int) $messageId;
        $content = $this->formatContent($messageId, $content, $senderInfo, $checkUrls, $courseId);
        $titleToNotification = $this->formatTitle($title, $senderInfo, $forceTitleWhenSendingEmail);
        $settingToCheck = '';
        $avoid_my_self = false;

        switch ($this->type) {
            case self::NOTIFICATION_TYPE_DIRECT_MESSAGE:
            case self::NOTIFICATION_TYPE_MESSAGE:
                $settingToCheck = 'mail_notify_message';
                $defaultStatus = self::NOTIFY_MESSAGE_AT_ONCE;
                break;
            case self::NOTIFICATION_TYPE_INVITATION:
                $settingToCheck = 'mail_notify_invitation';
                $defaultStatus = self::NOTIFY_INVITATION_AT_ONCE;
                break;
            case self::NOTIFICATION_TYPE_GROUP:
                $settingToCheck = 'mail_notify_group_message';
                $defaultStatus = self::NOTIFY_GROUP_AT_ONCE;
                $avoid_my_self = true;
                break;
            default:
                $defaultStatus = self::NOTIFY_MESSAGE_AT_ONCE;
                break;
        }

        $settingInfo = UserManager::get_extra_field_information_by_name($settingToCheck);

        if (!empty($userList)) {
            foreach ($userList as $user_id) {
                if ($avoid_my_self) {
                    if ($user_id == api_get_user_id()) {
                        continue;
                    }
                }
                $userInfo = api_get_user_info($user_id);

                // Extra field was deleted or removed? Use the default status.
                $userSetting = $defaultStatus;

                if (!empty($settingInfo)) {
                    $extra_data = UserManager::get_extra_user_data_by_field($user_id, $settingToCheck);

                    if (isset($extra_data[$settingToCheck])) {
                        $userSetting = $extra_data[$settingToCheck];
                    }

                    // Means that user extra was not set
                    // Then send email now.
                    if ($userSetting === '') {
                        $userSetting = self::NOTIFY_MESSAGE_AT_ONCE;
                    }
                }

                $sendDate = null;
                switch ($userSetting) {
                    // No notifications
                    case self::NOTIFY_MESSAGE_NO:
                    case self::NOTIFY_INVITATION_NO:
                    case self::NOTIFY_GROUP_NO:
                        break;
                    // Send notification right now!
                    case self::NOTIFY_MESSAGE_AT_ONCE:
                    case self::NOTIFY_INVITATION_AT_ONCE:
                    case self::NOTIFY_GROUP_AT_ONCE:
                        $extraHeaders = [];
                        if (isset($senderInfo['email'])) {
                            $extraHeaders = [
                                'reply_to' => [
                                    'name' => $senderInfo['complete_name'],
                                    'mail' => $senderInfo['email'],
                                ],
                            ];
                        }

                        if (!empty($userInfo['email'])) {
                            if ($checkUrls) {
                                $smsParameters['checkUrls'] = true;
                                $smsParameters['userId'] = $senderInfo['user_id'];
                                $smsParameters['courseId'] = $courseId;
                            }
                            api_mail_html(
                                $userInfo['complete_name'],
                                $userInfo['mail'],
                                Security::filter_terms($titleToNotification),
                                Security::filter_terms($content),
                                !empty($senderInfo['complete_name']) ? $senderInfo['complete_name'] : $this->adminName,
                                !empty($senderInfo['email']) ? $senderInfo['email'] : $this->adminEmail,
                                $extraHeaders,
                                $attachments,
                                false,
                                $smsParameters
                            );
                        }
                        $sendDate = api_get_utc_datetime();
                }

                // Saving the notification to be sent some day.
                $content = cut($content, $this->max_content_length);
                $params = [
                    'sent_at' => $sendDate,
                    'dest_user_id' => $user_id,
                    'dest_mail' => $userInfo['email'],
                    'title' => $title,
                    'content' => $content,
                    'send_freq' => $userSetting,
                ];

                $this->save($params);
            }

            self::sendPushNotification($userList, $title, $content);
        }
    }

    /**
     * Formats the content in order to add the welcome message,
     * the notification preference, etc.
     *
     * @param int    $messageId
     * @param string $content
     * @param array  $senderInfo result of api_get_user_info() or
     *                           GroupPortalManager:get_group_data()
     * @param bool   $checkUrls  It checks access url of user when multiple_access_urls = true
     * @param int    $courseId   The course id will be checked when checkUrls = true
     *
     * @return string
     * */
    public function formatContent(
        $messageId,
        $content,
        $senderInfo,
        $checkUrls = false,
        $courseId = null
    ) {
        $hook = HookNotificationContent::create();
        if (!empty($hook)) {
            $hook->setEventData(['content' => $content]);
            $data = $hook->notifyNotificationContent(HOOK_EVENT_TYPE_PRE);
            if (isset($data['content'])) {
                $content = $data['content'];
            }
        }

        $accessConfig = [];
        $useMultipleUrl = api_get_configuration_value('multiple_access_urls');
        if ($useMultipleUrl && $checkUrls) {
            $accessUrls = api_get_access_url_from_user($senderInfo['user_id'], $courseId);
            if (!empty($accessUrls)) {
                $accessConfig['multiple_access_urls'] = true;
                $accessConfig['access_url'] = (int) $accessUrls[0];
            }
            // To replace the current url by access url user
            $content = str_replace(api_get_path(WEB_PATH), api_get_path(WEB_PATH, $accessConfig), $content);
        }

        $newMessageText = $linkToNewMessage = '';
        $showEmail = api_get_configuration_value('show_user_email_in_notification');
        $senderInfoName = '';
        if (!empty($senderInfo) && isset($senderInfo['complete_name'])) {
            $senderInfoName = $senderInfo['complete_name'];
            if ($showEmail && isset($senderInfo['complete_name_with_email_forced'])) {
                $senderInfoName = $senderInfo['complete_name_with_email_forced'];
            }
        }

        switch ($this->type) {
            case self::NOTIFICATION_TYPE_DIRECT_MESSAGE:
                $newMessageText = '';
                $linkToNewMessage = Display::url(
                    get_lang('SeeMessage'),
                    api_get_path(WEB_CODE_PATH, $accessConfig).'messages/view_message.php?id='.$messageId
                );
                break;
            case self::NOTIFICATION_TYPE_MESSAGE:
                $allow = api_get_configuration_value('messages_hide_mail_content');
                if ($allow) {
                    $content = '';
                }
                if (!empty($senderInfo)) {
                    $newMessageText = sprintf(
                        get_lang('YouHaveANewMessageFromX'),
                        $senderInfoName
                    );
                }
                $linkToNewMessage = Display::url(
                    get_lang('SeeMessage'),
                    api_get_path(WEB_CODE_PATH, $accessConfig).'messages/view_message.php?id='.$messageId
                );
                break;
            case self::NOTIFICATION_TYPE_INVITATION:
                if (!empty($senderInfo)) {
                    $newMessageText = sprintf(
                        get_lang('YouHaveANewInvitationFromX'),
                        $senderInfoName
                    );
                }
                $linkToNewMessage = Display::url(
                    get_lang('SeeInvitation'),
                    api_get_path(WEB_CODE_PATH, $accessConfig).'social/invitations.php'
                );
                break;
            case self::NOTIFICATION_TYPE_GROUP:
                $topicPage = isset($_REQUEST['topics_page_nr']) ? (int) $_REQUEST['topics_page_nr'] : 0;
                if (!empty($senderInfo)) {
                    $senderName = $senderInfo['group_info']['name'];
                    $newMessageText = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $senderName);
                    $senderName = Display::url(
                        $senderInfoName,
                        api_get_path(WEB_CODE_PATH, $accessConfig).'social/profile.php?'.$senderInfo['user_info']['user_id']
                    );
                    $newMessageText .= '<br />'.get_lang('User').': '.$senderName;
                }
                $groupUrl = api_get_path(WEB_CODE_PATH, $accessConfig).'social/group_topics.php?id='.$senderInfo['group_info']['id'].'&topic_id='.$senderInfo['group_info']['topic_id'].'&msg_id='.$senderInfo['group_info']['msg_id'].'&topics_page_nr='.$topicPage;
                $linkToNewMessage = Display::url(get_lang('SeeMessage'), $groupUrl);
                break;
        }
        $preferenceUrl = api_get_path(WEB_CODE_PATH, $accessConfig).'auth/profile.php';

        // You have received a new message text
        if (!empty($newMessageText)) {
            $content = $newMessageText.'<br /><hr><br />'.$content;
        }

        // See message with link text
        if (!empty($linkToNewMessage) && api_get_setting('allow_message_tool') == 'true') {
            $content = $content.'<br /><br />'.$linkToNewMessage;
        }

        // You have received this message because you are subscribed text
        $content = $content.'<br /><hr><i>'.
            sprintf(
                get_lang('YouHaveReceivedThisNotificationBecauseYouAreSubscribedOrInvolvedInItToChangeYourNotificationPreferencesPleaseClickHereX'),
                Display::url($preferenceUrl, $preferenceUrl)
            ).'</i>';

        if (!empty($hook)) {
            $hook->setEventData(['content' => $content]);
            $data = $hook->notifyNotificationContent(HOOK_EVENT_TYPE_POST);
            if (isset($data['content'])) {
                $content = $data['content'];
            }
        }

        return $content;
    }

    /**
     * Send the push notifications to Chamilo Mobile app.
     *
     * @param array  $userIds The IDs of users who will be notified
     * @param string $title   The notification title
     * @param string $content The notification content
     *
     * @return int The number of success notifications. Otherwise returns false
     */
    public static function sendPushNotification(array $userIds, $title, $content)
    {
        if (api_get_setting('messaging_allow_send_push_notification') !== 'true') {
            return false;
        }

        $gdcApiKey = api_get_setting('messaging_gdc_api_key');

        if ($gdcApiKey === false) {
            return false;
        }

        $content = strip_tags($content);
        $content = explode("\n", $content);
        $content = array_map('trim', $content);
        $content = array_filter($content);
        $content = implode(PHP_EOL, $content);

        $gcmRegistrationIds = [];
        foreach ($userIds as $userId) {
            $extraFieldValue = new ExtraFieldValue('user');
            $valueInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
                $userId,
                Rest::EXTRA_FIELD_GCM_REGISTRATION
            );

            if (empty($valueInfo)) {
                continue;
            }

            $gcmRegistrationIds[] = $valueInfo['value'];
        }

        if (!$gcmRegistrationIds) {
            return 0;
        }

        $headers = [
            'Authorization: key='.$gdcApiKey,
            'Content-Type: application/json',
        ];

        $fields = json_encode([
            'registration_ids' => $gcmRegistrationIds,
            'data' => [
                'title' => $title,
                'message' => $content,
                'body' => $content,
                'sound' => 'default',
            ],
            'notification' => [
                'title' => $title,
                'body' => $content,
                'sound' => 'default',
            ],
            'collapse_key' => get_lang('Messages'),
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);

        /** @var array $decodedResult */
        $decodedResult = json_decode($result, true);

        return intval($decodedResult['success']);
    }
}
