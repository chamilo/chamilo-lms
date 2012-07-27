<?php
/**
 * This file is part of global info block plugin for dashboard,
 * it should be required inside the dashboard controller for 
 * showing it into the dashboard interface.
 * @package chamilo.dashboard
 * @author Yannick Warnier
 */

/**
 * required files for getting data
 */
require_once api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php';

/**
 * This class is used like controller for this global info block plugin
 * the class name must be registered inside path.info file 
 * (e.g: controller = "BlockGlobalInfo"), so dashboard controller can 
 * instantiate it
 * @package chamilo.dashboard
 */
class BlockGlobalInfo extends Block {

    private $user_id;
    private $courses;
    private $path;	
    private $permission = array();

    /**
     * Constructor
     */
    public function __construct ($user_id) {    	
        $this->user_id 		= $user_id;
        $this->path 		= 'block_global_info';				
        if ($this->is_block_visible_for_user($user_id)) {
            //$this->courses = CourseManager::get_courses_followed_by_drh($user_id);
        }
    }
    
    /**
     * This method check if a user is allowed to see the block inside dashboard interface
     * @param	int		User id
     * @return	bool	Is block visible for user
     */    
    public function is_block_visible_for_user($user_id) {	
    	$user_info = api_get_user_info($user_id);
    	$user_status = $user_info['status'];
    	$is_block_visible_for_user = false;
    	if (UserManager::is_admin($user_id) || in_array($user_status, $this->permission)) {
    		$is_block_visible_for_user = true;
    	}    	
    	return $is_block_visible_for_user;    	
    }
    

    /**
     * This method return content html containing information about courses and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller
     * @return array   column and content html
     */
    public function get_block() {

    	global $charset;

    	$column = 2;
    	$data   = array();
    	$content = '';
    	$data_table = '';
    	$content = $this->get_content_html();
    	$html = '
    	            <li class="widget color-red" id="intro">
    	                <div class="widget-head">
    	                    <h3>'.get_lang('GlobalPlatformInformation').'</h3>
    	                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
    	                </div>
    	                <div class="widget-content">
    	                   '.$content.'
    	                </div>
    	            </li>
    			';
    	$data['column'] = $column;
    	$data['content_html'] = $html;

    	return $data;
    }

 	/**
 	 * This method return a content html, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  content html
 	 */
     public function get_content_html() {

         $global_data = $this->get_global_information_data();
         $content = '<div style="margin:10px;">';
         $content .= '<h3><font color="#000">'.get_lang('GlobalPlatformInformation').'</font></h3>';
         if (!empty($global_data)) {
             $data_table = '<table class="data_table" width:"95%">';
             $i = 1;
             foreach ($global_data as $data) {
                 if ($i%2 == 0) {
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
         $content .= $data_table;
         $content .= '</div>';

         return $content;
    }

    /**
     * Get global information data
     * @return array
     */
    function get_global_information_data() {
        // Two-dimensional array with data about the system
        $global_info = array();
        // Check total number of users
        $global_info[] = array(get_lang('CountUsers'), statistics::count_users());
        // Check only active users
        $global_info[] = array(get_lang('NumberOfUsersActive'), statistics::count_users(null,null,null,true));
        // Check number of courses
        $global_info[] = array(get_lang('NumberOfCoursesTotal'), statistics::count_courses());
        $global_info[] = array(get_lang('NumberOfCoursesClosed'), statistics::count_courses_by_visibility(COURSE_VISIBILITY_CLOSED));
        $global_info[] = array(get_lang('NumberOfCoursesPrivate'), statistics::count_courses_by_visibility(COURSE_VISIBILITY_REGISTERED));
        $global_info[] = array(get_lang('NumberOfCoursesOpen'), statistics::count_courses_by_visibility(COURSE_VISIBILITY_OPEN_PLATFORM));
        $global_info[] = array(get_lang('NumberOfCoursesPublic'), statistics::count_courses_by_visibility(COURSE_VISIBILITY_OPEN_WORLD));
        return $global_info;
    }

}

