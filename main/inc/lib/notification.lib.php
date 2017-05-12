<?php
/* For licensing terms, see /license.txt */

/**
 * Notification class
 * This class provides methods for the Notification management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 */
class Notification extends Model
{
    public $table;
    public $columns = array(
        'id',
        'dest_user_id',
        'dest_mail',
        'title',
        'content',
        'send_freq',
        'created_at',
        'sent_at'
    );

    //Max length of the notification.content field
    public $max_content_length = 254;
    public $debug = false;

    /* message, invitation, group messages */
    public $type;
    public $adminName;
    public $adminEmail;
    public $titlePrefix;

    // mail_notify_message ("At once", "Daily", "No")
    const NOTIFY_MESSAGE_AT_ONCE = 1;
    const NOTIFY_MESSAGE_DAILY = 8;
    const NOTIFY_MESSAGE_WEEKLY = 12;
    const NOTIFY_MESSAGE_NO = 0;

    // mail_notify_invitation ("At once", "Daily", "No")
    const NOTIFY_INVITATION_AT_ONCE = 1;
    const NOTIFY_INVITATION_DAILY = 8;
    const NOTIFY_INVITATION_WEEKLY = 12;
    const NOTIFY_INVITATION_NO = 0;

    // mail_notify_group_message ("At once", "Daily", "No")
    const NOTIFY_GROUP_AT_ONCE = 1;
    const NOTIFY_GROUP_DAILY = 8;
    const NOTIFY_GROUP_WEEKLY = 12;
    const NOTIFY_GROUP_NO = 0;

