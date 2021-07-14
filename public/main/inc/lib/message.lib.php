<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageFeedback;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MessageManager
{
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
            'msg_type' => Message::MESSAGE_TYPE_CONVERSATION,
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

    public static function getMessagesAboutUser(User $user): array
    {
        if (!empty($user)) {
            $table = Database::get_main_table(TABLE_MESSAGE);
            $sql = 'SELECT id FROM '.$table.'
                    WHERE
                      user_receiver_id = '.$user->getId().' AND
                      msg_type = '.Message::MESSAGE_TYPE_CONVERSATION.'
                    ';
            $result = Database::query($sql);
            $messages = [];
            $repo = Database::getManager()->getRepository(Message::class);
            while ($row = Database::fetch_array($result)) {
                $message = $repo->find($row['id']);
                $messages[] = $message;
            }

            return $messages;
        }

        return [];
    }

    public static function getMessagesAboutUserToString(User $user): string
    {
        $messages = self::getMessagesAboutUser($user);
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
                $sender = $message->getSender();
                $html .= Display::panelCollapse(
                    $localTime.' '.UserManager::formatUserFullName($sender).' '.$message->getTitle(),
                    $message->getContent().'<br />'.$date.'<br />'.get_lang(
                        'Author'
                    ).': '.$sender->getUsername(),
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
                    msg_type = ".Message::MESSAGE_TYPE_INBOX."
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
     * @param bool   $checkCurrentAudioId
     * @param bool   $forceTitleWhenSendingEmail force the use of $title as subject instead of "You have a new message"
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
        $checkCurrentAudioId = false,
        $forceTitleWhenSendingEmail = false,
    ) {
        $group_id = (int) $group_id;
        $receiverUserId = (int) $receiverUserId;
        $parent_id = (int) $parent_id;
        $editMessageId = (int) $editMessageId;
        $topic_id = (int) $topic_id;
        $user_sender_id = empty($sender_id) ? api_get_user_id() : (int) $sender_id;

        if (empty($user_sender_id) || empty($receiverUserId)) {
            return false;
        }

        $userSender = api_get_user_entity($user_sender_id);
        if (null === $userSender) {
            Display::addFlash(Display::return_message(get_lang('This user doesn\'t exist'), 'warning'));

            return false;
        }

        $userRecipient = api_get_user_entity($receiverUserId);

        if (null === $userRecipient) {
            return false;
        }

        // Disabling messages for inactive users.
        if (!$userRecipient->getActive()) {
            return false;
        }

        $sendEmail = true;
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
                        $receiverUserId,
                        'start_pause_date'
                    );
                    $endDate = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $receiverUserId,
                        'end_pause_date'
                    );

                    if (!empty($startDate) && isset($startDate['value']) && !empty($startDate['value']) &&
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

        $totalFileSize = 0;
        $attachmentList = [];
        if (is_array($attachments)) {
            $counter = 0;
            foreach ($attachments as $attachment) {
                $attachment['comment'] = $fileCommentList[$counter] ?? '';
                $fileSize = $attachment['size'] ?? 0;
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
            $audio = Session::read('current_audio');
            if (!empty($audio) && isset($audio['name']) && !empty($audio['name'])) {
                $audio['comment'] = 'audio_message';
                // create attachment from audio message
                $attachmentList[] = $audio;
            }
        }

        // Validating fields
        if (empty($subject) && empty($group_id)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('You should write a subject'),
                    'warning'
                )
            );

            return false;
        } elseif ($totalFileSize > (int) api_get_setting('message_max_upload_filesize')) {
            $warning = sprintf(
                get_lang('Files size exceeds'),
                format_file_size(api_get_setting('message_max_upload_filesize'))
            );

            Display::addFlash(Display::return_message($warning, 'warning'));

            return false;
        }

        $em = Database::getManager();
        $repo = $em->getRepository(Message::class);
        $parent = null;
        if (!empty($parent_id)) {
            $parent = $repo->find($parent_id);
        }

        // Just in case we replace the and \n and \n\r while saving in the DB
        if (!empty($receiverUserId) || !empty($group_id)) {
            // message for user friend
            //@todo it's possible to edit a message? yes, only for groups
            if (!empty($editMessageId)) {
                $message = $repo->find($editMessageId);
                if (null !== $message) {
                    $message->setContent($content);
                    $em->persist($message);
                    $em->flush();
                }
                $messageId = $editMessageId;
            } else {
                $message = (new Message())
                    ->setSender($userSender)
                    ->addReceiver($userRecipient)
                    ->setTitle($subject)
                    ->setContent($content)
                    ->setGroup(api_get_group_entity($group_id))
                    ->setParent($parent)
                ;
                $em->persist($message);
                $em->flush();
                $messageId = $message->getId();
            }

            // Forward also message attachments.
            if (!empty($forwardId)) {
                $forwardMessage = $repo->find($forwardId);
                if (null !== $forwardMessage) {
                    $forwardAttachments = $forwardMessage->getAttachments();
                    foreach ($forwardAttachments as $forwardAttachment) {
                        $message->addAttachment($forwardAttachment);
                    }
                    $em->persist($message);
                    $em->flush();
                }
            }

            // Save attachment file for inbox messages
            if (is_array($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    if (0 === $attachment['error']) {
                        self::saveMessageAttachmentFile(
                            $attachment,
                            $attachment['comment'] ?? '',
                            $message,
                        );
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
                        $forceTitleWhenSendingEmail
                    );
                } else {
                    $usergroup = new UserGroupModel();
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
                    $subject = sprintf(get_lang('There is a new message in group %s'), $group_info['name']);
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
                        $attachmentAddedByMail
                    );
                }
            }

            return $messageId;
        }

        return false;
    }

    /**
     * @param int    $receiverUserId
     * @param string $subject
     * @param string $message
     * @param int    $sender_id
     * @param bool   $sendCopyToDrhUsers send copy to related DRH users
     * @param bool   $directMessage
     * @param bool   $uploadFiles        Do not upload files using the MessageManager class
     * @param array  $attachmentList
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
        $uploadFiles = true,
        $attachmentList = []
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
            $directMessage
        );

        if ($sendCopyToDrhUsers) {
            $userInfo = api_get_user_info($receiverUserId);
            $drhList = UserManager::getDrhListFromUser($receiverUserId);
            if (!empty($drhList)) {
                foreach ($drhList as $drhInfo) {
                    $message = sprintf(
                        get_lang('Copy of message sent to %s'),
                        $userInfo['complete_name']
                    ).' <br />'.$message;

                    self::send_message_simple(
                        $drhInfo['id'],
                        $subject,
                        $message,
                        $sender_id,
                        false,
                        $directMessage
                    );
                }
            }
        }

        return $result;
    }

    public static function softDeleteAttachments(Message $message): void
    {
        $attachments = $message->getAttachments();
        if (!empty($attachments)) {
            $repo = Container::getMessageAttachmentRepository();
            foreach ($attachments as $file) {
                $repo->softDelete($file);
            }
        }
    }

    /**
     * Saves a message attachment files.
     *
     * @param array  $file    $_FILES['name']
     * @param string $comment a comment about the uploaded file
     */
    public static function saveMessageAttachmentFile($file, $comment, Message $message)
    {
        // Try to add an extension to the file if it hasn't one
        $type = $file['type'] ?? '';
        if (empty($type)) {
            $type = DocumentManager::file_get_mime_type($file['name']);
        }
        $new_file_name = add_ext_on_mime(stripslashes($file['name']), $type);

        // user's file name
        $fileName = $file['name'];
        if (!filter_extension($new_file_name)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('File upload failed: this file extension or file type is prohibited'),
                    'error'
                )
            );

            return false;
        }

        $em = Database::getManager();
        $attachmentRepo = Container::getMessageAttachmentRepository();

        $attachment = new MessageAttachment();
        $attachment
            ->setSize($file['size'])
            ->setPath($fileName)
            ->setFilename($fileName)
            ->setComment($comment)
            ->setParent($message->getSender())
            ->setMessage($message)
        ;

        $request = Container::getRequest();
        $fileToUpload = null;

        // Search for files inside the $_FILES, when uploading several files from the form.
        if ($request->files->count()) {
            /** @var UploadedFile|null $fileRequest */
            foreach ($request->files->all() as $fileRequest) {
                if (null === $fileRequest) {
                    continue;
                }
                if ($fileRequest->getClientOriginalName() === $file['name']) {
                    $fileToUpload = $fileRequest;
                    break;
                }
            }
        }

        // If no found file, try with $file['content'].
        if (null === $fileToUpload && isset($file['content'])) {
            $handle = tmpfile();
            fwrite($handle, $file['content']);
            $meta = stream_get_meta_data($handle);
            $fileToUpload = new UploadedFile($meta['uri'], $fileName, $file['type'], null, true);
        }

        if (null !== $fileToUpload) {
            $em->persist($attachment);
            $attachmentRepo->addFile($attachment, $fileToUpload);
            $attachment->addUserLink($message->getSender());
            $receivers = $message->getReceivers();
            foreach ($receivers as $receiver) {
                $attachment->addUserLink($receiver);
            }
            $em->flush();

            return true;
        }

        return false;
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
                    msg_type = ".Message::MESSAGE_TYPE_GROUP."
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
                    msg_type = '".Message::MESSAGE_TYPE_GROUP."'
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
                    msg_type = '".Message::MESSAGE_TYPE_GROUP."'
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
                $reply_label = (1 == $items) ? get_lang('Reply') : get_lang('Replies');
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
                if (GROUP_USER_PERMISSION_ADMIN == $my_group_role ||
                    GROUP_USER_PERMISSION_MODERATOR == $my_group_role
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
        return '';

        /*global $my_group_role;
        $message = Container::getMessageRepository()->find($topic_id);
        if (null === $message) {
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

        $filesAttachments = self::getAttachmentLinkList($message);
        $name = UserManager::formatUserFullName($message->getSender());

        $topic_page_nr = isset($_GET['topics_page_nr']) ? (int) $_GET['topics_page_nr'] : null;

        $links .= '<div class="pull-right">';
        $links .= '<div class="btn-group btn-group-sm">';

        if ((GROUP_USER_PERMISSION_ADMIN == $my_group_role || GROUP_USER_PERMISSION_MODERATOR == $my_group_role) ||
            $message->getSender()->getId() == $current_user_id
        ) {
            $urlEdit = $webCodePath.'social/message_for_group_form.inc.php?'
                .http_build_query(
                    [
                        'user_friend' => $current_user_id,
                        'group_id' => $groupId,
                        'message_id' => $message->getId(),
                        'action' => 'edit_message_group',
                        'anchor_topic' => 'topic_'.$message->getId(),
                        'topics_page_nr' => $topic_page_nr,
                        'items_page_nr' => $items_page_nr,
                        'topic_id' => $message->getId(),
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

        $links .= self::getLikesButton($message->getId(), $current_user_id, $groupId);

        $urlReply = $webCodePath.'social/message_for_group_form.inc.php?'
            .http_build_query(
                [
                    'user_friend' => $current_user_id,
                    'group_id' => $groupId,
                    'message_id' => $message->getId(),
                    'action' => 'reply_message_group',
                    'anchor_topic' => 'topic_'.$message->getId(),
                    'topics_page_nr' => $topic_page_nr,
                    'topic_id' => $message->getId(),
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

        $title = '<h4>'.Security::remove_XSS($message->getTitle(), STUDENT, true).$links.'</h4>';

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
            .(!empty($filesAttachments) ? implode('<br />', $filesAttachments) : '')
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
                $filesAttachments = self::getAttachmentLinkList($topic['id'], 0);
                $name = $user_sender_info['complete_name'];

                $links .= '<div class="btn-group btn-group-sm">';
                if (
                    (GROUP_USER_PERMISSION_ADMIN == $my_group_role ||
                        GROUP_USER_PERMISSION_MODERATOR == $my_group_role
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
                    .(!empty($filesAttachments) ? implode('<br />', $filesAttachments) : '')
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
        }*/

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
     * Get array of links (download) for message attachment files.
     *
     * @return array
     */
    public static function getAttachmentLinkList(Message $message)
    {
        $files = $message->getAttachments();
        // get file attachments by message id
        $list = [];
        if ($files) {
            $attachIcon = Display::returnFontAwesomeIcon('paperclip');
            $repo = Container::getMessageAttachmentRepository();
            foreach ($files as $file) {
                $size = format_file_size($file->getSize());
                $comment = Security::remove_XSS($file->getComment());
                $filename = Security::remove_XSS($file->getFilename());
                $url = $repo->getResourceFileUrl($file);
                $link = Display::url($filename, $url);
                $comment = !empty($comment) ? '&nbsp;-&nbsp;<i>'.$comment.'</i>' : '';

                $attachmentLine = $attachIcon.'&nbsp;'.$link.'&nbsp;('.$size.')'.$comment;
                /*if ('audio_message' === $file['comment']) {
                    $attachmentLine = '<audio src="'.$archiveURL.$archiveFile.'"/>';
                }*/
                $list[] = $attachmentLine;
            }
        }

        return $list;
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
            get_lang('Add a personal message'),
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
    public static function getMessageGrid($type, $keyword, $actions = [])
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
            ['keyword' => $keyword, 'type' => $type, 'actions' => $actions]
        );
        $table->set_header(0, '', false, ['style' => 'width:15px;']);
        $table->set_header(1, get_lang('Messages'), false);
        $table->set_header(2, get_lang('Date'), true, ['style' => 'width:180px;']);
        $table->set_header(3, get_lang('Edit'), false, ['style' => 'width:120px;']);

        if (isset($_REQUEST['f']) && 'social' === $_REQUEST['f']) {
            $parameters['f'] = 'social';
            $table->set_additional_parameters($parameters);
        }

        $defaultActions = [
            'delete' => get_lang('Delete selected messages'),
            'mark_as_unread' => get_lang('Mark as unread'),
            'mark_as_read' => get_lang('Mark as read'),
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

        $emailsubject = '['.get_lang('The user has been registered').'] '.$user->getUsername();
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
        if (true == api_get_configuration_value('mail_template_system')) {
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

            if ('' == trim($line)) {
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
                if (2 == $iX &&
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
     * @param int $userId
     *
     * @return array
     */
    public static function getUsersThatHadConversationWithUser($userId)
    {
        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userId = (int) $userId;

        $sql = "SELECT DISTINCT
                    user_sender_id
                FROM $messagesTable
                WHERE
                    user_receiver_id = ".$userId;
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
     * @param int $userId
     * @param int $otherUserId
     *
     * @return array
     */
    public static function getAllMessagesBetweenStudents($userId, $otherUserId)
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
                    (user_receiver_id = $userId AND user_sender_id = $otherUserId) OR
                    (user_receiver_id = $otherUserId AND user_sender_id = $userId)
                ORDER BY send_date DESC
            ";
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
     * @param Course $course
     * @param int    $sessionId
     *
     * @return bool
     */
    public static function sendMessageToAllUsersInCourse($subject, $message, Course $course, $sessionId = 0)
    {
        $senderId = api_get_user_id();
        if (empty($senderId)) {
            return false;
        }
        if (empty($sessionId)) {
            // Course students and teachers
            $users = CourseManager::get_user_list_from_course_code($course->getCode());
        } else {
            // Course-session students and course session coaches
            $users = CourseManager::get_user_list_from_course_code($course->getCode(), $sessionId);
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
                false
            );
        }
    }

    /**
     * Clean audio messages already added in the message tool.
     */
    public static function cleanAudioMessage()
    {
        Session::erase('current_audio');
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
            ->getRepository(MessageFeedback::class)
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
                'title' => get_lang('Like'),
                'class' => 'btn  social-like '.$class,
                'data-status' => 'like',
                'data-message' => $messageId,
                'data-group' => $groupId,
            ]
        );

        $btnDislike = '';
        if (false === api_get_configuration_value('disable_dislike_option')) {
            $disabled = $countLikes['user_disliked'] ? 'btn-danger' : 'btn-default';

            $btnDislike = Display::button(
                'like',
                Display::returnFontAwesomeIcon('thumbs-down', '', true)
                .PHP_EOL.'<span>'.$countLikes['dislikes'].'</span>',
                [
                    'title' => get_lang('Dislike'),
                    'class' => 'btn social-like '.$disabled,
                    'data-status' => 'dislike',
                    'data-message' => $messageId,
                    'data-group' => $groupId,
                ]
            );
        }

        return $btnLike.PHP_EOL.$btnDislike;
    }
}
