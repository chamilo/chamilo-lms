<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Tag as TagEntity;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;

/**
 * Class MessageManager.
 *
 * This class provides methods for messages management.
 * Include/require it in your code to use its features.
 */
class MessageManager
{
    public const MESSAGE_TYPE_INBOX = 1;
    public const MESSAGE_TYPE_OUTBOX = 2;
    public const MESSAGE_TYPE_PROMOTED = 3;

    /**
     * Get count new messages for the current user from the database.
     *
     * @return int
     */
    public static function getCountNewMessages()
    {
        $userId = api_get_user_id();
        if (empty($userId)) {
            return false;
        }

        static $count;
        if (!isset($count)) {
            $cacheAvailable = api_get_configuration_value('apc');
            if ($cacheAvailable === true) {
                $var = api_get_configuration_value('apc_prefix').'social_messages_unread_u_'.$userId;
                if (apcu_exists($var)) {
                    $count = apcu_fetch($var);
                } else {
                    $count = self::getCountNewMessagesFromDB($userId);
                    apcu_store($var, $count, 60);
                }
            } else {
                $count = self::getCountNewMessagesFromDB($userId);
            }
        }

        return $count;
    }

    /**
     * Gets the total number of messages, used for the inbox sortable table.
     *
     * @param array $params
     *
     * @return int
     */
    public static function getNumberOfMessages($params)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $conditions = self::getWhereConditions($params);

        $sql = "SELECT COUNT(DISTINCT m.id) as number_messages
                FROM $table m";

        if (true === api_get_configuration_value('enable_message_tags')) {
            $tblExtraFielRelTag = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
            $tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);