    // Notification types
    const NOTIFICATION_TYPE_MESSAGE = 1;
    const NOTIFICATION_TYPE_INVITATION = 2;
    const NOTIFICATION_TYPE_GROUP = 3;
    const NOTIFICATION_TYPE_WALL_MESSAGE = 4;
    const NOTIFICATION_TYPE_DIRECT_MESSAGE = 5;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_NOTIFICATION);
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
     *  Send the notifications
     *  @param int $frequency notification frequency
     */
    public function send($frequency = 8)
    {
        $notifications = $this->find(
            'all',
            array('where' => array('sent_at IS NULL AND send_freq = ?' => $frequency))
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
     *
     * @return string
     */
    public function formatTitle($title, $senderInfo)
    {
        $hook = HookNotificationTitle::create();
        if (!empty($hook)) {
            $hook->setEventData(array('title' => $title));
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

        if (!empty($hook)) {
            $hook->setEventData(array('title' => $newTitle));
            $data = $hook->notifyNotificationTitle(HOOK_EVENT_TYPE_POST);
            if (isset($data['title'])) {
                $newTitle = $data['title'];
            }
        }

        return $newTitle;
    }

    /**
     * Save message notification
     * @param int	    $type message type
     * NOTIFICATION_TYPE_MESSAGE,
     * NOTIFICATION_TYPE_INVITATION,
     * NOTIFICATION_TYPE_GROUP
     * @param array	    $userList recipients: user list of ids
     * @param string	$title
     * @param string	$content
     * @param array	    $senderInfo result of api_get_user_info() or GroupPortalManager:get_group_data()
     * @param array	    $attachments
     *
     */
    public function save_notification(
        $type,
        $userList,
        $title,
        $content,
        $senderInfo = array(),
        $attachments = array()
    ) {
        $this->type = intval($type);
        $content = $this->formatContent($content, $senderInfo);
        $titleToNotification = $this->formatTitle($title, $senderInfo);

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
                    $extra_data = UserManager::get_extra_user_data($user_id);

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
                            $extraHeaders = array(
                                'reply_to' => array(
                                    'name' => $senderInfo['complete_name'],
                                    'mail' => $senderInfo['email']
                                )
                            );
                        }

                        if (!empty($userInfo['email'])) {
                            api_mail_html(
                                $userInfo['complete_name'],
                                $userInfo['mail'],
                                Security::filter_terms($titleToNotification),
                                Security::filter_terms($content),
                                $this->adminName,
                                $this->adminEmail,
                                $extraHeaders,
                                $attachments
                            );
                        }
                        $sendDate = api_get_utc_datetime();
                }

                // Saving the notification to be sent some day.
                $content = cut($content, $this->max_content_length);
                $params = array(
                    'sent_at' => $sendDate,
                    'dest_user_id' => $user_id,
                    'dest_mail' => $userInfo['email'],
                    'title' => $title,
                    'content' => $content,
                    'send_freq' => $userSetting
                );

                $this->save($params);
            }

            self::sendPushNotification($userList, $title, $content);
        }
    }

    /**
     * Formats the content in order to add the welcome message,
     * the notification preference, etc
     * @param string $content
     * @param array  $senderInfo result of api_get_user_info() or
     * GroupPortalManager:get_group_data()
     *
     * @return string
     * */
    public function formatContent($content, $senderInfo)
    {
        $hook = HookNotificationContent::create();
        if (!empty($hook)) {
            $hook->setEventData(array('content' => $content));
            $data = $hook->notifyNotificationContent(HOOK_EVENT_TYPE_PRE);
            if (isset($data['content'])) {
                $content = $data['content'];
            }
        }

        $newMessageText = $linkToNewMessage = '';

        switch ($this->type) {
            case self::NOTIFICATION_TYPE_DIRECT_MESSAGE:
                $newMessageText = '';
                $linkToNewMessage = Display::url(
                    get_lang('SeeMessage'),
                    api_get_path(WEB_CODE_PATH).'messages/inbox.php'
                );
                break;
            case self::NOTIFICATION_TYPE_MESSAGE:
                if (!empty($senderInfo)) {
                    $senderName = api_get_person_name(
                        $senderInfo['firstname'],
                        $senderInfo['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $newMessageText = sprintf(get_lang('YouHaveANewMessageFromX'), $senderName);
                }
                $linkToNewMessage = Display::url(
                    get_lang('SeeMessage'),
                    api_get_path(WEB_CODE_PATH).'messages/inbox.php'
                );
                break;
            case self::NOTIFICATION_TYPE_INVITATION:
                if (!empty($senderInfo)) {
                    $senderName = api_get_person_name(
                        $senderInfo['firstname'],
                        $senderInfo['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $newMessageText = sprintf(get_lang('YouHaveANewInvitationFromX'), $senderName);
                }
                $linkToNewMessage = Display::url(
                    get_lang('SeeInvitation'),
                    api_get_path(WEB_CODE_PATH).'social/invitations.php'
                );
                break;
            case self::NOTIFICATION_TYPE_GROUP:
                $topic_page = intval($_REQUEST['topics_page_nr']);
                if (!empty($senderInfo)) {
                    $senderName = $senderInfo['group_info']['name'];
                    $newMessageText = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $senderName);
                    $senderName = api_get_person_name(
                        $senderInfo['user_info']['firstname'],
                        $senderInfo['user_info']['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $senderName = Display::url(
                        $senderName,
                        api_get_path(WEB_CODE_PATH).'social/profile.php?'.$senderInfo['user_info']['user_id']
                    );
                    $newMessageText .= '<br />'.get_lang('User').': '.$senderName;
                }
                $group_url = api_get_path(WEB_CODE_PATH).'social/group_topics.php?id='.$senderInfo['group_info']['id'].'&topic_id='.$senderInfo['group_info']['topic_id'].'&msg_id='.$senderInfo['group_info']['msg_id'].'&topics_page_nr='.$topic_page;
                $linkToNewMessage = Display::url(get_lang('SeeMessage'), $group_url);
                break;
        }
        $preference_url = api_get_path(WEB_CODE_PATH).'auth/profile.php';

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
                Display::url($preference_url, $preference_url)
            ).'</i>';

        if (!empty($hook)) {
            $hook->setEventData(array('content' => $content));
            $data = $hook->notifyNotificationContent(HOOK_EVENT_TYPE_POST);
            if (isset($data['content'])) {
                $content = $data['content'];
            }
        }

        return $content;
    }

    /**
     * Send the push notifications to Chamilo Mobile app
     * @param array $userIds The IDs of users who will be notified
     * @param string $title The notification title
     * @param string $content The notification content
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

        $content = str_replace(['<br>', '<br/>', '<br />'], "\n", $content);
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES);

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
            'Content-Type: application/json'
        ];

        $fields = json_encode([
            'registration_ids' => $gcmRegistrationIds,
            'data' => [
                'title' => $title,
                'message' => $content
            ]
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
