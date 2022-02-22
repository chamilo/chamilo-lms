<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;

/**
 * Config setting:
 * $_configuration['allow_scheduled_announcements'] = true;.
 *
 * Setup linux cron file:
 * main/cron/scheduled_announcement.php
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

    public function get_all(array $options = []): array
    {
        return Database::select(
            '*',
            $this->table,
            ['where' => $options, 'order' => 'subject ASC']
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
            Display::return_icon('tuning.png', get_lang('Send pending announcements manually'), '', ICON_SIZE_MEDIUM).
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

        $form->addCheckBox('sent', null, get_lang('Message Sent'));

        if ('edit' === $action) {
            $form->addButtonUpdate(get_lang('Edit'));
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
            $header = get_lang('Edit');
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
                    nl2br(
                        get_lang(
                            'This form allows scheduling announcements to be sent automatically to the students who are taking a course in a session.'
                        )
                    ),
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
            'specific_date' => get_lang('Specific dispatch date'),
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
            get_lang('days'),
            false
        );

        $form->addSelect(
            'moment_type',
            get_lang('After or before'),
            [
                'after' => get_lang('After'),
                'before' => get_lang('Before'),
            ]
        );

        if (!empty($startDate)) {
            $options['start_date'] = get_lang('Start Date').' - '.$startDate;
        }

        if (!empty($endDate)) {
            $options['end_date'] = get_lang('End Date').' - '.$endDate;
        }
        if (!empty($options)) {
            $form->addSelect('base_date', get_lang('Dispatch based on the session\'s start/end dates'), $options);
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
            $form->addButtonUpdate(get_lang('Edit'));
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
            $url = $file['url'];

            return get_lang('Attachment').': '.Display::url(basename($file['url']), $url, ['target' => '_blank']);
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

        return $extraFieldValue->get_values_by_handler_and_field_variable($id, 'attachment');
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

        foreach ($result as $result) {
            if (empty($result['sent'])) {
                if (!empty($result['date']) && $result['date'] < $now) {
                    $sessionId = $result['session_id'];
                    $sessionInfo = api_get_session_info($sessionId);
                    $session = api_get_session_entity($sessionId);
                    if (empty($sessionInfo)) {
                        continue;
                    }
                    $users = SessionManager::get_users_by_session(
                        $sessionId,
                        Session::STUDENT,
                        false,
                        $urlId
                    );
                    $generalCoaches = $session->getGeneralCoaches();

                    if (empty($users) || 0 === $generalCoaches->count()) {
                        continue;
                    }

                    $coachId = $generalCoaches->first()->getId();

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

                            $generalCoachName = [];
                            $generalCoachEmail = [];
                            /** @var User $generalCoach */
                            foreach ($generalCoaches as $generalCoach) {
                                $generalCoachName[] = $generalCoach->getFullname();
                                $generalCoachEmail[] = $generalCoach->getEmail();
                            }

                            $tags = [
                                '((session_name))' => $sessionInfo['name'],
                                '((session_start_date))' => $startTime,
                                '((general_coach))' => implode(' - ', $generalCoachName),
                                '((general_coach_email))' => implode(' - ', $generalCoachEmail),
                                '((session_end_date))' => $endTime,
                                '((user_complete_name))' => $userInfo['complete_name'],
                                '((user_firstname))' => $userInfo['firstname'],
                                '((user_lastname))' => $userInfo['lastname'],
                                '((user_first_name))' => $userInfo['firstname'],
                                '((user_last_name))' => $userInfo['lastname'],
                                '((lp_progress))' => $progress,
                            ];

                            $message = str_replace(array_keys($tags), $tags, $message);
                            $message .= $attachments;

                            MessageManager::send_message_simple(
                                $userInfo['user_id'],
                                $subject,
                                $message,
                                $coachId
                            );
                        }

                        $message = get_lang('You\'re receiving a copy because, you\'re a course coach').
                            '<br /><br />'.$message;

                        foreach ($coachList as $courseCoachId) {
                            MessageManager::send_message_simple(
                                $courseCoachId,
                                get_lang('You\'re receiving a copy because, you\'re a course coach').'&nbsp;'.$subject,
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
        return [
            '((session_name))',
            '((session_start_date))',
            '((session_end_date))',
            '((general_coach))',
            '((general_coach_email))',
            '((user_complete_name))',
            '((user_first_name))',
            '((user_last_name))',
            '((lp_progress))',
        ];
    }

    /**
     * @return bool
     */
    public function allowed()
    {
        return api_get_configuration_value('allow_scheduled_announcements');
    }

    private function setTagsInForm(FormValidator $form)
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
