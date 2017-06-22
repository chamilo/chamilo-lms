<?php
/* For licensing terms, see /license.txt */

/**
 * Class ScheduledAnnouncement
 * Requires DB change:
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
 *
 * @package chamilo.library
 */
class ScheduledAnnouncement extends Model
{
    public $table;
    public $columns = array('id', 'subject', 'message', 'date', 'sent', 'session_id');

    /**
     * Constructor
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
    public function get_all($where_conditions = array())
    {
        return Database::select(
            '*',
            $this->table,
            array('where' => $where_conditions, 'order' => 'subject ASC')
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
            array(),
            'first'
        );

        return $row['count'];
    }

    /**
     * Displays the title + grid
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
            Display::return_icon('mail_send.png', get_lang('Send'), '', ICON_SIZE_MEDIUM).
            '</a>';

        $action .= '</div>';

        $html = $action;
        $html .= '<div id="session-table" class="table-responsive">';
        $html .= Display::grid_html('programmed');
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns a Form validator Obj
     * @param   string  $url
     * @param   string  $action add, edit
     *
     * @return  FormValidator form validator obj
     */
    public function returnSimpleForm($url, $action, $sessionInfo = [])
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
        $this->setTagsInForm($form);

        $form->addCheckBox('sent', null, get_lang('MessageSent'));

        if ($action == 'edit') {
            $form->addButtonUpdate(get_lang('Modify'));
        }

        return $form;
    }

    /**
     * @param FormValidator $form
     */
    private function setTagsInForm(& $form)
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

    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  $url
     * @param   string  $action add, edit
     * @param array
     * @return  FormValidator form validator obj
     */
    public function returnForm($url, $action, $sessionInfo = [])
    {
        // Setting the form elements
        $header = get_lang('Add');

        if ($action == 'edit') {
            $header = get_lang('Modify');
        }

        $form = new FormValidator(
            'announcement',
            'post',
            $url
        );

        $form->addHeader($header);
        if ($action == 'add') {
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
            'specific_date' => get_lang('SpecificDate')
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
            "]
        );

        $form->addElement('html', '<div id="specific_date">');
        $form->addDateTimePicker('date', get_lang('Date'));
        $form->addElement('html', '</div>');
        $form->addElement('html', '<div id="options" style="display:none">');

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

        $form->addElement('html', '</div>');

        $form->addText('subject', get_lang('Subject'));
        $form->addHtmlEditor('message', get_lang('Message'));
        $this->setTagsInForm($form);

        if ($action == 'edit') {
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        return $form;
    }

    /**
     * @return int
     */
    public function sendPendingMessages()
    {
        if (!$this->allowed()) {
            return 0;
        }

        $messagesSent = 0;
        $now = api_get_utc_datetime();
        $courseCode = api_get_course_id();
        $result = $this->get_all();

        foreach ($result as $result) {
            if (empty($result['sent'])) {
                if (!empty($result['date']) && $result['date'] < $now) {
                    $sessionId = $result['session_id'];
                    $sessionInfo = api_get_session_info($sessionId);
                    self::update(['id' => $result['id'], 'sent' => 1]);
                    $users = SessionManager::get_users_by_session($sessionId);
                    $subject = $result['subject'];
                    $message = $result['message'];

                    if ($users) {
                        foreach ($users as $user) {
                            $userInfo = api_get_user_info($user['user_id']);
                            $progress = Tracking::get_avg_student_progress(
                                $user['user_id'],
                                $courseCode,
                                null,
                                $sessionId
                            );

                            if (is_numeric($progress)) {
                                $progress = $progress.'%';
                            } else {
                                $progress = '0%';
                            }

                            $tags = [
                                '((session_name))' => $sessionInfo['name'],
                                '((user_complete_name))' => $userInfo['complete_name'],
                                '((user_first_name))' => $userInfo['firstname'],
                                '((user_last_name))' => $userInfo['lastname'],
                                //'((course_title))' => $userInfo['lastname'],
                                '((lp_progress))' => $progress,
                            ];

                            $message = str_replace(array_keys($tags), $tags, $message);

                            MessageManager::send_message(
                                $user['user_id'],
                                $subject,
                                $message
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
            '((user_complete_name))',
            '((user_first_name))',
            '((user_last_name))',
            '((lp_progress))'
        ];

        return $tags;
    }

    /**
     * @return bool
     */
    public function allowed()
    {
        return api_get_configuration_value('allow_scheduled_announcements');
    }
}