            $sql .= "
                LEFT JOIN $tblExtraFielRelTag efrt ON efrt.item_id = m.id
                LEFT JOIN $tblExtraField ef ON ef.id = efrt.field_id AND ef.variable = 'tags'";
        }

        $sql .= "
                WHERE
                    $conditions
                ";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        if ($result) {
            return (int) $result['number_messages'];
        }

        return 0;
    }

    /**
     * @param array $extraParams
     *
     * @return string
     */
    public static function getWhereConditions($extraParams)
    {
        $userId = api_get_user_id();

        $keyword = isset($extraParams['keyword']) && !empty($extraParams['keyword']) ? $extraParams['keyword'] : '';
        $type = isset($extraParams['type']) && !empty($extraParams['type']) ? $extraParams['type'] : '';
        $tags = $extraParams['tags'] ?? [];

        if (empty($type)) {
            return '';
        }

        switch ($type) {
            case self::MESSAGE_TYPE_INBOX:
                $statusList = [MESSAGE_STATUS_NEW, MESSAGE_STATUS_UNREAD];
                $userCondition = " m.user_receiver_id = $userId AND";
                break;
            case self::MESSAGE_TYPE_OUTBOX:
                $statusList = [MESSAGE_STATUS_OUTBOX];
                $userCondition = " m.user_sender_id = $userId AND";
                break;
            case self::MESSAGE_TYPE_PROMOTED:
                $statusList = [MESSAGE_STATUS_PROMOTED];
                $userCondition = " m.user_receiver_id = $userId AND";
                break;
        }

        if (empty($statusList)) {
            return '';
        }

        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (m.title like '%$keyword%' OR m.content LIKE '%$keyword%') ";
        }
        $messageStatusCondition = implode("','", $statusList);

        $tagsCondition = '';

        if (true === api_get_configuration_value('enable_message_tags') && !empty($tags)) {
            $tagsCondition = ' AND efrt.tag_id IN ('.implode(', ', $tags).") ";
        }

        return " $userCondition
                 m.msg_status IN ('$messageStatusCondition')
                 $keywordCondition
                 $tagsCondition";
    }

    /**
     * Gets information about some messages, used for the inbox sortable table.
     *
     * @param int    $from
     * @param int    $numberOfItems
     * @param int    $column
     * @param string $direction
     * @param array  $extraParams
     *
     * @return array
     */
    public static function getMessageData(
        $from,
        $numberOfItems,
        $column,
        $direction,
        $extraParams = []
    ) {
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;
        // Forcing this order.
        if (!isset($direction)) {
            $column = 2;
            $direction = 'DESC';
        } else {
            $column = (int) $column;
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }
        }

        if (!in_array($column, [0, 1, 2])) {
            $column = 2;
        }

        $type = isset($extraParams['type']) && !empty($extraParams['type']) ? $extraParams['type'] : '';

        if (empty($type)) {
            return [];
        }

        $viewUrl = '';
        switch ($type) {
            case self::MESSAGE_TYPE_OUTBOX:
            case self::MESSAGE_TYPE_INBOX:
                $viewUrl = api_get_path(WEB_CODE_PATH).'messages/view_message.php';
                break;
            case self::MESSAGE_TYPE_PROMOTED:
                $viewUrl = api_get_path(WEB_CODE_PATH).'social/view_promoted_message.php';
                break;
        }
        $viewUrl .= '?type='.$type;

        $whereConditions = self::getWhereConditions($extraParams);

        if (empty($whereConditions)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT DISTINCT
                    m.id as col0,
                    m.title as col1,
                    m.send_date as col2,
                    m.msg_status as col3,
                    m.user_sender_id,
                    m.user_receiver_id
                FROM $table m";

        if (true === api_get_configuration_value('enable_message_tags')) {
            $tblExtraFielRelTag = Database::get_main_table(TABLE_MAIN_EXTRA_FIELD_REL_TAG);
            $tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);

            $sql .= "
                LEFT JOIN $tblExtraFielRelTag efrt ON efrt.item_id = m.id
                LEFT JOIN $tblExtraField ef ON ef.id = efrt.field_id AND ef.variable = 'tags'";
        }

        $sql .= "
                WHERE
                    $whereConditions
                ORDER BY col$column $direction
                LIMIT $from, $numberOfItems";

        $result = Database::query($sql);
        $messageList = [];
        $newMessageLink = api_get_path(WEB_CODE_PATH).'messages/new_message.php';

        $actions = $extraParams['actions'];
        $url = api_get_self();

        $objExtraField = new ExtraField('message');
        $extrafieldTags = $objExtraField->getHandlerEntityByFieldVariable('tags');
        $efrtRepo = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $messageId = $row['col0'];
            $title = $row['col1'];
            $sendDate = $row['col2'];
            $status = $row['col3'];
            $senderId = $row['user_sender_id'];
            $receiverId = $row['user_receiver_id'];

            $title = Security::remove_XSS($title, STUDENT, true);
            $title = cut($title, 80, true);

            $class = 'class = "read"';
            if (1 == $status) {
                $class = 'class = "unread"';
            }

            $userInfo = api_get_user_info($senderId);
            if ($type == self::MESSAGE_TYPE_OUTBOX) {
                $userInfo = api_get_user_info($receiverId);
            }
            $message[3] = '';
            if (!empty($senderId) && !empty($userInfo)) {
                $message[1] = '<a '.$class.' href="'.$viewUrl.'&id='.$messageId.'">'.$title.'</a><br />';
                $message[1] .= Display::tag('small', $userInfo['complete_name_with_username']);
                if (in_array('reply', $actions)) {
                    $message[3] =
                        Display::url(
                            Display::returnFontAwesomeIcon('reply', 2),
                            $newMessageLink.'?re_id='.$messageId,
                            ['title' => get_lang('ReplyToMessage')]
                        );
                }
            } else {
                $message[1] = '<a '.$class.' href="'.$viewUrl.'&id='.$messageId.'">'.$title.'</a><br />';
                $message[1] .= get_lang('UnknownUser');
                if (in_array('reply', $actions)) {
                    $message[3] =
                        Display::url(
                            Display::returnFontAwesomeIcon('reply', 2),
                            '#',
                            ['title' => get_lang('ReplyToMessage')]
                        );
                }
            }

            if (in_array($type, [self::MESSAGE_TYPE_INBOX, self::MESSAGE_TYPE_OUTBOX])
                && api_get_configuration_value('enable_message_tags')
                && $extrafieldTags
            ) {
                $tags = $efrtRepo->getTags($extrafieldTags, $messageId);
                $tagsBadges = array_map(
                    function (TagEntity $tag) {
                        return Display::badge($tag->getTag(), 'default');
                    },
                    $tags
                );

                $message[1] .= Display::badge_group($tagsBadges);
            }

            $message[0] = $messageId;
            $message[2] = api_convert_and_format_date($sendDate, DATE_TIME_FORMAT_LONG);

            // Actions
            if (in_array('edit', $actions)) {
                $message[3] .=
                    '&nbsp;&nbsp;'.
                    Display::url(
                        Display::returnFontAwesomeIcon('pencil', 2),
                        $newMessageLink.'?action=edit&id='.$messageId,
                        ['title' => get_lang('ForwardMessage')]
                    );
            }

            // Actions
            if (in_array('forward', $actions)) {
                $message[3] .=
                    '&nbsp;&nbsp;'.
                    Display::url(
                        Display::returnFontAwesomeIcon('share', 2),
                        $newMessageLink.'?forward_id='.$messageId,
                        ['title' => get_lang('ForwardMessage')]
                    );
            }

            if (in_array('delete', $actions)) {
                $message[3] .= '&nbsp;&nbsp;<a title="'.addslashes(
                    get_lang('DeleteMessage')
                ).'" onclick="javascript:if(!confirm('."'".addslashes(
                    api_htmlentities(get_lang('ConfirmDeleteMessage'))
                )."'".')) return false;" href="'.$url.'?action=deleteone&id='.$messageId.'">'.
                Display::returnFontAwesomeIcon('trash', 2).'</a>';
            }

            foreach ($message as $key => $value) {
                $message[$key] = api_xml_http_response_encode($value);
            }
            $messageList[] = $message;
        }

        return $messageList;
    }

    /**
     * @param array  $aboutUserInfo
     * @param array  $fromUserInfo
     * @param string $subject
     * @param string $content
     *
     * @return bool
     */
    public static function sendMessageAboutUser(
        $aboutUserInfo,
        $fromUserInfo,
        $subject,
        $content
    ) {
        if (empty($aboutUserInfo) || empty($fromUserInfo)) {
            return false;
        }

        if (empty($fromUserInfo['id']) || empty($aboutUserInfo['id'])) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $now = api_get_utc_datetime();
        $params = [
            'user_sender_id' => $fromUserInfo['id'],
            'user_receiver_id' => $aboutUserInfo['id'],
            'msg_status' => MESSAGE_STATUS_CONVERSATION,
            'send_date' => $now,
            'title' => $subject,
            'content' => $content,
            'group_id' => 0,
            'parent_id' => 0,
            'update_date' => $now,
        ];
        $id = Database::insert($table, $params);

        if ($id) {
            return true;
        }

        return false;
    }

    /**
     * @param array $aboutUserInfo
     *
     * @return array
     */
    public static function getMessagesAboutUser($aboutUserInfo)
    {
        if (!empty($aboutUserInfo)) {
            $table = Database::get_main_table(TABLE_MESSAGE);
            $sql = 'SELECT id FROM '.$table.'
                    WHERE
                      user_receiver_id = '.$aboutUserInfo['id'].' AND
                      msg_status = '.MESSAGE_STATUS_CONVERSATION.'
                    ';
            $result = Database::query($sql);
            $messages = [];
            $repo = Database::getManager()->getRepository('ChamiloCoreBundle:Message');
            while ($row = Database::fetch_array($result)) {
                $message = $repo->find($row['id']);
                $messages[] = $message;
            }

            return $messages;
        }

        return [];
    }

    /**
     * @param array $userInfo
     *
     * @return string
     */
    public static function getMessagesAboutUserToString($userInfo, $origin = null)
    {
        $messages = self::getMessagesAboutUser($userInfo);
        $html = '';
        if (!empty($messages)) {
            /** @var Message $message */
            foreach ($messages as $message) {
                $tag = 'message_'.$message->getId();
                $tagAccordion = 'accordion_'.$message->getId();
                $tagCollapse = 'collapse_'.$message->getId();
                $date = Display::dateToStringAgoAndLongDate(
                    $message->getSendDate()
                );
                $localTime = api_get_local_time(
                    $message->getSendDate(),
                    null,
                    null,
                    false,
                    false
                );
                $senderId = $message->getUserSenderId();
                $senderInfo = api_get_user_info($senderId);
                $deleteLink = '';
                if ('my_space' == $origin && api_get_user_id() == $senderId) {
                    $deleteLink = '<a title="'.addslashes(
                            get_lang('DeleteMessage')
                        ).'" href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$userInfo['id'].'&action=delete_msg&msg_id='.$message->getId().'"  onclick="javascript:if(!confirm('."'".addslashes(
                            api_htmlentities(get_lang('ConfirmDeleteMessage'))
                        )."'".')) return false;" >&nbsp;&nbsp;&nbsp;&nbsp;'.
                        Display::returnFontAwesomeIcon('trash', 1).'</a>';
                }
                $html .= Display::panelCollapse(
                    $localTime.' '.$senderInfo['complete_name'].' '.$message->getTitle().$deleteLink,
                    $message->getContent().'<br />'.$date.'<br />'.get_lang(
                        'Author'
                    ).': '.$senderInfo['complete_name_with_message_link'],
                    $tag,
                    null,
                    $tagAccordion,
                    $tagCollapse,
                    false
                );
            }
        }

        return $html;
    }

    /**
     * @param int    $senderId
     * @param int    $receiverId
     * @param string $subject
     * @param string $message
     *
     * @return bool
     */
    public static function messageWasAlreadySent($senderId, $receiverId, $subject, $message)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $senderId = (int) $senderId;
        $receiverId = (int) $receiverId;
        $subject = Database::escape_string($subject);
        $message = Database::escape_string($message);

        $sql = "SELECT * FROM $table
                WHERE
                    user_sender_id = $senderId AND
                    user_receiver_id = $receiverId AND
                    title = '$subject' AND
                    content = '$message' AND
                    (msg_status = ".MESSAGE_STATUS_UNREAD." OR msg_status = ".MESSAGE_STATUS_NEW.")
                ";
        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * Sends a message to a user/group.
     *
     * @param int    $receiverUserId
     * @param string $subject
     * @param string $content
     * @param array  $attachments                files array($_FILES) (optional)
     * @param array  $fileCommentList            about attachment files (optional)
     * @param int    $group_id                   (optional)
     * @param int    $parent_id                  (optional)
     * @param int    $editMessageId              id for updating the message (optional)
     * @param int    $topic_id                   (optional) the default value is the current user_id
     * @param int    $sender_id
     * @param bool   $directMessage
     * @param int    $forwardId
     * @param array  $smsParameters
     * @param bool   $checkCurrentAudioId
     * @param bool   $forceTitleWhenSendingEmail force the use of $title as subject instead of "You have a new message"
     * @param int    $status                     Message status
     * @param bool   $checkUrls                  It checks access url of user when multiple_access_urls = true
     *
     * @return bool
     */
    public static function send_message(
        $receiverUserId,
        $subject,
        $content,
        array $attachments = [],
        array $fileCommentList = [],
        $group_id = 0,
        $parent_id = 0,
        $editMessageId = 0,
        $topic_id = 0,
        $sender_id = 0,
        $directMessage = false,
        $forwardId = 0,
        $smsParameters = [],
        $checkCurrentAudioId = false,
        $forceTitleWhenSendingEmail = false,
        $status = 0,
        array $extraParams = [],
        $checkUrls = false,
        $courseId = null
    ) {
        $group_id = (int) $group_id;
        $receiverUserId = (int) $receiverUserId;
        $parent_id = (int) $parent_id;
        $editMessageId = (int) $editMessageId;
        $topic_id = (int) $topic_id;
        $status = empty($status) ? MESSAGE_STATUS_UNREAD : (int) $status;

        $sendEmail = true;
        if (!empty($receiverUserId)) {
            $receiverUserInfo = api_get_user_info($receiverUserId);
            if (empty($receiverUserInfo)) {
                return false;
            }

            // Disabling messages for inactive users.
            if (0 == $receiverUserInfo['active']) {
                return false;
            }

            // Disabling messages depending the pausetraining plugin.
            $allowPauseFormation =
                'true' === api_get_plugin_setting('pausetraining', 'tool_enable') &&
                'true' === api_get_plugin_setting('pausetraining', 'allow_users_to_edit_pause_formation');

            if ($allowPauseFormation) {
                $extraFieldValue = new ExtraFieldValue('user');
                $disableEmails = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $receiverUserId,
                    'disable_emails'
                );

                // User doesn't want email notifications but chamilo inbox still available.
                if (!empty($disableEmails) &&
                    isset($disableEmails['value']) && 1 === (int) $disableEmails['value']
                ) {
                    $sendEmail = false;
                }

                if ($sendEmail) {
                    // Check if user pause his formation.
                    $pause = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $receiverUserId,
                        'pause_formation'
                    );
                    if (!empty($pause) && isset($pause['value']) && 1 === (int) $pause['value']) {
                        $startDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                            $receiverUserInfo['user_id'],
                            'start_pause_date'
                        );
                        $endDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                            $receiverUserInfo['user_id'],
                            'end_pause_date'
                        );

                        if (
                            !empty($startDate) && isset($startDate['value']) && !empty($startDate['value']) &&
                            !empty($endDate) && isset($endDate['value']) && !empty($endDate['value'])
                        ) {
                            $now = time();
                            $start = api_strtotime($startDate['value']);
                            $end = api_strtotime($endDate['value']);

                            if ($now > $start && $now < $end) {
                                $sendEmail = false;
                            }
                        }
                    }
                }
            }
        }

        $user_sender_id = empty($sender_id) ? api_get_user_id() : (int) $sender_id;
        if (empty($user_sender_id)) {
            Display::addFlash(Display::return_message(get_lang('UserDoesNotExist'), 'warning'));

            return false;
        }

        $totalFileSize = 0;
        $attachmentList = [];
        if (is_array($attachments)) {
            $counter = 0;
            foreach ($attachments as $attachment) {
                $attachment['comment'] = isset($fileCommentList[$counter]) ? $fileCommentList[$counter] : '';
                $fileSize = isset($attachment['size']) ? $attachment['size'] : 0;
                if (is_array($fileSize)) {
                    foreach ($fileSize as $size) {
                        $totalFileSize += $size;
                    }
                } else {
                    $totalFileSize += $fileSize;
                }
                $attachmentList[] = $attachment;
                $counter++;
            }
        }

        if ($checkCurrentAudioId) {
            // Add the audio file as an attachment
            $audioId = Session::read('current_audio_id');
            if (!empty($audioId)) {
                $file = api_get_uploaded_file('audio_message', api_get_user_id(), $audioId);
                if (!empty($file)) {
                    $audioAttachment = [
                        'name' => basename($file),
                        'comment' => 'audio_message',
                        'size' => filesize($file),
                        'tmp_name' => $file,
                        'error' => 0,
                        'type' => DocumentManager::file_get_mime_type(basename($file)),
                    ];
                    // create attachment from audio message
                    $attachmentList[] = $audioAttachment;
                }
            }
        }

        // Validating fields
        if (empty($subject) && empty($group_id)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('YouShouldWriteASubject'),
                    'warning'
                )
            );

            return false;
        } elseif ($totalFileSize > (int) getIniMaxFileSizeInBytes(false, true)) {
            $warning = get_lang('FileSizeIsTooBig').' '.get_lang('MaxFileSize').' : '.getIniMaxFileSizeInBytes(true, true);
            Display::addFlash(Display::return_message($warning, 'error'));

            return false;
        }

        $now = api_get_utc_datetime();
        $table = Database::get_main_table(TABLE_MESSAGE);

        if (!empty($receiverUserId) || !empty($group_id)) {
            // message for user friend
            //@todo it's possible to edit a message? yes, only for groups
            if (!empty($editMessageId)) {
                $query = " UPDATE $table SET
                              update_date = '".$now."',
                              content = '".Database::escape_string($content)."'
                           WHERE id = '$editMessageId' ";
                Database::query($query);
                $messageId = $editMessageId;
            } else {
                $params = [
                    'user_sender_id' => $user_sender_id,
                    'user_receiver_id' => $receiverUserId,
                    'msg_status' => $status,
                    'send_date' => $now,
                    'title' => $subject,
                    'content' => $content,
                    'group_id' => $group_id,
                    'parent_id' => $parent_id,
                    'update_date' => $now,
                ];
                $messageId = Database::insert($table, $params);
            }

            // Forward also message attachments
            if (!empty($forwardId)) {
                $attachments = self::getAttachmentList($forwardId);
                foreach ($attachments as $attachment) {
                    if (!empty($attachment['file_source'])) {
                        $file = [
                            'name' => $attachment['filename'],
                            'tmp_name' => $attachment['file_source'],
                            'size' => $attachment['size'],
                            'error' => 0,
                            'comment' => $attachment['comment'],
                        ];

                        // Inject this array so files can be added when sending and email with the mailer
                        $attachmentList[] = $file;
                    }
                }
            }

            // Save attachment file for inbox messages
            if (is_array($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    if (0 == $attachment['error']) {
                        $comment = $attachment['comment'];
                        self::saveMessageAttachmentFile(
                            $attachment,
                            $comment,
                            $messageId,
                            null,
                            $receiverUserId,
                            $group_id
                        );
                    }
                }
            }

            // Save message in the outbox for user friend or group.
            if (empty($group_id) && MESSAGE_STATUS_UNREAD == $status) {
                $params = [
                    'user_sender_id' => $user_sender_id,
                    'user_receiver_id' => $receiverUserId,
                    'msg_status' => MESSAGE_STATUS_OUTBOX,
                    'send_date' => $now,
                    'title' => $subject,
                    'content' => $content,
                    'group_id' => $group_id,
                    'parent_id' => $parent_id,
                    'update_date' => $now,
                ];
                $outbox_last_id = Database::insert($table, $params);

                if ($extraParams) {
                    $extraParams['item_id'] = $outbox_last_id;
                    $extraFieldValues = new ExtraFieldValue('message');
                    $extraFieldValues->saveFieldValues($extraParams);
                }

                // save attachment file for outbox messages
                if (is_array($attachmentList)) {
                    foreach ($attachmentList as $attachment) {
                        if (0 == $attachment['error']) {
                            $comment = $attachment['comment'];
                            self::saveMessageAttachmentFile(
                                $attachment,
                                $comment,
                                $outbox_last_id,
                                $user_sender_id
                            );
                        }
                    }
                }
            }

            if ($sendEmail) {
                $notification = new Notification();
                $sender_info = api_get_user_info($user_sender_id);

                // add file attachment additional attributes
                $attachmentAddedByMail = [];
                foreach ($attachmentList as $attachment) {
                    $attachmentAddedByMail[] = [
                        'path' => $attachment['tmp_name'],
                        'filename' => $attachment['name'],
                    ];
                }

                if (empty($group_id)) {
                    $type = Notification::NOTIFICATION_TYPE_MESSAGE;
                    if ($directMessage) {
                        $type = Notification::NOTIFICATION_TYPE_DIRECT_MESSAGE;
                    }
                    $notification->saveNotification(
                        $messageId,
                        $type,
                        [$receiverUserId],
                        $subject,
                        $content,
                        $sender_info,
                        $attachmentAddedByMail,
                        $smsParameters,
                        $forceTitleWhenSendingEmail,
                        $checkUrls,
                        $courseId
                    );
                } else {
                    $usergroup = new UserGroup();
                    $group_info = $usergroup->get($group_id);
                    $group_info['topic_id'] = $topic_id;
                    $group_info['msg_id'] = $messageId;

                    $user_list = $usergroup->get_users_by_group(
                        $group_id,
                        false,
                        [],
                        0,
                        1000
                    );

                    // Adding more sense to the message group
                    $subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'), $group_info['name']);
                    $new_user_list = [];
                    foreach ($user_list as $user_data) {
                        $new_user_list[] = $user_data['id'];
                    }
                    $group_info = [
                        'group_info' => $group_info,
                        'user_info' => $sender_info,
                    ];
                    $notification->saveNotification(
                        $messageId,
                        Notification::NOTIFICATION_TYPE_GROUP,
                        $new_user_list,
                        $subject,
                        $content,
                        $group_info,
                        $attachmentAddedByMail,
                        $smsParameters,
                        false,
                        $checkUrls,
                        $courseId
                    );
                }
            }

            return $messageId;
        }

        return false;
    }

    /**
     * @param int    $receiverUserId
     * @param int    $subject
     * @param string $message
     * @param int    $sender_id
     * @param bool   $sendCopyToDrhUsers send copy to related DRH users
     * @param bool   $directMessage
     * @param array  $smsParameters
     * @param bool   $uploadFiles        Do not upload files using the MessageManager class
     * @param array  $attachmentList
     * @param bool   $checkUrls          It checks access url of user when multiple_access_urls = true
     *
     * @return bool
     */
    public static function send_message_simple(
        $receiverUserId,
        $subject,
        $message,
        $sender_id = 0,
        $sendCopyToDrhUsers = false,
        $directMessage = false,
        $smsParameters = [],
        $uploadFiles = true,
        $attachmentList = [],
        $checkUrls = false,
        $courseId = null
    ) {
        $files = $_FILES ? $_FILES : [];
        if (false === $uploadFiles) {
            $files = [];
        }
        // $attachmentList must have: tmp_name, name, size keys
        if (!empty($attachmentList)) {
            $files = $attachmentList;
        }
        $result = self::send_message(
            $receiverUserId,
            $subject,
            $message,
            $files,
            [],
            null,
            null,
            null,
            null,
            $sender_id,
            $directMessage,
            0,
            $smsParameters,
            false,
            false,
            0,
            [],
            $checkUrls,
            $courseId
        );

        if ($sendCopyToDrhUsers) {
            $userInfo = api_get_user_info($receiverUserId);
            $drhList = UserManager::getDrhListFromUser($receiverUserId);
            if (!empty($drhList)) {
                foreach ($drhList as $drhInfo) {
                    $message = sprintf(
                        get_lang('CopyOfMessageSentToXUser'),
                        $userInfo['complete_name']
                    ).' <br />'.$message;

                    self::send_message_simple(
                        $drhInfo['id'],
                        $subject,
                        $message,
                        $sender_id,
                        false,
                        $directMessage,
                        [],
                        true,
                        [],
                        $checkUrls,
                        $courseId
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Delete (or just flag) a message (and its attachment) from the table and disk.
     *
     * @param int The owner (receiver) of the message
     * @param int The internal ID of the message
     * @param bool Whether to really delete the message (true) or just mark it deleted (default/false)
     *
     * @throws Exception if file cannot be deleted in delete_message_attachment_file()
     *
     * @return bool False on error, true otherwise
     */
    public static function delete_message_by_user_receiver(int $user_receiver_id, int $id, bool $realDelete = false)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);

        if (empty($id) || empty($user_receiver_id)) {
            return false;
        }

        $sql = "SELECT * FROM $table
                WHERE
                    id = $id AND
                    user_receiver_id = $user_receiver_id AND
                    msg_status <> ".MESSAGE_STATUS_OUTBOX;
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            // Delete attachment file.
            self::delete_message_attachment_file($id, $user_receiver_id, null, $realDelete);
            if (false !== $realDelete) {
                // Hard delete message.
                $query = "DELETE FROM $table WHERE id = $id";
            } else {
                // Soft delete message.
                $query = "UPDATE $table
                      SET msg_status = ".MESSAGE_STATUS_DELETED."
                      WHERE
                        id = $id AND
                        user_receiver_id = $user_receiver_id ";
            }
            Database::query($query);

            return true;
        }

        return false;
    }

    /**
     * Set status deleted or delete the message completely.
     *
     * @author Isaac FLores Paz <isaac.flores@dokeos.com>
     * @author Yannick Warnier <yannick.warnier@beeznest.com> - Added realDelete option
     *
     * @param   int     The user's sender ID
     * @param   int     The message's ID
     * @param   bool    whether to really delete the message (true) or just mark it deleted (default/false)
     *
     * @throws Exception if file cannot be deleted in delete_message_attachment_file()
     *
     * @return bool
     */
    public static function delete_message_by_user_sender(int $user_sender_id, int $id, bool $realDelete = false)
    {
        if (empty($id) || empty($user_sender_id)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);

        $sql = "SELECT * FROM $table WHERE id = $id AND user_sender_id= $user_sender_id";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            // delete attachment file
            self::delete_message_attachment_file($id, $user_sender_id, null, $realDelete);
            if (false !== $realDelete) {
                // hard delete message
                $sql = "DELETE FROM $table WHERE id = $id";
            } else {
                // soft delete message
                $sql = "UPDATE $table
                    SET msg_status = '".MESSAGE_STATUS_DELETED."'
                    WHERE user_sender_id = $user_sender_id AND id = $id";
            }
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Saves a message attachment files.
     *
     * @param array $file_attach $_FILES['name']
     * @param  string    a comment about the uploaded file
     * @param  int        message id
     * @param  int        receiver user id (optional)
     * @param  int        sender user id (optional)
     * @param  int        group id (optional)
     */
    public static function saveMessageAttachmentFile(
        $file_attach,
        $file_comment,
        $message_id,
        $receiver_user_id = 0,
        $sender_user_id = 0,
        $group_id = 0
    ) {
        $table = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        // Try to add an extension to the file if it hasn't one
        $type = isset($file_attach['type']) ? $file_attach['type'] : '';
        if (empty($type)) {
            $type = DocumentManager::file_get_mime_type($file_attach['name']);
        }
        $new_file_name = add_ext_on_mime(stripslashes($file_attach['name']), $type);

        // user's file name
        $file_name = $file_attach['name'];
        if (!filter_extension($new_file_name)) {
            Display::addFlash(Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error'));
        } else {
            $new_file_name = uniqid('');
            if (!empty($receiver_user_id)) {
                $message_user_id = $receiver_user_id;
            } else {
                $message_user_id = $sender_user_id;
            }

            // User-reserved directory where photos have to be placed.*
            $userGroup = new UserGroup();
            if (!empty($group_id)) {
                $path_user_info = $userGroup->get_group_picture_path_by_id(
                    $group_id,
                    'system',
                    true
                );
            } else {
                $path_user_info['dir'] = UserManager::getUserPathById($message_user_id, 'system');
            }

            $path_message_attach = $path_user_info['dir'].'message_attachments/';
            // If this directory does not exist - we create it.
            if (!file_exists($path_message_attach)) {
                @mkdir($path_message_attach, api_get_permissions_for_new_directories(), true);
            }

            $new_path = $path_message_attach.$new_file_name;
            $fileCopied = false;
            if (isset($file_attach['tmp_name']) && !empty($file_attach['tmp_name'])) {
                if (is_uploaded_file($file_attach['tmp_name'])) {
                    @copy($file_attach['tmp_name'], $new_path);
                    $fileCopied = true;
                } else {
                    // 'tmp_name' can be set by the ticket or when forwarding a message
                    if (file_exists($file_attach['tmp_name'])) {
                        @copy($file_attach['tmp_name'], $new_path);
                        $fileCopied = true;
                    }
                }
            }

            if ($fileCopied) {
                // Storing the attachments if any
                $params = [
                    'filename' => $file_name,
                    'comment' => $file_comment,
                    'path' => $new_file_name,
                    'message_id' => $message_id,
                    'size' => $file_attach['size'],
                ];

                return Database::insert($table, $params);
            }
        }

        return false;
    }

    /**
     * Delete message attachment files (logically updating the row with a suffix _DELETE_id).
     *
     * @param  int    message id
     * @param  int    message user id (receiver user id or sender user id)
     * @param  int|null   group id (optional)
     * @param  bool   whether to really delete the file (true) or just mark it deleted (default/false)
     *
     * @throws Exception if file cannot be deleted
     */
    public static function delete_message_attachment_file(
        int $message_id,
        int $message_uid,
        ?int $group_id = 0,
        bool $realDelete = false
    ): void {
        $table_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $sql = "SELECT * FROM $table_message_attach
                WHERE message_id = $message_id";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $path = $row['path'];
            $attach_id = $row['id'];
            $new_path = $path.'_DELETED_'.$attach_id;

            if (!empty($group_id)) {
                $userGroup = new UserGroup();
                $path_user_info = $userGroup->get_group_picture_path_by_id(
                    $group_id,
                    'system',
                    true
                );
            } else {
                $path_user_info['dir'] = UserManager::getUserPathById(
                    $message_uid,
                    'system'
                );
            }

            $path_message_attach = $path_user_info['dir'].'message_attachments/';
            if (is_file($path_message_attach.$path)) {
                if ($realDelete) {
                    $unlink = unlink($path_message_attach.$path);
                    if (!$unlink) {
                        throw new Exception('Could not delete file '.$path_message_attach.$path);
                    }
                    $sql = "DELETE FROM $table_message_attach
                            WHERE id = $attach_id ";
                } else {
                    if (rename($path_message_attach.$path, $path_message_attach.$new_path)) {
                        $sql = "UPDATE $table_message_attach
                            SET path = '$new_path'
                            WHERE id = $attach_id ";
                    }
                }
                Database::query($sql);
            }
        }
    }

    /**
     * @param int $user_id
     * @param int $message_id
     * @param int $type
     *
     * @return bool
     */
    public static function update_message_status($user_id, $message_id, $type)
    {
        $user_id = (int) $user_id;
        $message_id = (int) $message_id;
        $type = (int) $type;

        if (empty($user_id) || empty($message_id)) {
            return false;
        }

        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = "UPDATE $table_message SET
                    msg_status = '$type'
                WHERE
                    user_receiver_id = ".$user_id." AND
                    id = '".$message_id."'";
        $result = Database::query($sql);

        return Database::affected_rows($result) > 0;
    }

    /**
     * get messages by group id.
     *
     * @param int $group_id group id
     *
     * @return array
     */
    public static function get_messages_by_group($group_id)
    {
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT * FROM $table
                WHERE
                    group_id= $group_id AND
                    msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')
                ORDER BY id";
        $rs = Database::query($sql);
        $data = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * get messages by group id.
     *
     * @param int $group_id
     * @param int $message_id
     *
     * @return array
     */
    public static function get_messages_by_group_by_message($group_id, $message_id)
    {
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT * FROM $table
                WHERE
                    group_id = $group_id AND
                    msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')
                ORDER BY id ";

        $rs = Database::query($sql);
        $data = [];
        $parents = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                if ($message_id == $row['parent_id'] || in_array($row['parent_id'], $parents)) {
                    $parents[] = $row['id'];
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * Get messages by parent id optionally with limit.
     *
     * @param  int        parent id
     * @param  int        group id (optional)
     * @param  int        offset (optional)
     * @param  int        limit (optional)
     *
     * @return array
     */
    public static function getMessagesByParent($parentId, $groupId = 0, $offset = 0, $limit = 0)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $parentId = (int) $parentId;

        if (empty($parentId)) {
            return [];
        }

        $condition_group_id = '';
        if (!empty($groupId)) {
            $groupId = (int) $groupId;
            $condition_group_id = " AND group_id = '$groupId' ";
        }

        $condition_limit = '';
        if ($offset && $limit) {
            $offset = (int) $offset;
            $limit = (int) $limit;
            $offset = ($offset - 1) * $limit;
            $condition_limit = " LIMIT $offset,$limit ";
        }

        $sql = "SELECT * FROM $table
                WHERE
                    parent_id='$parentId' AND
                    msg_status NOT IN (".MESSAGE_STATUS_OUTBOX.", ".MESSAGE_STATUS_WALL_DELETE.")
                    $condition_group_id
                ORDER BY send_date DESC $condition_limit ";
        $rs = Database::query($sql);
        $data = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $data[$row['id']] = $row;
            }
        }

        return $data;
    }

    /**
     * Gets information about messages sent.
     *
     * @param int
     * @param int
     * @param string
     * @param string
     *
     * @return array
     */
    public static function get_message_data_sent(
        $from,
        $numberOfItems,
        $column,
        $direction,
        $extraParams = []
    ) {
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;
        if (!isset($direction)) {
            $column = 2;
            $direction = 'DESC';
        } else {
            $column = (int) $column;
            if (!in_array($direction, ['ASC', 'DESC'])) {
                $direction = 'ASC';
            }
        }

        if (!in_array($column, [0, 1, 2])) {
            $column = 2;
        }
        $table = Database::get_main_table(TABLE_MESSAGE);
        $request = api_is_xml_http_request();
        $keyword = isset($extraParams['keyword']) && !empty($extraParams['keyword']) ? $extraParams['keyword'] : '';
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (title like '%$keyword%' OR content LIKE '%$keyword%') ";
        }

        $sql = "SELECT
                    id as col0,
                    title as col1,
                    send_date as col2,
                    user_receiver_id,
                    msg_status,
                    user_sender_id
                FROM $table
                WHERE
                    user_sender_id = ".api_get_user_id()." AND
                    msg_status = ".MESSAGE_STATUS_OUTBOX."
                    $keywordCondition
                ORDER BY col$column $direction
                LIMIT $from, $numberOfItems";
        $result = Database::query($sql);

        $message_list = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $messageId = $row['col0'];
            $title = $row['col1'];
            $sendDate = $row['col2'];
            $senderId = $row['user_sender_id'];

            if ($request === true) {
                $message[0] = '<input type="checkbox" value='.$messageId.' name="out[]">';
            } else {
                $message[0] = $messageId;
            }

            $class = 'class = "read"';
            $title = Security::remove_XSS($title);
            $userInfo = api_get_user_info($senderId);
            if ($request === true) {
                $message[1] = '<a onclick="show_sent_message('.$messageId.')" href="javascript:void(0)">'.
                    $userInfo['complete_name_with_username'].'</a>';
                $message[2] = '<a onclick="show_sent_message('.$messageId.')" href="javascript:void(0)">'.str_replace(
                        "\\",
                        "",
                        $title
                    ).'</a>';
                //date stays the same
                $message[3] = api_convert_and_format_date($sendDate, DATE_TIME_FORMAT_LONG);
                $message[4] = '&nbsp;&nbsp;<a title="'.addslashes(
                        get_lang('DeleteMessage')
                    ).'" onclick="delete_one_message_outbox('.$messageId.')" href="javascript:void(0)"  >'.
                    Display::returnFontAwesomeIcon('trash', 2).'</a>';
            } else {
                $message[1] = '<a '.$class.' onclick="show_sent_message('.$messageId.')" href="../messages/view_message.php?id_send='.$messageId.'">'.$title.'</a><br />'.$userInfo['complete_name_with_username'];
                $message[2] = api_convert_and_format_date($sendDate, DATE_TIME_FORMAT_LONG);
                $message[3] = '<a title="'.addslashes(
                        get_lang('DeleteMessage')
                    ).'" href="outbox.php?action=deleteone&id='.$messageId.'"  onclick="javascript:if(!confirm('."'".addslashes(
                        api_htmlentities(get_lang('ConfirmDeleteMessage'))
                    )."'".')) return false;" >'.
                    Display::returnFontAwesomeIcon('trash', 2).'</a>';
            }

            $message_list[] = $message;
        }

        return $message_list;
    }

    /**
     * display message box in the inbox.
     *
     * @param int $messageId
     * @param int $type
     *
     * @todo replace numbers with letters in the $row array pff...
     *
     * @return string html with the message content
     */
    public static function showMessageBox($messageId, $type)
    {
        $messageId = (int) $messageId;

        if (empty($messageId) || empty($type)) {
            return '';
        }
        $currentUserId = api_get_user_id();

        $table = Database::get_main_table(TABLE_MESSAGE);

        if (empty($type)) {
            return '';
        }

        switch ($type) {
            case self::MESSAGE_TYPE_OUTBOX:
                $status = MESSAGE_STATUS_OUTBOX;
                $userCondition = " user_sender_id = $currentUserId AND ";
                break;
            case self::MESSAGE_TYPE_INBOX:
                $status = MESSAGE_STATUS_NEW;
                $userCondition = " user_receiver_id = $currentUserId AND ";

                $query = "UPDATE $table SET
                          msg_status = '".MESSAGE_STATUS_NEW."'
                          WHERE id = $messageId ";
                Database::query($query);
                break;
            case self::MESSAGE_TYPE_PROMOTED:
                $status = MESSAGE_STATUS_PROMOTED;
                $userCondition = " user_receiver_id = $currentUserId AND ";
                break;
        }

        if (empty($userCondition)) {
            return '';
        }

        $query = "SELECT * FROM $table
                  WHERE
                    id = $messageId AND
                    $userCondition
                    msg_status = $status";
        $result = Database::query($query);
        $row = Database::fetch_array($result, 'ASSOC');

        if (empty($row)) {
            return '';
        }

        /* get previous message */
        $query = "SELECT id FROM $table
                  WHERE
                  $userCondition
                       id < $messageId
                     order by id DESC limit 1 ";
        $result = Database::query($query);
        $rowPrevMessage = Database::fetch_array($result, 'ASSOC');
        $idPrevMessage = (int) isset($rowPrevMessage['id']) ? $rowPrevMessage['id'] : 0;

        /* get next message */
        $query = "SELECT id FROM $table
                  WHERE
                  $userCondition
                       id > $messageId
                     order by id ASC limit 1 ";
        $result = Database::query($query);
        $rowNextMessage = Database::fetch_array($result, 'ASSOC');
        $idNextMessage = (int) isset($rowNextMessage['id']) ? $rowNextMessage['id'] : 0;

        $user_sender_id = $row['user_sender_id'];

        // get file attachments by message id
        $files_attachments = self::getAttachmentLinkList($messageId, $type);

        $row['content'] = str_replace('</br>', '<br />', $row['content']);
        $title = Security::remove_XSS($row['title'], STUDENT, true);
        $content = Security::remove_XSS($row['content'], STUDENT, true);

        $name = get_lang('UnknownUser');
        $fromUser = api_get_user_info($user_sender_id);
        $userImage = '';
        if (!empty($user_sender_id) && !empty($fromUser)) {
            $name = $fromUser['complete_name_with_username'];
            $userImage = Display::img(
                $fromUser['avatar_small'],
                $name,
                ['title' => $name, 'class' => 'img-responsive img-circle', 'style' => 'max-width:35px'],
                false
            );
        }

        $message_content = Display::page_subheader(str_replace("\\", '', $title));

        $receiverUserInfo = [];
        if (!empty($row['user_receiver_id'])) {
            $receiverUserInfo = api_get_user_info($row['user_receiver_id']);
        }

        if ('true' === api_get_setting('allow_social_tool')) {
            $message_content .= '<ul class="list-message">';

            if (!empty($user_sender_id)) {
                $message_content .= '<li>'.$userImage.'</li>';
                $message_content .= '<li>';
                $message_content .= Display::url(
                    $name,
                    api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_sender_id
                );
            } else {
                $message_content .= '<li>'.$name;
            }

            switch ($type) {
                case self::MESSAGE_TYPE_INBOX:
                    $message_content .= '&nbsp;'.api_strtolower(get_lang('To')).'&nbsp;'.get_lang('Me');
                    break;
                case self::MESSAGE_TYPE_OUTBOX:
                    if (!empty($receiverUserInfo)) {
                        $message_content .= '&nbsp;'.api_strtolower(
                                get_lang('To')
                            ).'&nbsp;<b>'.$receiverUserInfo['complete_name_with_username'].'</b></li>';
                    }
                    break;
                case self::MESSAGE_TYPE_PROMOTED:
                    break;
            }

            $message_content .= '&nbsp;<li>'.Display::dateToStringAgoAndLongDate($row['send_date']).'</li>';
            $message_content .= '</ul>';
        } else {
            switch ($type) {
                case self::MESSAGE_TYPE_INBOX:
                    $message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.
                        get_lang('Me').'</b>';
                    break;
                case self::MESSAGE_TYPE_OUTBOX:
                    $message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.
                        $receiverUserInfo['complete_name_with_username'].'</b>';
                    break;
            }
        }

        $message_content .= '
		        <hr>
		        <table width="100%">
		            <tr>
		              <td valign=top class="view-message-content">'.str_replace("\\", "", $content).'</td>
		            </tr>
		        </table>
		        <div id="message-attach">'.(!empty($files_attachments) ? implode('<br />', $files_attachments) : '').'</div>
		        <hr>';

        if (api_get_configuration_value('enable_message_tags')) {
            $message_content .= self::addTagsForm($messageId, $type);
        }

        $message_content .= '<div style="padding: 15px 0 5px 0;">';

        $social_link = '';
        if (isset($_GET['f']) && $_GET['f'] == 'social') {
            $social_link = 'f=social';
        }

        $eventLink = Display::url(
            Display::return_icon('new_event.png', get_lang('New event')),
            api_get_path(WEB_CODE_PATH).'calendar/agenda.php?action=add&type=personal&m='.$messageId
        ).PHP_EOL;

        switch ($type) {
            case self::MESSAGE_TYPE_OUTBOX:
                $message_content .= '<a href="outbox.php?'.$social_link.'">'.
                    Display::return_icon('back.png', get_lang('ReturnToOutbox')).'</a> &nbsp';
                $message_content .= $eventLink;
                $message_content .= '<a href="outbox.php?action=deleteone&id='.$messageId.'&'.$social_link.'" >'.
                    Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>&nbsp';
                break;
            case self::MESSAGE_TYPE_INBOX:
                $message_content .= '<a href="inbox.php?'.$social_link.'">'.
                    Display::return_icon('icons/22/arrow_up.png', get_lang('ReturnToInbox')).'</a>&nbsp;';
                $message_content .= '<a href="new_message.php?re_id='.$messageId.'&'.$social_link.'">'.
                    Display::return_icon('message_reply.png', get_lang('ReplyToMessage')).'</a>&nbsp;';
                $message_content .= $eventLink;
                $message_content .= '<a href="inbox.php?action=deleteone&id='.$messageId.'&'.$social_link.'" >'.
                    Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>&nbsp;';
                if ($idPrevMessage != 0) {
                    $message_content .= '<a title="'.get_lang('PrevMessage').'" href="view_message.php?type='.$type.'&id='.$idPrevMessage.'" ">'.Display::return_icon('icons/22/back.png', get_lang('ScormPrevious')).'</a> &nbsp';
                }
                if ($idNextMessage != 0) {
                    $message_content .= '<a title="'.get_lang('NextMessage').'" href="view_message.php?type='.$type.'&id='.$idNextMessage.'">'.Display::return_icon('icons/22/move.png', get_lang('ScormNext')).'</a> &nbsp';
                }
                break;
        }

        $message_content .= '</div>';

        return $message_content;
    }

    /**
     * get user id by user email.
     *
     * @param string $user_email
     *
     * @return int user id
     */
    public static function get_user_id_by_email($user_email)
    {
        $table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT user_id
                FROM '.$table.'
                WHERE email="'.Database::escape_string($user_email).'";';
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        if (isset($row['user_id'])) {
            return $row['user_id'];
        }

        return null;
    }

    /**
     * Displays messages of a group with nested view.
     *
     * @param int $groupId
     *
     * @return string
     */
    public static function display_messages_for_group($groupId)
    {
        global $my_group_role;

        $rows = self::get_messages_by_group($groupId);
        $topics_per_page = 10;
        $html_messages = '';
        $query_vars = ['id' => $groupId, 'topics_page_nr' => 0];

        if (is_array($rows) && count($rows) > 0) {
            // prepare array for topics with its items
            $topics = [];
            $x = 0;
            foreach ($rows as $index => $value) {
                if (empty($value['parent_id'])) {
                    $topics[$value['id']] = $value;
                }
            }

            $new_topics = [];

            foreach ($topics as $id => $value) {
                $rows = null;
                $rows = self::get_messages_by_group_by_message($groupId, $value['id']);
                if (!empty($rows)) {
                    $count = count(self::calculate_children($rows, $value['id']));
                } else {
                    $count = 0;
                }
                $value['count'] = $count;
                $new_topics[$id] = $value;
            }

            $array_html = [];
            foreach ($new_topics as $index => $topic) {
                $html = '';
                // topics
                $user_sender_info = api_get_user_info($topic['user_sender_id']);
                $name = $user_sender_info['complete_name'];
                $html .= '<div class="groups-messages">';
                $html .= '<div class="row">';

                $items = $topic['count'];
                $reply_label = ($items == 1) ? get_lang('GroupReply') : get_lang('GroupReplies');
                $label = '<i class="fa fa-envelope"></i> '.$items.' '.$reply_label;
                $topic['title'] = trim($topic['title']);

                if (empty($topic['title'])) {
                    $topic['title'] = get_lang('Untitled');
                }

                $html .= '<div class="col-xs-8 col-md-10">';
                $html .= Display::tag(
                    'h4',
                    Display::url(
                        Security::remove_XSS($topic['title'], STUDENT, true),
                        api_get_path(WEB_CODE_PATH).'social/group_topics.php?id='.$groupId.'&topic_id='.$topic['id']
                    ),
                    ['class' => 'title']
                );
                $actions = '';
                if ($my_group_role == GROUP_USER_PERMISSION_ADMIN ||
                    $my_group_role == GROUP_USER_PERMISSION_MODERATOR
                ) {
                    $actions = '<br />'.Display::url(
                            get_lang('Delete'),
                            api_get_path(
                                WEB_CODE_PATH
                            ).'social/group_topics.php?action=delete&id='.$groupId.'&topic_id='.$topic['id'],
                            ['class' => 'btn btn-default']
                        );
                }

                $date = '';
                if ($topic['send_date'] != $topic['update_date']) {
                    if (!empty($topic['update_date'])) {
                        $date .= '<i class="fa fa-calendar"></i> '.get_lang(
                                'LastUpdate'
                            ).' '.Display::dateToStringAgoAndLongDate($topic['update_date']);
                    }
                } else {
                    $date .= '<i class="fa fa-calendar"></i> '.get_lang(
                            'Created'
                        ).' '.Display::dateToStringAgoAndLongDate($topic['send_date']);
                }
                $html .= '<div class="date">'.$label.' - '.$date.$actions.'</div>';
                $html .= '</div>';

                $image = $user_sender_info['avatar'];

                $user_info = '<div class="author"><img class="img-responsive img-circle" src="'.$image.'" alt="'.$name.'"  width="64" height="64" title="'.$name.'" /></div>';
                $user_info .= '<div class="name"><a href="'.api_get_path(
                        WEB_PATH
                    ).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp;</a></div>';

                $html .= '<div class="col-xs-4 col-md-2">';
                $html .= $user_info;
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';

                $array_html[] = [$html];
            }

            // grids for items and topics  with paginations
            $html_messages .= Display::return_sortable_grid(
                'topics',
                [],
                $array_html,
                [
                    'hide_navigation' => false,
                    'per_page' => $topics_per_page,
                ],
                $query_vars,
                false,
                [true, true, true, false],
                false
            );
        }

        return $html_messages;
    }

    /**
     * Displays messages of a group with nested view.
     *
     * @param $groupId
     * @param $topic_id
     *
     * @return string
     */
    public static function display_message_for_group($groupId, $topic_id)
    {
        global $my_group_role;
        $main_message = self::get_message_by_id($topic_id);
        if (empty($main_message)) {
            return false;
        }

        $webCodePath = api_get_path(WEB_CODE_PATH);
        $iconCalendar = Display::returnFontAwesomeIcon('calendar');

        $langEdit = get_lang('Edit');
        $langReply = get_lang('Reply');
        $langLastUpdated = get_lang('LastUpdated');
        $langCreated = get_lang('Created');

        $rows = self::get_messages_by_group_by_message($groupId, $topic_id);
        $rows = self::calculate_children($rows, $topic_id);
        $current_user_id = api_get_user_id();

        $items_per_page = 50;
        $query_vars = ['id' => $groupId, 'topic_id' => $topic_id, 'topics_page_nr' => 0];

        // Main message
        $links = '';
        $main_content = '';
        $html = '';
        $items_page_nr = null;

        $user_sender_info = api_get_user_info($main_message['user_sender_id']);
        $files_attachments = self::getAttachmentLinkList($main_message['id'], 0);
        $name = $user_sender_info['complete_name'];

        $topic_page_nr = isset($_GET['topics_page_nr']) ? (int) $_GET['topics_page_nr'] : null;

        $links .= '<div class="pull-right">';
        $links .= '<div class="btn-group btn-group-sm">';

        if (($my_group_role == GROUP_USER_PERMISSION_ADMIN || $my_group_role == GROUP_USER_PERMISSION_MODERATOR) ||
            $main_message['user_sender_id'] == $current_user_id
        ) {
            $urlEdit = $webCodePath.'social/message_for_group_form.inc.php?'
                .http_build_query(
                    [
                        'user_friend' => $current_user_id,
                        'group_id' => $groupId,
                        'message_id' => $main_message['id'],
                        'action' => 'edit_message_group',
                        'anchor_topic' => 'topic_'.$main_message['id'],
                        'topics_page_nr' => $topic_page_nr,
                        'items_page_nr' => $items_page_nr,
                        'topic_id' => $main_message['id'],
                    ]
                );

            $links .= Display::toolbarButton(
                $langEdit,
                $urlEdit,
                'pencil',
                'default',
                ['class' => 'ajax', 'data-title' => $langEdit, 'data-size' => 'lg'],
                false
            );
        }

        $links .= self::getLikesButton($main_message['id'], $current_user_id, $groupId);

        $urlReply = $webCodePath.'social/message_for_group_form.inc.php?'
            .http_build_query(
                [
                    'user_friend' => $current_user_id,
                    'group_id' => $groupId,
                    'message_id' => $main_message['id'],
                    'action' => 'reply_message_group',
                    'anchor_topic' => 'topic_'.$main_message['id'],
                    'topics_page_nr' => $topic_page_nr,
                    'topic_id' => $main_message['id'],
                ]
            );

        $links .= Display::toolbarButton(
            $langReply,
            $urlReply,
            'commenting',
            'default',
            ['class' => 'ajax', 'data-title' => $langReply, 'data-size' => 'lg'],
            false
        );

        if (api_is_platform_admin()) {
            $links .= Display::toolbarButton(
                get_lang('Delete'),
                'group_topics.php?action=delete&id='.$groupId.'&topic_id='.$topic_id,
                'trash',
                'default',
                [],
                false
            );
        }

        $links .= '</div>';
        $links .= '</div>';

        $title = '<h4>'.Security::remove_XSS($main_message['title'], STUDENT, true).$links.'</h4>';

        $userPicture = $user_sender_info['avatar'];
        $main_content .= '<div class="row">';
        $main_content .= '<div class="col-md-2">';
        $main_content .= '<div class="avatar-author">';
        $main_content .= Display::img(
            $userPicture,
            $name,
            ['width' => '60px', 'class' => 'img-responsive img-circle'],
            false
        );
        $main_content .= '</div>';
        $main_content .= '</div>';

        $date = '';
        if ($main_message['send_date'] != $main_message['update_date']) {
            if (!empty($main_message['update_date'])) {
                $date = '<div class="date"> '
                    ."$iconCalendar $langLastUpdated "
                    .Display::dateToStringAgoAndLongDate($main_message['update_date'])
                    .'</div>';
            }
        } else {
            $date = '<div class="date"> '
                ."$iconCalendar $langCreated "
                .Display::dateToStringAgoAndLongDate($main_message['send_date'])
                .'</div>';
        }
        $attachment = '<div class="message-attach">'
            .(!empty($files_attachments) ? implode('<br />', $files_attachments) : '')
            .'</div>';
        $main_content .= '<div class="col-md-10">';
        $user_link = Display::url(
            $name,
            $webCodePath.'social/profile.php?u='.$main_message['user_sender_id']
        );
        $main_content .= '<div class="message-content"> ';
        $main_content .= '<div class="username">'.$user_link.'</div>';
        $main_content .= $date;
        $main_content .= '<div class="message">'.$main_message['content'].$attachment.'</div></div>';
        $main_content .= '</div>';
        $main_content .= '</div>';

        $html .= Display::div(
            Display::div(
                $title.$main_content,
                ['class' => 'message-topic']
            ),
            ['class' => 'sm-groups-message']
        );

        $topic_id = $main_message['id'];

        if (is_array($rows) && count($rows) > 0) {
            $topics = $rows;
            $array_html_items = [];

            foreach ($topics as $index => $topic) {
                if (empty($topic['id'])) {
                    continue;
                }
                $items_page_nr = isset($_GET['items_'.$topic['id'].'_page_nr'])
                    ? (int) $_GET['items_'.$topic['id'].'_page_nr']
                    : null;
                $links = '';
                $links .= '<div class="pull-right">';
                $html_items = '';
                $user_sender_info = api_get_user_info($topic['user_sender_id']);
                $files_attachments = self::getAttachmentLinkList($topic['id'], 0);
                $name = $user_sender_info['complete_name'];

                $links .= '<div class="btn-group btn-group-sm">';
                if (
                    ($my_group_role == GROUP_USER_PERMISSION_ADMIN ||
                        $my_group_role == GROUP_USER_PERMISSION_MODERATOR
                    ) ||
                    $topic['user_sender_id'] == $current_user_id
                ) {
                    $links .= Display::toolbarButton(
                        $langEdit,
                        $webCodePath.'social/message_for_group_form.inc.php?'
                            .http_build_query(
                                [
                                    'user_friend' => $current_user_id,
                                    'group_id' => $groupId,
                                    'message_id' => $topic['id'],
                                    'action' => 'edit_message_group',
                                    'anchor_topic' => 'topic_'.$topic_id,
                                    'topics_page_nr' => $topic_page_nr,
                                    'items_page_nr' => $items_page_nr,
                                    'topic_id' => $topic_id,
                                ]
                            ),
                        'pencil',
                        'default',
                        ['class' => 'ajax', 'data-title' => $langEdit, 'data-size' => 'lg'],
                        false
                    );
                }

                $links .= self::getLikesButton($topic['id'], $current_user_id, $groupId);

                $links .= Display::toolbarButton(
                    $langReply,
                    $webCodePath.'social/message_for_group_form.inc.php?'
                        .http_build_query(
                            [
                                'user_friend' => $current_user_id,
                                'group_id' => $groupId,
                                'message_id' => $topic['id'],
                                'action' => 'reply_message_group',
                                'anchor_topic' => 'topic_'.$topic_id,
                                'topics_page_nr' => $topic_page_nr,
                                'items_page_nr' => $items_page_nr,
                                'topic_id' => $topic_id,
                            ]
                        ),
                    'commenting',
                    'default',
                    ['class' => 'ajax', 'data-title' => $langReply, 'data-size' => 'lg'],
                    false
                );
                $links .= '</div>';
                $links .= '</div>';

                $userPicture = $user_sender_info['avatar'];
                $user_link = Display::url(
                    $name,
                    $webCodePath.'social/profile.php?u='.$topic['user_sender_id']
                );
                $html_items .= '<div class="row">';
                $html_items .= '<div class="col-md-2">';
                $html_items .= '<div class="avatar-author">';
                $html_items .= Display::img(
                    $userPicture,
                    $name,
                    ['width' => '60px', 'class' => 'img-responsive img-circle'],
                    false
                );
                $html_items .= '</div>';
                $html_items .= '</div>';

                $date = '';
                if ($topic['send_date'] != $topic['update_date']) {
                    if (!empty($topic['update_date'])) {
                        $date = '<div class="date"> '
                            ."$iconCalendar $langLastUpdated "
                            .Display::dateToStringAgoAndLongDate($topic['update_date'])
                            .'</div>';
                    }
                } else {
                    $date = '<div class="date"> '
                        ."$iconCalendar $langCreated "
                        .Display::dateToStringAgoAndLongDate($topic['send_date'])
                        .'</div>';
                }
                $attachment = '<div class="message-attach">'
                    .(!empty($files_attachments) ? implode('<br />', $files_attachments) : '')
                    .'</div>';
                $html_items .= '<div class="col-md-10">'
                    .'<div class="message-content">'
                    .$links
                    .'<div class="username">'.$user_link.'</div>'
                    .$date
                    .'<div class="message">'
                    .Security::remove_XSS($topic['content'], STUDENT, true)
                    .'</div>'.$attachment.'</div>'
                    .'</div>'
                    .'</div>';

                $base_padding = 20;

                if (0 == $topic['indent_cnt']) {
                    $indent = $base_padding;
                } else {
                    $indent = (int) $topic['indent_cnt'] * $base_padding + $base_padding;
                }

                $html_items = Display::div($html_items, ['class' => 'message-post', 'id' => 'msg_'.$topic['id']]);
                $html_items = Display::div($html_items, ['class' => '', 'style' => 'margin-left:'.$indent.'px']);
                $array_html_items[] = [$html_items];
            }

            // grids for items with paginations
            $options = ['hide_navigation' => false, 'per_page' => $items_per_page];
            $visibility = [true, true, true, false];

            $style_class = [
                'item' => ['class' => 'user-post'],
                'main' => ['class' => 'user-list'],
            ];
            if (!empty($array_html_items)) {
                $html .= Display::return_sortable_grid(
                    'items_'.$topic['id'],
                    [],
                    $array_html_items,
                    $options,
                    $query_vars,
                    null,
                    $visibility,
                    false,
                    $style_class
                );
            }
        }

        return $html;
    }

    /**
     * Add children to messages by id is used for nested view messages.
     *
     * @param array $rows rows of messages
     *
     * @return array $first_seed new list adding the item children
     */
    public static function calculate_children($rows, $first_seed)
    {
        $rows_with_children = [];
        foreach ($rows as $row) {
            $rows_with_children[$row["id"]] = $row;
            $rows_with_children[$row["parent_id"]]["children"][] = $row["id"];
        }
        $rows = $rows_with_children;
        $sorted_rows = [0 => []];
        self::message_recursive_sort($rows, $sorted_rows, $first_seed);
        unset($sorted_rows[0]);

        return $sorted_rows;
    }

    /**
     * Sort recursively the messages, is used for for nested view messages.
     *
     * @param array  original rows of messages
     * @param array  list recursive of messages
     * @param int   seed for calculate the indent
     * @param int   indent for nested view
     */
    public static function message_recursive_sort(
        $rows,
        &$messages,
        $seed = 0,
        $indent = 0
    ) {
        if ($seed > 0 && isset($rows[$seed]["id"])) {
            $messages[$rows[$seed]["id"]] = $rows[$seed];
            $messages[$rows[$seed]["id"]]["indent_cnt"] = $indent;
            $indent++;
        }

        if (isset($rows[$seed]["children"])) {
            foreach ($rows[$seed]["children"] as $child) {
                self::message_recursive_sort($rows, $messages, $child, $indent);
            }
        }
    }

    /**
     * @param int $messageId
     *
     * @return array
     */
    public static function getAttachmentList($messageId)
    {
        $table = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
        $messageId = (int) $messageId;

        if (empty($messageId)) {
            return [];
        }

        $messageInfo = self::get_message_by_id($messageId);

        if (empty($messageInfo)) {
            return [];
        }

        $attachmentDir = UserManager::getUserPathById($messageInfo['user_receiver_id'], 'system');
        $attachmentDir .= 'message_attachments/';

        $sql = "SELECT * FROM $table
                WHERE message_id = '$messageId'";
        $result = Database::query($sql);
        $files = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $row['file_source'] = '';
            if (file_exists($attachmentDir.$row['path'])) {
                $row['file_source'] = $attachmentDir.$row['path'];
            }
            $files[] = $row;
        }

        return $files;
    }

    /**
     * Get array of links (download) for message attachment files.
     *
     * @param int $messageId
     * @param int $type
     *
     * @return array
     */
    public static function getAttachmentLinkList($messageId, $type)
    {
        $files = self::getAttachmentList($messageId);
        // get file attachments by message id
        $list = [];
        if ($files) {
            $attachIcon = Display::return_icon('attachment.gif', '');
            $archiveURL = api_get_path(WEB_CODE_PATH).'messages/download.php?type='.$type.'&file=';
            foreach ($files as $row_file) {
                $archiveFile = $row_file['path'];
                $filename = $row_file['filename'];
                $size = format_file_size($row_file['size']);
                $comment = Security::remove_XSS($row_file['comment']);
                $filename = Security::remove_XSS($filename);
                $link = Display::url($filename, $archiveURL.$archiveFile);
                $comment = !empty($comment) ? '&nbsp;-&nbsp;<i>'.$comment.'</i>' : '';

                $attachmentLine = $attachIcon.'&nbsp;'.$link.'&nbsp;('.$size.')'.$comment;
                if ($row_file['comment'] === 'audio_message') {
                    $attachmentLine = '<audio src="'.$archiveURL.$archiveFile.'"/>';
                }
                $list[] = $attachmentLine;
            }
        }

        return $list;
    }

    /**
     * Get message list by id.
     *
     * @param int $messageId
     *
     * @return array
     */
    public static function get_message_by_id($messageId)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $messageId = (int) $messageId;
        $sql = "SELECT * FROM $table
                WHERE
                    id = '$messageId' AND
                    msg_status <> '".MESSAGE_STATUS_DELETED."' ";
        $res = Database::query($sql);
        $item = [];
        if (Database::num_rows($res) > 0) {
            $item = Database::fetch_array($res, 'ASSOC');
        }

        return $item;
    }

    /**
     * @return string
     */
    public static function generate_message_form()
    {
        $form = new FormValidator('send_message');
        $form->addText(
            'subject',
            get_lang('Subject'),
            false,
            ['id' => 'subject_id']
        );
        $form->addTextarea(
            'content',
            get_lang('Message'),
            ['id' => 'content_id', 'rows' => '5']
        );

        return $form->returnForm();
    }

    /**
     * @return string
     */
    public static function generate_invitation_form()
    {
        $form = new FormValidator('send_invitation');
        $form->addTextarea(
            'content',
            get_lang('AddPersonalMessage'),
            ['id' => 'content_invitation_id', 'rows' => 5]
        );

        return $form->returnForm();
    }

    /**
     * @param string $type
     * @param string $keyword
     * @param array  $actions
     *
     * @return string
     */
    public static function getMessageGrid($type, $keyword, $actions = [], array $searchTags = [])
    {
        $html = '';
        // display sortable table with messages of the current user
        $table = new SortableTable(
            'message_inbox',
            ['MessageManager', 'getNumberOfMessages'],
            ['MessageManager', 'getMessageData'],
            2,
            20,
            'DESC'
        );
        $table->setDataFunctionParams(
            ['keyword' => $keyword, 'type' => $type, 'actions' => $actions, 'tags' => $searchTags]
        );
        $table->set_header(0, '', false, ['style' => 'width:15px;']);
        $table->set_header(1, get_lang('Messages'), false);
        $table->set_header(2, get_lang('Date'), true, ['style' => 'width:180px;']);
        $table->set_header(3, get_lang('Modify'), false, ['style' => 'width:120px;']);

        if (isset($_REQUEST['f']) && $_REQUEST['f'] === 'social') {
            $parameters['f'] = 'social';
            $table->set_additional_parameters($parameters);
        }

        $defaultActions = [
            'delete' => get_lang('DeleteSelectedMessages'),
            'mark_as_unread' => get_lang('MailMarkSelectedAsUnread'),
            'mark_as_read' => get_lang('MailMarkSelectedAsRead'),
        ];

        if (!in_array('delete', $actions)) {
            unset($defaultActions['delete']);
        }
        if (!in_array('mark_as_unread', $actions)) {
            unset($defaultActions['mark_as_unread']);
        }
        if (!in_array('mark_as_read', $actions)) {
            unset($defaultActions['mark_as_read']);
        }

        $table->set_form_actions($defaultActions);

        $html .= $table->return_table();

        return $html;
    }

    /**
     * @param string $keyword
     *
     * @return string
     */
    public static function inboxDisplay($keyword = '', array $searchTags = [])
    {
        $success = get_lang('SelectedMessagesDeleted');
        $success_read = get_lang('SelectedMessagesRead');
        $success_unread = get_lang('SelectedMessagesUnRead');
        $currentUserId = api_get_user_id();

        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'mark_as_unread':
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $messageId) {
                            self::update_message_status(
                                $currentUserId,
                                $messageId,
                                MESSAGE_STATUS_UNREAD
                            );
                        }
                    }
                    Display::addFlash(Display::return_message(
                        $success_unread,
                        'normal',
                        false
                    ));
                    break;
                case 'mark_as_read':
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $messageId) {
                            self::update_message_status(
                                $currentUserId,
                                $messageId,
                                MESSAGE_STATUS_NEW
                            );
                        }
                    }
                    Display::addFlash(Display::return_message(
                        $success_read,
                        'normal',
                        false
                    ));
                    break;
                case 'delete':
                    foreach ($_POST['id'] as $index => $messageId) {
                        self::delete_message_by_user_receiver($currentUserId, $messageId);
                    }
                    Display::addFlash(Display::return_message(
                        $success,
                        'normal',
                        false
                    ));
                    break;
                case 'deleteone':
                    $result = self::delete_message_by_user_receiver($currentUserId, $_GET['id']);
                    if ($result) {
                        Display::addFlash(
                            Display::return_message(
                                $success,
                                'confirmation',
                                false
                            )
                        );
                    }
                    break;
            }
            header('Location: '.api_get_self());
            exit;
        }

        $actions = ['reply', 'mark_as_unread', 'mark_as_read', 'forward', 'delete'];

        $html = self::getMessageGrid(self::MESSAGE_TYPE_INBOX, $keyword, $actions, $searchTags);

        if (!empty($html)) {
            $html .= self::addTagsFormToInbox();
        }

        return $html;
    }

    /**
     * @param string $keyword
     *
     * @return string
     */
    public static function getPromotedMessagesGrid($keyword)
    {
        $actions = ['delete'];
        $currentUserId = api_get_user_id();

        $success = get_lang('SelectedMessagesDeleted');
        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    foreach ($_POST['id'] as $index => $messageId) {
                        self::delete_message_by_user_receiver($currentUserId, $messageId);
                    }
                    Display::addFlash(Display::return_message(
                        $success,
                        'normal',
                        false
                    ));
                    break;
                case 'deleteone':
                    self::delete_message_by_user_receiver($currentUserId, $_GET['id']);
                    Display::addFlash(Display::return_message(
                        $success,
                        'confirmation',
                        false
                    ));
                    break;
            }

            header('Location: '.api_get_self());
            exit;
        }

        $html = self::getMessageGrid(self::MESSAGE_TYPE_PROMOTED, $keyword, $actions);

        return $html;
    }

    /**
     * @param string $keyword
     *
     * @return string
     */
    public static function outBoxDisplay($keyword, array $searchTags = [])
    {
        $actions = ['delete'];

        $success = get_lang('SelectedMessagesDeleted');
        $currentUserId = api_get_user_id();
        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    foreach ($_POST['id'] as $index => $messageId) {
                        self::delete_message_by_user_sender($currentUserId, $messageId);
                    }
                    Display::addFlash(Display::return_message(
                        $success,
                        'normal',
                        false
                    ));

                    break;
                case 'deleteone':
                    self::delete_message_by_user_sender($currentUserId, $_GET['id']);
                    Display::addFlash(Display::return_message(
                        $success,
                        'confirmation',
                        false
                    ));
                    break;
            }

            header('Location: '.api_get_self());
            exit;
        }

        $html = self::getMessageGrid(self::MESSAGE_TYPE_OUTBOX, $keyword, $actions, $searchTags);

        if (!empty($html)) {
            $html .= self::addTagsFormToInbox();
        }

        return $html;
    }

    /**
     * @param string $keyword
     *
     * @return string
     */
    public static function outbox_display($keyword = '')
    {
        Session::write('message_sent_search_keyword', $keyword);
        $success = get_lang('SelectedMessagesDeleted').'&nbsp</b><br />
                    <a href="outbox.php">'.get_lang('BackToOutbox').'</a>';

        $html = '';
        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    $count = count($_POST['id']);
                    if ($count != 0) {
                        foreach ($_POST['id'] as $index => $messageId) {
                            self::delete_message_by_user_receiver(
                                api_get_user_id(),
                                $messageId
                            );
                        }
                    }
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                    break;
                case 'deleteone':
                    self::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                    $html .= '<br/>';
                    break;
            }
        }

        // display sortable table with messages of the current user
        $table = new SortableTable(
            'message_outbox',
            ['MessageManager', 'getNumberOfMessages'],
            ['MessageManager', 'getMessageData'],
            2,
            20,
            'DESC'
        );
        $table->setDataFunctionParams(
            ['keyword' => $keyword, 'type' => self::MESSAGE_TYPE_OUTBOX]
        );

        $table->set_header(0, '', false, ['style' => 'width:15px;']);
        $table->set_header(1, get_lang('Messages'), false);
        $table->set_header(2, get_lang('Date'), true, ['style' => 'width:180px;']);
        $table->set_header(3, get_lang('Modify'), false, ['style' => 'width:70px;']);

        $table->set_form_actions(['delete' => get_lang('DeleteSelectedMessages')]);
        $html .= $table->return_table();

        return $html;
    }

    /**
     * Get the data of the last received messages for a user.
     *
     * @param int $userId The user id
     * @param int $lastId The id of the last received message
     *
     * @return array
     */
    public static function getMessagesFromLastReceivedMessage($userId, $lastId = 0)
    {
        $userId = (int) $userId;
        $lastId = (int) $lastId;

        if (empty($userId)) {
            return [];
        }

        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT m.*, u.user_id, u.lastname, u.firstname
                FROM $messagesTable as m
                INNER JOIN $userTable as u
                ON m.user_sender_id = u.user_id
                WHERE
                    m.user_receiver_id = $userId AND
                    m.msg_status = ".MESSAGE_STATUS_UNREAD."
                    AND m.id > $lastId
                ORDER BY m.send_date DESC";

        $result = Database::query($sql);

        $messages = [];
        if ($result !== false) {
            while ($row = Database::fetch_assoc($result)) {
                $messages[] = $row;
            }
        }

        return $messages;
    }

    /**
     * Get the data of the last received messages for a user.
     *
     * @param int $userId The user id
     * @param int $lastId The id of the last received message
     *
     * @return array
     */
    public static function getReceivedMessages($userId, $lastId = 0)
    {
        $userId = intval($userId);
        $lastId = intval($lastId);

        if (empty($userId)) {
            return [];
        }
        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT m.*, u.user_id, u.lastname, u.firstname, u.picture_uri
                FROM $messagesTable as m
                INNER JOIN $userTable as u
                ON m.user_sender_id = u.user_id
                WHERE
                    m.user_receiver_id = $userId AND
                    m.msg_status IN (".MESSAGE_STATUS_NEW.", ".MESSAGE_STATUS_UNREAD.")
                    AND m.id > $lastId
                ORDER BY m.send_date DESC";

        $result = Database::query($sql);

        $messages = [];
        if ($result !== false) {
            while ($row = Database::fetch_assoc($result)) {
                $pictureInfo = UserManager::get_user_picture_path_by_id($row['user_id'], 'web');
                $row['pictureUri'] = $pictureInfo['dir'].$pictureInfo['file'];
                $messages[] = $row;
            }
        }

        return $messages;
    }

    /**
     * Get the data of the last received messages for a user.
     *
     * @param int $userId The user id
     * @param int $lastId The id of the last received message
     *
     * @return array
     */
    public static function getSentMessages($userId, $lastId = 0)
    {
        $userId = intval($userId);
        $lastId = intval($lastId);

        if (empty($userId)) {
            return [];
        }

        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT m.*, u.user_id, u.lastname, u.firstname, u.picture_uri
                FROM $messagesTable as m
                INNER JOIN $userTable as u
                ON m.user_receiver_id = u.user_id
                WHERE
                    m.user_sender_id = $userId
                    AND m.msg_status = ".MESSAGE_STATUS_OUTBOX."
                    AND m.id > $lastId
                ORDER BY m.send_date DESC";

        $result = Database::query($sql);

        $messages = [];
        if ($result !== false) {
            while ($row = Database::fetch_assoc($result)) {
                $pictureInfo = UserManager::get_user_picture_path_by_id($row['user_id'], 'web');
                $row['pictureUri'] = $pictureInfo['dir'].$pictureInfo['file'];
                $messages[] = $row;
            }
        }

        return $messages;
    }

    /**
     * Check whether a message has attachments.
     *
     * @param int $messageId The message id
     *
     * @return bool Whether the message has attachments return true. Otherwise return false
     */
    public static function hasAttachments($messageId)
    {
        $messageId = (int) $messageId;

        if (empty($messageId)) {
            return false;
        }

        $messageAttachmentTable = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $conditions = [
            'where' => [
                'message_id = ?' => $messageId,
            ],
        ];

        $result = Database::select(
            'COUNT(1) AS qty',
            $messageAttachmentTable,
            $conditions,
            'first'
        );

        if (!empty($result)) {
            if ($result['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $messageId
     *
     * @return array|bool
     */
    public static function getAttachment($messageId)
    {
        $messageId = (int) $messageId;

        if (empty($messageId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $conditions = [
            'where' => [
                'id = ?' => $messageId,
            ],
        ];

        $result = Database::select(
            '*',
            $table,
            $conditions,
            'first'
        );

        if (!empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     * @param string $url
     *
     * @return FormValidator
     */
    public static function getSearchForm($url)
    {
        $form = new FormValidator(
            'search',
            'post',
            $url,
            null,
            [],
            FormValidator::LAYOUT_INLINE
        );

        self::addTagsFormToSearch($form);

        $form->addElement(
            'text',
            'keyword',
            false,
            [
                'aria-label' => get_lang('Search'),
            ]
        );
        $form->addButtonSearch(get_lang('Search'));

        return $form;
    }

    /**
     * Send a notification to all admins when a new user is registered.
     */
    public static function sendNotificationOfNewRegisteredUser(User $user)
    {
        $tplMailBody = new Template(
            null,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $tplMailBody->assign('user', $user);
        $tplMailBody->assign('is_western_name_order', api_is_western_name_order());
        $tplMailBody->assign(
            'manageUrl',
            api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user->getId()
        );

        $layoutContent = $tplMailBody->get_template('mail/new_user_mail_to_admin.tpl');

        $emailsubject = '['.get_lang('UserRegistered').'] '.$user->getUsername();
        $emailbody = $tplMailBody->fetch($layoutContent);

        $admins = UserManager::get_all_administrators();

        foreach ($admins as $admin_info) {
            self::send_message(
                $admin_info['user_id'],
                $emailsubject,
                $emailbody,
                [],
                [],
                null,
                null,
                null,
                null,
                $user->getId()
            );
        }
    }

    /**
     * Send a notification to all admins when a new user is registered
     * while the approval method is used for users registration.
     */
    public static function sendNotificationOfNewRegisteredUserApproval(User $user)
    {
        $tplMailBody = new Template(
            null,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $tplMailBody->assign('user', $user);
        $tplMailBody->assign('is_western_name_order', api_is_western_name_order());
        $userId = $user->getId();
        $url_edit = Display::url(
            api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$userId,
            api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$userId
        );
        $tplMailBody->assign(
            'manageUrl',
            $url_edit
        );
        // Get extra field values for this user and reformat the array
        $extraFieldValues = new ExtraFieldValue('user');
        $userExtraFields = $extraFieldValues->getAllValuesByItem($userId);
        $values = [];
        foreach ($userExtraFields as $field => $value) {
            $values[$value['variable']] = $value['value'];
        }
        $tplMailBody->assign(
            'extra',
            $values
        );
        $layoutContent = '';
        $emailbody = '';
        if (api_get_configuration_value('mail_template_system') == true) {
            $mailTemplateManager = new MailTemplateManager();
            $templateText = $mailTemplateManager->getTemplateByType('new_user_mail_to_admin_approval.tpl');
            if (empty($templateText)) {
            } else {
                // custom procedure to load a template as a string (doesn't use cache so may slow down)
                $template = $tplMailBody->twig->createTemplate($templateText);
                $emailbody = $template->render($tplMailBody->params);
            }
        }
        if (empty($emailbody)) {
            $layoutContent = $tplMailBody->get_template('mail/new_user_mail_to_admin_approval.tpl');
            $emailbody = $tplMailBody->fetch($layoutContent);
        }

        $emailsubject = '['.get_lang('ApprovalForNewAccount').'] '.$user->getUsername();

        if (api_get_configuration_value('send_inscription_notification_to_general_admin_only')) {
            $email = api_get_setting('emailAdministrator');
            $firstname = api_get_setting('administratorSurname');
            $lastname = api_get_setting('administratorName');
            api_mail_html("$firstname $lastname", $email, $emailsubject, $emailbody);
        } else {
            $admins = UserManager::get_all_administrators();
            foreach ($admins as $admin_info) {
                self::send_message(
                    $admin_info['user_id'],
                    $emailsubject,
                    $emailbody,
                    [],
                    [],
                    null,
                    null,
                    null,
                    null,
                    $userId
                );
            }
        }
    }

    /**
     * Get the error log from failed mailing
     * This assumes a complex setup where you have a cron script regularly copying the mail queue log
     * into app/cache/mail/mailq.
     * This can be done with a cron command like (check the location of your mail log file first):.
     *
     * @example 0,30 * * * * root cp /var/log/exim4/mainlog /var/www/chamilo/app/cache/mail/mailq
     *
     * @return array|bool
     */
    public static function failedSentMailErrors()
    {
        $base = api_get_path(SYS_ARCHIVE_PATH).'mail/';
        $mailq = $base.'mailq';

        if (!file_exists($mailq) || !is_readable($mailq)) {
            return false;
        }

        $file = fopen($mailq, 'r');
        $i = 1;
        while (!feof($file)) {
            $line = fgets($file);

            if (trim($line) == '') {
                continue;
            }

            // Get the mail code, something like 1WBumL-0002xg-FF
            if (preg_match('/(.*)\s((.*)-(.*)-(.*))\s<(.*)$/', $line, $codeMatches)) {
                $mail_queue[$i]['code'] = $codeMatches[2];
            }

            $fullMail = $base.$mail_queue[$i]['code'];
            $mailFile = fopen($fullMail, 'r');

            // Get the reason of mail fail
            $iX = 1;
            while (!feof($mailFile)) {
                $mailLine = fgets($mailFile);
                //if ($iX == 4 && preg_match('/(.*):\s(.*)$/', $mailLine, $matches)) {
                if ($iX == 2 &&
                    preg_match('/(.*)(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s(.*)/', $mailLine, $detailsMatches)
                ) {
                    $mail_queue[$i]['reason'] = $detailsMatches[3];
                }
                $iX++;
            }

            fclose($mailFile);

            // Get the time of mail fail
            if (preg_match('/^\s?(\d+)(\D+)\s+(.*)$/', $line, $timeMatches)) {
                $mail_queue[$i]['time'] = $timeMatches[1].$timeMatches[2];
            } elseif (preg_match('/^(\s+)((.*)@(.*))\s+(.*)$/', $line, $emailMatches)) {
                $mail_queue[$i]['mail'] = $emailMatches[2];
                $i++;
            }
        }

        fclose($file);

        return array_reverse($mail_queue);
    }

    /**
     * @param int      $userId
     * @param datetime $startDate
     * @param datetime $endDate
     *
     * @return array
     */
    public static function getUsersThatHadConversationWithUser($userId, $startDate = null, $endDate = null)
    {
        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userId = (int) $userId;

        $sql = "SELECT DISTINCT
                    user_sender_id
                FROM $messagesTable
                WHERE
                    user_receiver_id = ".$userId;

        if ($startDate != null) {
            $startDate = Database::escape_string($startDate);
            $sql .= " AND send_date >= '".$startDate."'";
        }

        if ($endDate != null) {
            $endDate = Database::escape_string($endDate);
            $sql .= " AND send_date <= '".$endDate."'";
        }

        $result = Database::query($sql);
        $users = Database::store_result($result);
        $userList = [];
        foreach ($users as $userData) {
            $userId = $userData['user_sender_id'];
            if (empty($userId)) {
                continue;
            }
            $userInfo = api_get_user_info($userId);
            if ($userInfo) {
                $userList[$userId] = $userInfo;
            }
        }

        return $userList;
    }

    /**
     * @param int      $userId
     * @param int      $otherUserId
     * @param datetime $startDate
     * @param datetime $endDate
     *
     * @return array
     */
    public static function getAllMessagesBetweenStudents($userId, $otherUserId, $startDate = null, $endDate = null)
    {
        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userId = (int) $userId;
        $otherUserId = (int) $otherUserId;

        if (empty($otherUserId) || empty($userId)) {
            return [];
        }

        $sql = "SELECT DISTINCT *
                FROM $messagesTable
                WHERE
                    ((user_receiver_id = $userId AND user_sender_id = $otherUserId) OR
                    (user_receiver_id = $otherUserId AND user_sender_id = $userId))
            ";
        if ($startDate != null) {
            $startDate = Database::escape_string($startDate);
            $sql .= " AND send_date >= '".$startDate."'";
        }
        if ($endDate != null) {
            $endDate = Database::escape_string($endDate);
            $sql .= " AND send_date <= '".$endDate."'";
        }
        $sql .= " ORDER BY send_date DESC";
        $result = Database::query($sql);
        $messages = Database::store_result($result);
        $list = [];
        foreach ($messages as $message) {
            $list[] = $message;
        }

        return $list;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array  $courseInfo
     * @param int    $sessionId
     *
     * @return bool
     */
    public static function sendMessageToAllUsersInCourse($subject, $message, $courseInfo, $sessionId = 0)
    {
        if (empty($courseInfo)) {
            return false;
        }

        $senderId = api_get_user_id();
        if (empty($senderId)) {
            return false;
        }
        if (empty($sessionId)) {
            // Course students and teachers
            $users = CourseManager::get_user_list_from_course_code($courseInfo['code']);
        } else {
            // Course-session students and course session coaches
            $users = CourseManager::get_user_list_from_course_code($courseInfo['code'], $sessionId);
        }

        if (empty($users)) {
            return false;
        }

        foreach ($users as $userInfo) {
            self::send_message_simple(
                $userInfo['user_id'],
                $subject,
                $message,
                $senderId,
                false,
                false,
                [],
                false
            );
        }
    }

    /**
     * Clean audio messages already added in the message tool.
     */
    public static function cleanAudioMessage()
    {
        $audioId = Session::read('current_audio_id');
        if (!empty($audioId)) {
            api_remove_uploaded_file_by_id('audio_message', api_get_user_id(), $audioId);
            Session::erase('current_audio_id');
        }
    }

    /**
     * @param int    $senderId
     * @param string $subject
     * @param string $message
     */
    public static function sendMessageToAllAdminUsers(
        $senderId,
        $subject,
        $message
    ) {
        $admins = UserManager::get_all_administrators();
        foreach ($admins as $admin) {
            self::send_message_simple($admin['user_id'], $subject, $message, $senderId);
        }
    }

    /**
     * @param int $messageId
     * @param int $userId
     *
     * @return array
     */
    public static function countLikesAndDislikes($messageId, $userId)
    {
        if (!api_get_configuration_value('social_enable_messages_feedback')) {
            return [];
        }

        $messageId = (int) $messageId;
        $userId = (int) $userId;

        $em = Database::getManager();
        $query = $em
            ->createQuery('
                SELECT SUM(l.liked) AS likes, SUM(l.disliked) AS dislikes FROM ChamiloCoreBundle:MessageFeedback l
                WHERE l.message = :message
            ')
            ->setParameters(['message' => $messageId]);

        try {
            $counts = $query->getSingleResult();
        } catch (Exception $e) {
            $counts = ['likes' => 0, 'dislikes' => 0];
        }

        $userLike = $em
            ->getRepository('ChamiloCoreBundle:MessageFeedback')
            ->findOneBy(['message' => $messageId, 'user' => $userId]);

        return [
            'likes' => (int) $counts['likes'],
            'dislikes' => (int) $counts['dislikes'],
            'user_liked' => $userLike ? $userLike->isLiked() : false,
            'user_disliked' => $userLike ? $userLike->isDisliked() : false,
        ];
    }

    /**
     * @param int $messageId
     * @param int $userId
     * @param int $groupId   Optional.
     *
     * @return string
     */
    public static function getLikesButton($messageId, $userId, $groupId = 0)
    {
        if (!api_get_configuration_value('social_enable_messages_feedback')) {
            return '';
        }

        $countLikes = self::countLikesAndDislikes($messageId, $userId);

        $class = $countLikes['user_liked'] ? 'btn-primary' : 'btn-default';

        $btnLike = Display::button(
            'like',
            Display::returnFontAwesomeIcon('thumbs-up', '', true)
                .PHP_EOL.'<span>'.$countLikes['likes'].'</span>',
            [
                'title' => get_lang('VoteLike'),
                'class' => 'btn  social-like '.$class,
                'data-status' => 'like',
                'data-message' => $messageId,
                'data-group' => $groupId,
            ]
        );

        $btnDislike = '';
        if (api_get_configuration_value('disable_dislike_option') === false) {
            $disabled = $countLikes['user_disliked'] ? 'btn-danger' : 'btn-default';

            $btnDislike = Display::button(
                'like',
                Display::returnFontAwesomeIcon('thumbs-down', '', true)
                .PHP_EOL.'<span>'.$countLikes['dislikes'].'</span>',
                [
                    'title' => get_lang('VoteDislike'),
                    'class' => 'btn social-like '.$disabled,
                    'data-status' => 'dislike',
                    'data-message' => $messageId,
                    'data-group' => $groupId,
                ]
            );
        }

        return $btnLike.PHP_EOL.$btnDislike;
    }

    /**
     * Execute the SQL necessary to know the number of messages in the database.
     *
     * @param int $userId The user for which we need the unread messages count
     *
     * @return int The number of unread messages in the database for the given user
     */
    public static function getCountNewMessagesFromDB($userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return 0;
        }

        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT COUNT(id) as count
                FROM $table
                WHERE
                    user_receiver_id = $userId AND
                    msg_status = ".MESSAGE_STATUS_UNREAD;
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        return (int) $row['count'];
    }

    public static function getMessagesCountForUser(int $userId): array
    {
        // Setting notifications
        $countUnreadMessage = 0;

        if (api_get_setting('allow_message_tool') === 'true') {
            // get count unread message and total invitations
            $countUnreadMessage = MessageManager::getCountNewMessagesFromDB($userId);
        }

        if (api_get_setting('allow_social_tool') === 'true') {
            $numberOfNewMessagesOfFriend = SocialManager::get_message_number_invitation_by_user_id(
                $userId
            );
            $usergroup = new UserGroup();
            $groupPendingInvitations = $usergroup->get_groups_by_user(
                $userId,
                GROUP_USER_PERMISSION_PENDING_INVITATION
            );

            if (!empty($groupPendingInvitations)) {
                $groupPendingInvitations = count($groupPendingInvitations);
            } else {
                $groupPendingInvitations = 0;
            }

            return [
                'ms_friends' => $numberOfNewMessagesOfFriend,
                'ms_groups' => $groupPendingInvitations,
                'ms_inbox' => $countUnreadMessage,
            ];
        }

        return [
            'ms_friends' => 0,
            'ms_groups' => 0,
            'ms_inbox' => $countUnreadMessage,
        ];
    }

    /**
     * @throws Exception
     */
    public static function setDefaultValuesInFormFromMessageInfo(array $messageInfo, FormValidator $form)
    {
        $currentUserId = api_get_user_id();
        $contentMatch = [];
        preg_match('/<body>(.*?)<\/body>/s', $messageInfo['content'], $contentMatch);

        $defaults = [
            'title' => $messageInfo['title'],
        ];

        if (empty($contentMatch[1])) {
            $defaults['content'] = strip_tags_blacklist(
                $messageInfo['content'],
                ['link', 'script', 'title', 'head', 'body']
            );
            $defaults['content'] = preg_replace('#(<link(.*?)>)#msi', '', $defaults['content']);
        } else {
            $defaults['content'] = $contentMatch[1];
        }

        if (api_get_configuration_value('agenda_collective_invitations')) {
            $defaults['invitees'] = [];

            if ($currentUserId != $messageInfo['user_sender_id']) {
                $senderInfo = api_get_user_info($messageInfo['user_sender_id']);
                $form->getElement('invitees')->addOption(
                    $senderInfo['complete_name_with_username'],
                    $senderInfo['id']
                );
                $defaults['invitees'][] = $senderInfo['id'];
            }

            $messageCopies = MessageManager::getCopiesFromMessageInfo($messageInfo);

            foreach ($messageCopies as $messageCopy) {
                if ($currentUserId == $messageCopy->getUserReceiverId()) {
                    continue;
                }

                $receiverInfo = api_get_user_info($messageCopy->getUserReceiverId());
                $form->getElement('invitees')->addOption(
                    $receiverInfo['complete_name_with_username'],
                    $receiverInfo['id']
                );

                $defaults['invitees'][] = $receiverInfo['id'];
            }
        }

        $form->setDefaults($defaults);
    }

    /**
     * @throws Exception
     *
     * @return array<Message>
     */
    public static function getCopiesFromMessageInfo(array $messageInfo): array
    {
        $em = Database::getManager();
        $messageRepo = $em->getRepository('ChamiloCoreBundle:Message');

        return $messageRepo->findBy(
            [
                'userSenderId' => $messageInfo['user_sender_id'],
                'msgStatus' => MESSAGE_STATUS_OUTBOX,
                'sendDate' => new DateTime($messageInfo['send_date'], new DateTimeZone('UTC')),
                'title' => $messageInfo['title'],
                'content' => $messageInfo['content'],
                'groupId' => $messageInfo['group_id'],
                'parentId' => $messageInfo['parent_id'],
            ]
        );
    }

    /**
     * Reports whether the given user is sender or receiver of the given message.
     *
     * @return bool
     */
    public static function isUserOwner(int $userId, int $messageId)
    {
        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT id FROM $table
          WHERE id = $messageId
            AND (user_receiver_id = $userId OR user_sender_id = $userId)";
        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
            return true;
        }

        return false;
    }

    private static function addTagsFormToInbox(): string
    {
        if (false === api_get_configuration_value('enable_message_tags')) {
            return '';
        }

        $form = new FormValidator('frm_inbox_tags', 'post');

        $extrafield = new ExtraField('message');
        $extraHtml = $extrafield->addElements($form, 0, [], true, false, ['tags']);

        $form->addButton('submit', get_lang('AddTags'), 'plus', 'primary');
        $form->protect();

        $html = $form->returnForm();
        $html .= '<script>$(function () { '.$extraHtml['jquery_ready_content'].' });</script>';

        return $html;
    }

    private static function addTagsForm(int $messageId, string $type): string
    {
        $url = api_get_self()."?id=$messageId&type=$type";
        $form = new FormValidator('frm_tags', 'post', $url);

        $extrafield = new ExtraField('message');
        $extraHtml = $extrafield->addElements($form, $messageId, [], true, false, ['tags']);

        $form->addButtonSave(get_lang('Save'));
        $form->protect();

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $values['item_id'] = $messageId;

            $extraFieldValues = new ExtraFieldValue('message');
            $extraFieldValues->saveFieldValues($values);

            Display::addFlash(
                Display::return_message(get_lang('ItemUpdated'), 'success')
            );

            header("Location: $url");
            exit;
        }

        $messageContent = $form->returnForm();
        $messageContent .= '<script>$(function () { '.$extraHtml['jquery_ready_content'].' });</script>';

        return $messageContent;
    }

    private static function addTagsFormToSearch(FormValidator $form)
    {
        if (false === api_get_configuration_value('enable_message_tags')) {
            return;
        }

        $userId = api_get_user_id();

        $em = Database::getManager();
        $tags = $em
            ->getRepository('ChamiloCoreBundle:ExtraFieldRelTag')
            ->getTagsByUserMessages($userId)
        ;

        $tagsOptions = [];

        foreach ($tags as $tag) {
            $tagsOptions[$tag->getId()] = $tag->getTag();
        }

        $form
            ->addSelect(
                'tags',
                get_lang('Tags'),
                $tagsOptions,
                ['class' => 'inbox-search-tags', 'title' => get_lang('FilterByTags')]
            )
            ->setMultiple(true)
        ;
    }
}
