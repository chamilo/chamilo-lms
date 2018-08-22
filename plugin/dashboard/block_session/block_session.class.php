<?php
/**
 * This file is part of session block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 */

/**
 * This class is used like controller for this session block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockSession"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockSession extends Block
{
    private $user_id;
    private $sessions;
    private $permission = [DRH, SESSIONADMIN];

    /**
     * Constructor.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_session';
        if ($this->is_block_visible_for_user($user_id)) {
            $this->sessions = SessionManager::get_sessions_followed_by_drh($user_id);
        }
    }

    /**
     * This method check if a user is allowed to see the block inside dashboard interface.
     *
     * @param int        User id
     *
     * @return bool Is block visible for user
     */
    public function is_block_visible_for_user($user_id)
    {
        $user_info = api_get_user_info($user_id);
        $user_status = $user_info['status'];
        $is_block_visible_for_user = false;
        if (UserManager::is_admin($user_id) || in_array($user_status, $this->permission)) {
            $is_block_visible_for_user = true;
        }

        return $is_block_visible_for_user;
    }

    /**
     * This method return content html containing
     * information about sessions and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        $column = 2;
        $data = [];
        $html = $this->getBlockCard(
            get_lang('YourSessionsList'),
            $this->getContent()
        );

        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a content html, it's used inside get_block method for showing it inside dashboard interface.
     *
     * @return string content html
     */
    public function getContent()
    {
        $content = '';
        $sessions = $this->sessions;
        if (count($sessions) > 0) {
            $sessions_table = '<table class="data_table" width:"95%">';
            $sessions_table .= '<tr>
                                    <th >'.get_lang('Title').'</th>
                                    <th >'.get_lang('Date').'</th>
                                    <th width="100px">'.get_lang('NbCoursesPerSession').'</th>
                                </tr>';
            $i = 1;
            foreach ($sessions as $session) {
                $session_id = intval($session['id']);
                $title = $session['name'];

                if (!empty($session['access_start_date'])) {
                    $dateFrom = api_convert_and_format_date(
                        $session['access_start_date'],
                        DATE_FORMAT_SHORT,
                        date_default_timezone_get()
                    );
                    $dateUntil = api_convert_and_format_date(
                        $session['access_end_date'],
                        DATE_FORMAT_SHORT,
                        date_default_timezone_get()
                    );

                    $date = vsprintf(get_lang('FromDateXToDateY'), [$dateFrom, $dateUntil]);
                } else {
                    $date = ' - ';
                }

                $count_courses_in_session = count(Tracking::get_courses_list_from_session($session_id));

                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }

                $sessions_table .= '<tr class="'.$class_tr.'">
                                        <td>'.$title.'</td>
                                        <td align="center">'.$date.'</td>
                                        <td align="center">'.$count_courses_in_session.'</td>
                                   </tr>';
                $i++;
            }
            $sessions_table .= '</table>';
            $content .= $sessions_table;
        } else {
            $content .= get_lang('ThereIsNoInformationAboutYourSessions');
        }

        if (count($sessions) > 0) {
            $content .= '<div style="text-align:right;margin-top:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/session.php">'.get_lang('SeeMore').'</a></div>';
        }

        return $content;
    }

    /**
     * Get number of sessions.
     *
     * @return int
     */
    public function get_number_of_sessions()
    {
        return count($this->sessions);
    }
}
