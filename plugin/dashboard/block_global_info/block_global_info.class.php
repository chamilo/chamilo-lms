<?php
/* See license terms in /license.txt */

/**
 * This file is part of global info block plugin for dashboard,
 * it should be required inside the dashboard controller for
 * showing it into the dashboard interface.
 *
 * @package chamilo.dashboard
 *
 * @author Yannick Warnier
 */

/**
 * This class is used like controller for this global info block plugin
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockGlobalInfo"), so dashboard controller can
 * instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockGlobalInfo extends Block
{
    private $user_id;
    private $courses;
    private $permission = [];

    /**
     * Constructor.
     *
     * @param int $user_id
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_global_info';
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
     * This method return content html containing information
     * about courses and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        $column = 2;
        $data = [];
        $html = $this->getBlockCard(
            get_lang('Global platform information'),
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
        $global_data = $this->get_global_information_data();
        $data_table = null;
        if (!empty($global_data)) {
            $data_table = '<table class="table table-bordered">';
            $i = 1;
            foreach ($global_data as $data) {
                if ($i % 2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
                $data_table .= '<tr class="'.$class_tr.'">';
                foreach ($data as $cell) {
                    $data_table .= '<td align="right">'.$cell.'</td>';
                }
                $data_table .= '</tr>';
                $i++;
            }
            $data_table .= '</table>';
        } else {
            $data_table .= get_lang('ThereIsNoInformationAboutThePlatform');
        }

        return $data_table;
    }

    /**
     * Get global information data.
     *
     * @return array
     */
    public function get_global_information_data()
    {
        // Two-dimensional array with data about the system
        $path = api_get_path(WEB_CODE_PATH);
        // Check total number of users
        $global_info = [
            [get_lang('Number of users'), '<a href="'.$path.'admin/user_list.php">'.Statistics::countUsers().'</a>'],
            // Check only active users
            [get_lang('Number of active users'), '<a href="'.$path.'admin/user_list.php?keyword_firstname=&amp;keyword_lastname=&amp;keyword_username=&amp;keyword_email=&amp;keyword_officialcode=&amp;keyword_status=%25&amp;keyword_active=1&amp;submit=&amp;_qf__advanced_search=">'.Statistics::countUsers(null, null, null, true).'</a>'],
            // Check number of courses
            [get_lang('Total number of courses'), '<a href="'.$path.'admin/course_list.php">'.Statistics::countCourses().'</a>'],
            [get_lang('Number of public courses'), '<a href="'.$path.'admin/course_list.php?keyword_code=&amp;keyword_title=&amp;keyword_language=%25&amp;keyword_category=&amp;keyword_visibility='.COURSE_VISIBILITY_OPEN_WORLD.'&amp;keyword_subscribe=%25&amp;keyword_unsubscribe=%25&amp;submit=&amp;_qf__advanced_course_search=">'.Statistics::countCoursesByVisibility(COURSE_VISIBILITY_OPEN_WORLD).'</a>'],
            [get_lang('Number of open courses'), '<a href="'.$path.'admin/course_list.php?keyword_code=&amp;keyword_title=&amp;keyword_language=%25&amp;keyword_category=&amp;keyword_visibility='.COURSE_VISIBILITY_OPEN_PLATFORM.'&amp;keyword_subscribe=%25&amp;keyword_unsubscribe=%25&amp;submit=&amp;_qf__advanced_course_search=">'.Statistics::countCoursesByVisibility(COURSE_VISIBILITY_OPEN_PLATFORM).'</a>'],
            [get_lang('Number of private courses'), '<a href="'.$path.'admin/course_list.php?keyword_code=&amp;keyword_title=&amp;keyword_language=%25&amp;keyword_category=&amp;keyword_visibility='.COURSE_VISIBILITY_REGISTERED.'&amp;keyword_subscribe=%25&amp;keyword_unsubscribe=%25&amp;submit=&amp;_qf__advanced_course_search=">'.Statistics::countCoursesByVisibility(COURSE_VISIBILITY_REGISTERED).'</a>'],
            [get_lang('Number of closed courses'), '<a href="'.$path.'admin/course_list.php?keyword_code=&amp;keyword_title=&amp;keyword_language=%25&amp;keyword_category=&amp;keyword_visibility='.COURSE_VISIBILITY_CLOSED.'&amp;keyword_subscribe=%25&amp;keyword_unsubscribe=%25&amp;submit=&amp;_qf__advanced_course_search=">'.Statistics::countCoursesByVisibility(COURSE_VISIBILITY_CLOSED).'</a>'],
            [get_lang('Number of hidden courses'), '<a href="'.$path.'admin/course_list.php?keyword_code=&amp;keyword_title=&amp;keyword_language=%25&amp;keyword_category=&amp;keyword_visibility='.COURSE_VISIBILITY_HIDDEN.'&amp;keyword_subscribe=%25&amp;keyword_unsubscribe=%25&amp;submit=&amp;_qf__advanced_course_search=">'.Statistics::countCoursesByVisibility(COURSE_VISIBILITY_HIDDEN).'</a>'],
        ];

        return $global_info;
    }
}
