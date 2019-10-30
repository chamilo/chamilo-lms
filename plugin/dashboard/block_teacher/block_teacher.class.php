<?php
/* For licensing terms, see /license.txt */

/**
 * This file is part of teacher block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 */

/**
 * This class is used like controller for teacher block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockTeacher"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockTeacher extends Block
{
    private $user_id;
    private $teachers;
    private $permission = [DRH];

    /**
     * Controller.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_teacher';
        if ($this->is_block_visible_for_user($user_id)) {
            $this->teachers = UserManager::get_users_followed_by_drh($user_id, COURSEMANAGER);
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
     * This method return content html containing information about
     * teachers and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from
     * dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        $column = 1;
        $data = [];
        $html = $this->getBlockCard(
            get_lang('Teachers report'),
            $this->getContent()
        );
        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a content html, it's used inside get_block method
     * for showing it inside dashboard interface.
     *
     * @return string content html
     */
    public function getContent()
    {
        $teachers = $this->teachers;
        $teachers_table = null;
        if (count($teachers) > 0) {
            $teachers_table .= '<table class="data_table" width:"95%">';
            $teachers_table .= '
                                <tr>
                                    <th>'.get_lang('User').'</th>
                                    <th>'.get_lang('Time spent in portal').'</th>
                                    <th>'.get_lang('Latest login').'</th>
                                </tr>
                            ';
            $i = 1;
            foreach ($teachers as $teacher) {
                $teacher_id = $teacher['user_id'];
                $firstname = $teacher['firstname'];
                $lastname = $teacher['lastname'];
                $username = $teacher['username'];

                $time_on_platform = api_time_to_hms(Tracking::get_time_spent_on_the_platform($teacher_id));
                $last_connection = Tracking::get_last_connection_date($teacher_id);

                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
                $teachers_table .= '
                                    <tr class="'.$class_tr.'">
                                        <td>'.api_get_person_name($firstname, $lastname).' ('.$username.')</td>
                                        <td align="right">'.$time_on_platform.'</td>
                                        <td align="right">'.$last_connection.'</td>
                                    </tr>
                                    ';
                $i++;
            }
            $teachers_table .= '</table>';
        } else {
            $teachers_table .= get_lang('There is no available information about your teachers');
        }

        $content = $teachers_table;

        if (count($teachers) > 0) {
            $content .= '<div style="text-align:right;margin-top:10px;">
            <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin">'.get_lang('See more').'</a></div>';
        }

        return $content;
    }

    /**
     * @return string
     */
    public function get_teachers_content_html_for_drh()
    {
        $teachers = $this->teachers;
        $content = '<h4>'.get_lang('Your teachers').'</h4>';
        $teachers_table = null;
        if (count($teachers) > 0) {
            $a_last_week = get_last_week();
            $last_week = date('Y-m-d', $a_last_week[0]).' '.get_lang('To').' '.date('Y-m-d', $a_last_week[6]);

            $teachers_table .= '<table class="data_table" width:"95%">';
            $teachers_table .= '
                                <tr>
                                    <th>'.get_lang('User').'</th>
                                    <th>'.get_lang('Time spent last week').'<br />'.$last_week.'</th>
                                </tr>
                            ';

            $i = 1;
            foreach ($teachers as $teacher) {
                $teacher_id = $teacher['user_id'];
                $firstname = $teacher['firstname'];
                $lastname = $teacher['lastname'];
                $username = $teacher['username'];
                $time_on_platform = api_time_to_hms(
                    Tracking::get_time_spent_on_the_platform($teacher_id, true)
                );

                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
                $teachers_table .= '<tr class="'.$class_tr.'">
                                        <td>'.api_get_person_name($firstname, $lastname).' ('.$username.')</td>
                                        <td align="right">'.$time_on_platform.'</td>
                                    </tr>';

                $i++;
            }
            $teachers_table .= '</table>';
        } else {
            $teachers_table .= get_lang('There is no available information about your teachers');
        }
        $content .= $teachers_table;
        if (count($teachers) > 0) {
            $content .= '<div style="text-align:right;margin-top:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/teachers.php">'.get_lang('See more').'</a></div>';
        }

        return $content;
    }

    /**
     * Get number of teachers.
     *
     * @return int
     */
    public function get_number_of_teachers()
    {
        return count($this->teachers);
    }
}
