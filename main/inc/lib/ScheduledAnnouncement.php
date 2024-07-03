<?php

/* For licensing terms, see /license.txt */

/**
 * Class ScheduledAnnouncement
 * Requires DB change:.
 *
 * CREATE TABLE scheduled_announcements (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, date DATETIME DEFAULT NULL, sent TINYINT(1) NOT NULL, session_id INT NOT NULL, c_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
 *
 * Config setting:
 * $_configuration['allow_scheduled_announcements'] = true;
 *
 * Setup linux cron file:
 * main/cron/scheduled_announcement.php
 *
 * Requires:
 * composer update
 */
class ScheduledAnnouncement extends Model
{
    public $table;
    public $columns = ['id', 'subject', 'message', 'date', 'sent', 'session_id'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = 'scheduled_announcements';
    }

    /**
     * @param array $where_conditions
     *
     * @return array
     */
    public function get_all($where_conditions = [])
    {
        return Database::select(
            '*',
            $this->table,
            ['where' => $where_conditions, 'order' => 'subject ASC']
        );
    }

    /**
     * @return mixed
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [],
            'first'
        );

        return $row['count'];
    }

    /**
     * Displays the title + grid.
     *
     * @param int $sessionId
     *
     * @return string
     */
    public function getGrid($sessionId)
    {
        // action links
        $action = '<div class="actions" style="margin-bottom:20px">';
        $action .= Display::url(
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId
        );

        $action .= '<a href="'.api_get_self().'?action=add&session_id='.$sessionId.'">'.
            Display::return_icon('add.png', get_lang('Add'), '', ICON_SIZE_MEDIUM).'</a>';
        $action .= '<a href="scheduled_announcement.php?action=run&session_id='.$sessionId.'">'.
            Display::return_icon('tuning.png', get_lang('SendManuallyPendingAnnouncements'), '', ICON_SIZE_MEDIUM).
            '</a>';

        $action .= '</div>';

        $html = $action;
        $html .= '<div id="session-table" class="table-responsive">';
        $html .= Display::grid_html('programmed');
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns a Form validator Obj.
     *
     * @param int    $id
     * @param string $url
     * @param string $action      add, edit
     * @param array  $sessionInfo
     *
     * @return FormValidator form validator obj
     */
    public function returnSimpleForm($id, $url, $action, $sessionInfo = [])
    {
        $form = new FormValidator(
            'announcement',
            'post',
            $url
        );

        $form->addHidden('session_id', $sessionInfo['id']);
        $form->addDateTimePicker('date', get_lang('Date'));
        $form->addText('subject', get_lang('Subject'));
        $form->addHtmlEditor('message', get_lang('Message'));

        $extraField = new ExtraField('scheduled_announcement');
        $extra = $extraField->addElements($form, $id);
        $js = $extra['jquery_ready_content'];
        $form->addHtml("<script> $(function() { $js }); </script> ");

        $this->setTagsInForm($form);

        $form->addCheckBox('sent', null, get_lang('MessageSent'));

        if ('edit' === $action) {
            $form->addButtonUpdate(get_lang('Modify'));
        }

        return $form;
    }

    /**
     * Returns a Form validator Obj.
     *
     * @todo the form should be auto generated
     *
     * @param string $url
     * @param string $action add, edit
     * @param array
     *
     * @return FormValidator form validator obj
     */
    public function returnForm($url, $action, $sessionInfo = [])
    {
        // Setting the form elements
        $header = get_lang('Add');

        if ('edit' === $action) {
            $header = get_lang('Modify');
        }

        $form = new FormValidator(
            'announcement',
            'post',
            $url
        );

        $form->addHeader($header);
        if ('add' === $action) {
            $form->addHtml(
                Display::return_message(
                    nl2br(get_lang('ScheduleAnnouncementDescription')),
                    'normal',
                    false
                )
            );
        }
        $form->addHidden('session_id', $sessionInfo['id']);

        $useBaseDate = false;
        $startDate = $sessionInfo['access_start_date'];
        $endDate = $sessionInfo['access_end_date'];

        if (!empty($startDate) || !empty($endDate)) {
            $useBaseDate = true;
        }

        $typeOptions = [
            'specific_date' => get_lang('SpecificDate'),
        ];

        if ($useBaseDate) {
            $typeOptions['base_date'] = get_lang('BaseDate');
        }

        $form->addSelect(
            'type',
            get_lang('Type'),
            $typeOptions,
            [
                'onchange' => "javascript:
                    if (this.options[this.selectedIndex].value == 'base_date') {
                        document.getElementById('options').style.display = 'block';
                        document.getElementById('specific_date').style.display = 'none';
                    } else {
                        document.getElementById('options').style.display = 'none';
                        document.getElementById('specific_date').style.display = 'block';
                    }
            ", ]
        );

        $form->addHtml('<div id="specific_date">');
        $form->addDateTimePicker('date', get_lang('Date'));
        $form->addHtml('</div>');
        $form->addHtml('<div id="options" style="display:none">');

        $startDate = $sessionInfo['access_start_date'];
        $endDate = $sessionInfo['access_end_date'];

        $form->addText(
            'days',
            get_lang('Days'),
            false
        );

        $form->addSelect(
            'moment_type',
            get_lang('AfterOrBefore'),
            [
                'after' => get_lang('After'),
                'before' => get_lang('Before'),
            ]
        );

        if (!empty($startDate)) {
            $options['start_date'] = get_lang('StartDate').' - '.$startDate;
        }

        if (!empty($endDate)) {
            $options['end_date'] = get_lang('EndDate').' - '.$endDate;
        }
        if (!empty($options)) {
            $form->addSelect('base_date', get_lang('BaseDate'), $options);
        }

        $form->addHtml('</div>');
        $form->addText('subject', get_lang('Subject'));
        $form->addHtmlEditor('message', get_lang('Message'));

        $extraField = new ExtraField('scheduled_announcement');
        $extra = $extraField->addElements($form);
        $js = $extra['jquery_ready_content'];
        $form->addHtml("<script> $(function() { $js }); </script> ");

        $this->setTagsInForm($form);

        if ('edit' === $action) {
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        return $form;
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function getAttachmentToString($id)
    {
        $file = $this->getAttachment($id);
        if (!empty($file) && !empty($file['value'])) {
            $url = api_get_path(WEB_UPLOAD_PATH).$file['value'];

            return get_lang('Attachment').': '.Display::url(basename($file['value']), $url, ['target' => '_blank']);
        }

        return '';
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getAttachment($id)
    {
        $extraFieldValue = new ExtraFieldValue('scheduled_announcement');
        $attachment = $extraFieldValue->get_values_by_handler_and_field_variable($id, 'attachment');

        return $attachment;
    }

    /**
     * @param int $urlId
     *
     * @return int
     */
    public function sendPendingMessages($urlId = 0)
    {
        if (!$this->allowed()) {
            return 0;
        }

        $messagesSent = 0;
        $now = api_get_utc_datetime();
        $result = $this->get_all();
        $extraFieldValue = new ExtraFieldValue('scheduled_announcement');

        // get user extra fields list (only visible to self and filter-able)
        $extraField = new ExtraField('user');
        $extraFields = $extraField->get_all(['filter = ? AND visible_to_self = ?' => [1, 1]]);

        foreach ($result as $result) {
            if (empty($result['sent'])) {
                if (!empty($result['date']) && $result['date'] < $now) {
                    $sessionId = $result['session_id'];
                    $sessionInfo = api_get_session_info($sessionId);
                    if (empty($sessionInfo)) {
                        continue;
                    }
                    $users = SessionManager::get_users_by_session(
                        $sessionId,
                        0,
                        false,
                        $urlId
                    );

                    $coachId = $sessionInfo['id_coach'];

                    if (empty($users) || empty($coachId)) {
                        continue;
                    }

                    $coachList = [];
                    if ($users) {
                        $sendToCoaches = $extraFieldValue->get_values_by_handler_and_field_variable(
                            $result['id'],
                            'send_to_coaches'
                        );
                        $courseList = SessionManager::getCoursesInSession($sessionId);
                        if (!empty($sendToCoaches) && !empty($sendToCoaches['value']) && 1 == $sendToCoaches['value']) {
                            foreach ($courseList as $courseItemId) {
                                $coaches = SessionManager::getCoachesByCourseSession(
                                    $sessionId,
                                    $courseItemId
                                );
                                $coachList = array_merge($coachList, $coaches);
                            }
                            $coachList = array_unique($coachList);
                        }

                        $this->update(['id' => $result['id'], 'sent' => 1]);
                        $attachments = $this->getAttachmentToString($result['id']);
                        $subject = $result['subject'];

                        $courseInfo = [];
                        if (!empty($courseList)) {
                            $courseId = current($courseList);
                            $courseInfo = api_get_course_info_by_id($courseId);
                        }

                        $message = '';
                        foreach ($users as $user) {
                            // Take original message
                            $message = $result['message'];
                            $userInfo = api_get_user_info($user['user_id']);
                            $userPicture = UserManager::getUserPicture($user['user_id'], USER_IMAGE_SIZE_ORIGINAL);

                            $progress = '';
                            if (!empty($sessionInfo) && !empty($courseInfo)) {
                                $progress = Tracking::get_avg_student_progress(
                                    $user['user_id'],
                                    $courseInfo['code'],
                                    [],
                                    $sessionId
                                );
                            }

                            if (is_numeric($progress)) {
                                $progress = $progress.'%';
                            } else {
                                $progress = '0%';
                            }

                            $startTime = api_get_local_time(
                                $sessionInfo['access_start_date'],
                                null,
                                null,
                                true
                            );
                            $endTime = api_get_local_time(
                                $sessionInfo['access_end_date'],
                                null,
                                null,
                                true
                            );

                            $generalCoach = '';
                            $generalCoachEmail = '';
                            if (!empty($coachId)) {
                                $coachInfo = api_get_user_info($coachId);
                                if (!empty($coachInfo)) {
                                    $generalCoach = $coachInfo['complete_name'];
                                    $generalCoachEmail = $coachInfo['email'];
                                }
                            }

                            $tags = [
                                '((session_name))' => $sessionInfo['name'],
                                '((session_start_date))' => $startTime,
                                '((general_coach))' => $generalCoach,
                                '((general_coach_email))' => $generalCoachEmail,
                                '((session_end_date))' => $endTime,
                                '((user_username))' => $userInfo['username'],
                                '((user_complete_name))' => $userInfo['complete_name'],
                                '((user_firstname))' => $userInfo['firstname'],
                                '((user_lastname))' => $userInfo['lastname'],
                                '((user_first_name))' => $userInfo['firstname'],
                                '((user_last_name))' => $userInfo['lastname'],
                                '((user_picture))' => $userPicture,
                                '((lp_progress))' => $progress,
                            ];

                            if (!empty($extraFields)) {
                                $efv = new ExtraFieldValue('user');

                                foreach ($extraFields as $extraField) {
                                    $valueExtra = $efv->get_values_by_handler_and_field_variable(
                                        $user['user_id'],
                                        $extraField['variable'],
                                        true
                                    );
                                    $tags['(('.strtolower($extraField['variable']).'))'] = $valueExtra['value'];
                                }
                            }

                            $message = str_replace(array_keys($tags), $tags, $message);
                            $message .= $attachments;

                            MessageManager::send_message_simple(
                                $userInfo['user_id'],
                                $subject,
                                $message,
                                $coachId
                            );
                        }

                        $message = get_lang('YouAreReceivingACopyBecauseYouAreACourseCoach').'<br /><br />'.$message;

                        foreach ($coachList as $courseCoachId) {
                            MessageManager::send_message_simple(
                                $courseCoachId,
                                get_lang('YouAreReceivingACopyBecauseYouAreACourseCoach').'&nbsp;'.$subject,
                                $message,
                                $coachId
                            );
                        }
                    }

                    $messagesSent++;
                }
            }
        }

        return $messagesSent;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        $tags = [
            '((session_name))',
            '((session_start_date))',
            '((session_end_date))',
            '((general_coach))',
            '((general_coach_email))',
            '((user_username))',
            '((user_complete_name))',
            '((user_first_name))',
            '((user_last_name))',
            '((user_picture))',
            '((lp_progress))',
        ];
        // get user extra fields list (only visible to self and filter-able)
        $extraField = new ExtraField('user');
        $extraFields = $extraField->get_all(['filter = ? AND visible_to_self = ?' => [1, 1]]);
        if (!empty($extraFields)) {
            foreach ($extraFields as $extraField) {
                $tags[] = '(('.strtolower($extraField['variable']).'))';
            }
        }

        return $tags;
    }

    /**
     * @return bool
     */
    public function allowed()
    {
        return api_get_configuration_value('allow_scheduled_announcements');
    }

    /**
     * @param FormValidator $form
     */
    private function setTagsInForm(&$form)
    {
        $form->addLabel(
            get_lang('Tags'),
            Display::return_message(
                implode('<br />', $this->getTags()),
                'normal',
                false
            )
        );
    }
}
