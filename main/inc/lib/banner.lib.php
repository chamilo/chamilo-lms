<?php
/* For licensing terms, see /license.txt */
/**
 * Code
 * @todo use globals or parameters or add this file in the template
 * @package chamilo.include
 */

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and 
 * all the sections that do not appear there (as determined by the 
 * platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages
 * @return array containing all the possible tabs
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs() {
	global $_course;
    
    $navigation = array();
    
	// Campus Homepage
	$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
	$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

	// My Courses
	if (api_get_setting('use_session_mode')=='true') {
		if(api_is_allowed_to_create_course()) {
			// Link to my courses for teachers
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		} else {
			// Link to my courses for students
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}
	} else {
		// Link to my courses
		$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
		$navigation['mycourses']['title'] = get_lang('MyCourses');
	}

	// My Profile
	$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myprofile']['title'] = get_lang('ModifyProfile');

	// Link to my agenda
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=personal';
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
		// Link to my space
		$navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/';
		$navigation['session_my_space']['title'] = get_lang('MySpace');
	} else {
		// Link to my progress
		$navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
		$navigation['session_my_progress']['title'] = get_lang('MyProgress');
	}
	
	// Social
	if (api_get_setting('allow_social_tool')=='true') {
		$navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
                
        // get count unread message and total invitations
        $count_unread_message = MessageManager::get_number_of_messages(true);
     
        $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION,false);
        $group_pending_invitations = 0;
        if (!empty($group_pending_invitations )) {        
	        $group_pending_invitations = count($group_pending_invitations);
        }
        $total_invitations = intval($number_of_new_messages_of_friend) + $group_pending_invitations + intval($count_unread_message);
        $total_invitations = (!empty($total_invitations) ? Display::badge($total_invitations) :'');        
        
		$navigation['social']['title'] = get_lang('SocialNetwork'). $total_invitations;
	}
	
	// Dashboard
	if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
		$navigation['dashboard']['url'] = api_get_path(WEB_CODE_PATH).'dashboard/index.php';
		$navigation['dashboard']['title'] = get_lang('Dashboard');
	}

	// Reports
	if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {        
        $navigation['reports']['url'] = api_get_path(WEB_CODE_PATH).'reports/index.php';
        $navigation['reports']['title'] = get_lang('Reports');
	}

	// Custom tabs
	for ($i = 1; $i<=3; $i++) 
		if (api_get_setting('custom_tab_'.$i.'_name') && api_get_setting('custom_tab_'.$i.'_url')) {
			$navigation['custom_tab_'.$i]['url'] = api_get_setting('custom_tab_'.$i.'_url');
			$navigation['custom_tab_'.$i]['title'] = api_get_setting('custom_tab_'.$i.'_name');
		}

	// Platform administration
	if (api_is_platform_admin(true)) {
		$navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}
	return $navigation;
}

function return_logo($theme) {    
    $_course = api_get_course_info();    
    $html = '';
    $logo = api_get_path(SYS_CODE_PATH).'css/'.$theme.'/images/header-logo.png';            
    
    $site_name = api_get_setting('siteName');
    if (file_exists($logo)) {
        $site_name = api_get_setting('Institution').' - '.$site_name;
        $html .= '<div id="logo">';
            $image_url = api_get_path(WEB_CSS_PATH).$theme.'/images/header-logo.png';           
            $logo = Display::img($image_url, $site_name, array('title'=>$site_name));
            $html .= Display::url($logo, api_get_path(WEB_PATH).'index.php');
        $html .= '</div>';
    } else {         
        $html .= '<a href="'.api_get_path(WEB_PATH).'index.php" target="_top">'.$site_name.'</a>';                    
        $iurl  = api_get_setting('InstitutionUrl');
        $iname = api_get_setting('Institution');

        if (!empty($iname)) {
            $html .= '-&nbsp;<a href="'.$iurl.'" target="_top">'.$iname.'</a>';
        }           
        // External link section a.k.a Department - Department URL          
        if (isset($_course['extLink']) && $_course['extLink']['name'] != '') {
            $html .= '<span class="extLinkSeparator"> - </span>';
            if ($_course['extLink']['url'] != '') {
                $html .= '<a class="extLink" href="'.$_course['extLink']['url'].'" target="_top">';
                $html .= $_course['extLink']['name'];
                $html .= '</a>';
            } else {
                $html .= $_course['extLink']['name'];
            }
        }
    }        
        
   /* //  Course title section 
    if (!empty($_cid) and $_cid != -1 and isset($_course)) {
        //Put the name of the course in the header  
        $html .= '<div id="my_courses">';     
        $html .= '</div>';        
    } elseif (isset($nameTools) && $language_file != 'course_home') {
        //Put the name of the user-tools in the header
        if (!isset($user_id)) {
            //echo '<div id="my_courses"></div>';
        } elseif (!$noPHP_SELF) {
            $html .= '<div id="my_courses"><a href="'.api_get_self().'?'.api_get_cidreq(). '" target="_top">'.$nameTools.'</a></div>';
        } else {
            $html .= '<div id="my_courses">'.$nameTools.'</div>';
        }   
    }*/    
    return $html;
}

function return_notification_menu() {

    $_course    = api_get_course_info(); 
    $course_id  = api_get_course_id();
    $user_id    = api_get_user_id();
        
    $html = '';
    
    if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting('showonline', 'users') == 'true' AND $user_id) OR (api_get_setting('showonline', 'course') == 'true' AND $user_id AND $course_id)) {
        $number = who_is_online_count(api_get_setting('time_limit_whosonline'));            
        
        $number_online_in_course = 0;
        if(!empty($_course['id'])) {
            $number_online_in_course = who_is_online_in_this_course_count($user_id, api_get_setting('time_limit_whosonline'), $_course['id']);
        }               
        
        // Display the who's online of the platform
        if ($number) {
            if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting('showonline', 'users') == 'true' AND $user_id)) {
                $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_top" title="'.get_lang('UsersOnline').'" >'.Display::return_icon('members.gif', get_lang('UsersOnline'), array('width'=>'13px')).' '.$number.'</a></li>';
            }
        }
    
        // Display the who's online for the course
        if ($number_online_in_course) {
            if (is_array($_course) AND api_get_setting('showonline', 'course') == 'true' AND isset($_course['sysCode'])) {
                $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php?cidReq='.$_course['sysCode'].'" target="_top">'.Display::return_icon('course.gif', get_lang('UsersOnline').' '.get_lang('InThisCourse'), array('width'=>'13px')).' '.$number_online_in_course.' </a></li>';
            }
        }
        
        // Display the who's online for the session 
        if (api_get_setting('use_session_mode') == 'true' && isset($user_id) && api_get_session_id() != 0) {
            //echo '<li><a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php?id_coach='.$user_id.'&amp;referer='.urlencode($_SERVER['REQUEST_URI']).'" target="_top">'.get_lang('UsersConnectedToMySessions').'</a></li>';        
            $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php?id_coach='.$user_id.'&amp;referer='.urlencode($_SERVER['REQUEST_URI']).'" target="_top">'.Display::return_icon('session.png', get_lang('UsersConnectedToMySessions'), array('width'=>'13px')).' </a></li>';
        }        
    }
    
    if ($user_id && isset($course_id)) {
        if ((api_is_course_admin() || api_is_platform_admin()) && api_get_setting('student_view_enabled') == 'true') {
            $html .= '<li>';
            $html .= api_display_tool_view_option();
            $html .= '</li>';
        }
    }
    
    if (api_get_setting('accessibility_font_resize') == 'true') {
        $html .= '<li class="resize_font">';
        $html .= '<span class="decrease_font" title="'.get_lang('DecreaseFontSize').'">A</span> <span class="reset_font" title="'.get_lang('ResetFontSize').'">A</span> <span class="increase_font" title="'.get_lang('IncreaseFontSize').'">A</span>';
        $html .= '</li>';
    }        
    return $html;
}

function return_navigation_array() {
    
    $navigation         = array();
    $menu_navigation    = array();
    $possible_tabs      = get_tabs();        
        
    // Campus Homepage
    if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
        $navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    } else {
        $menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    }
    
    if (api_get_user_id() && !api_is_anonymous()) {
        // My Courses
        if (api_get_setting('show_tabs', 'my_courses') == 'true') {
            $navigation['mycourses'] = $possible_tabs['mycourses'];
        } else {
            $menu_navigation['mycourses'] = $possible_tabs['mycourses'];
        }
    
        // My Profile
        if (api_get_setting('show_tabs', 'my_profile') == 'true' && api_get_setting('allow_social_tool') != 'true') {
            $navigation['myprofile'] = $possible_tabs['myprofile'];
        } else {
            $menu_navigation['myprofile'] = $possible_tabs['myprofile'];
        }
    
        // My Agenda
        if (api_get_setting('show_tabs', 'my_agenda') == 'true') {
            $navigation['myagenda'] = $possible_tabs['myagenda'];
        } else {
            $menu_navigation['myagenda'] = $possible_tabs['myagenda'];
        }
    
        // Gradebook
        if (api_get_setting('gradebook_enable') == 'true') {
            if (api_get_setting('show_tabs', 'my_gradebook') == 'true') {
                $navigation['mygradebook'] = $possible_tabs['mygradebook'];
            } else{
                $menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
            }
        }
    
        // Reporting
        if (api_get_setting('show_tabs', 'reporting') == 'true') {
            if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
                $navigation['session_my_space'] = $possible_tabs['session_my_space'];
            } else {
                $navigation['session_my_space'] = $possible_tabs['session_my_progress'];
            }
        } else {
            if (api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
                $menu_navigation['session_my_space'] = $possible_tabs['session_my_space'];
            } else {
                $menu_navigation['session_my_space'] = $possible_tabs['session_my_progress'];
            }
        }
    
        // Social Networking
        if (api_get_setting('show_tabs', 'social') == 'true') {
            if (api_get_setting('allow_social_tool') == 'true') {
                $navigation['social'] = $possible_tabs['social'];
            }
        } else{
            $menu_navigation['social'] = $possible_tabs['social'];
        }
    
        // Dashboard
        if (api_get_setting('show_tabs', 'dashboard') == 'true') {
            if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
                $navigation['dashboard'] = $possible_tabs['dashboard'];
            }
        } else{
            $menu_navigation['dashboard'] = $possible_tabs['dashboard'];
        }
    
        // Administration
        if (api_is_platform_admin(true)) {
            if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
                $navigation['platform_admin'] = $possible_tabs['platform_admin'];
            } else {
                $menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
            }
        }
        
		// Reports
        if (!empty($possible_tabs['reports'])) {
            if (api_get_setting('show_tabs', 'reports') == 'true') {
                if ((api_is_platform_admin() || api_is_drh() || api_is_session_admin()) && Rights::hasRight('show_tabs:reports')) {
                    $navigation['reports'] = $possible_tabs['reports'];                
                }
            } else {
                $menu_navigation['reports'] = $possible_tabs['reports'];            
            }
        }

		// Custom tabs
		for ($i=1;$i<=3;$i++)
			if (api_get_setting('show_tabs', 'custom_tab_'.$i) == 'true') {
				$navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
			} else {
			    if (isset($possible_tabs['custom_tab_'.$i])) {
                    $menu_navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
                }
			}
    }
    return array('menu_navigation' => $menu_navigation, 'navigation' => $navigation, 'possible_tabs' => $possible_tabs);    
}

function return_menu() {
    $navigation         = return_navigation_array();        
    $navigation         = $navigation['navigation'];
   
    // Displaying the tabs
    
    $lang = ''; //el for "Edit Language"
    if (!empty($_SESSION['user_language_choice'])) {
        $lang = $_SESSION['user_language_choice'];
    } elseif (!empty($_SESSION['_user']['language'])) {
        $lang = $_SESSION['_user']['language'];
    } else {
        $lang = get_setting('platformLanguage');
    }
    
    //Preparing home folder for multiple urls
    
    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $url_info = api_get_access_url($access_url_id);
            $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
            $clean_url = replace_dangerous_char($url);
            $clean_url = str_replace('/', '-', $clean_url);
            $clean_url .= '/';
            $homep     = api_get_path(SYS_PATH).'home/'.$clean_url; //homep for Home Path               
            //we create the new dir for the new sites
            if (!is_dir($homep)) {
                mkdir($homep, api_get_permissions_for_new_directories());
            }
        }
    } else {    
        $homep = api_get_path(SYS_PATH).'home/';
    }
    
    $ext      = '.html';
    $menutabs = 'home_tabs';
    $home_top = '';
    
    if (is_file($homep.$menutabs.'_'.$lang.$ext) && is_readable($homep.$menutabs.'_'.$lang.$ext)) {
        $home_top = @(string)file_get_contents($homep.$menutabs.'_'.$lang.$ext);    
    } elseif (is_file($homep.$menutabs.$lang.$ext) && is_readable($homep.$menutabs.$lang.$ext)) {
        $home_top = @(string)file_get_contents($homep.$menutabs.$lang.$ext);
    } else {
        //$errorMsg = get_lang('HomePageFilesNotReadable');
    }
        
    $home_top = api_to_system_encoding($home_top, api_detect_encoding(strip_tags($home_top)));
   
    $open = str_replace('{rel_path}',api_get_path(REL_PATH), $home_top);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    
    $lis = '';
    $show_bar = false;
    
    if (!empty($open)) {
        if (strpos($open, 'show_menu') === false) {
            if (api_is_anonymous()) {
                $navigation[SECTION_CAMPUS]  = null;               
            }
        } else {
            $lis .= Display::tag('li', $open);    
        }
        $show_bar = true;
    }
    
    if (count($navigation) > 1 || !empty($lis)) {     
        $pre_lis = '';
        foreach ($navigation as $section => $navigation_info) {
            if (isset($GLOBALS['this_section'])) {
                $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
            } else {
                $current = '';
            }
            if (!empty($navigation_info['title'])) {
                $pre_lis .= '<li'.$current.'><a  href="'.$navigation_info['url'].'" target="_top"><span id="tab_active">'.$navigation_info['title'].'</span></a></li>';
            }
        }
        $lis = $pre_lis.$lis;
        $show_bar = true;
    }
        
    $menu = null;
    
    // Logout    
    if ($show_bar) {
        if (api_get_user_id() && !api_is_anonymous()) {
            $login = '';
            if (api_is_anonymous()) {
                $login = get_lang('Anonymous');
            } else {
                $user_info = api_get_user_info(api_get_user_id());                
            }            
            $logout_link = api_get_path(WEB_PATH).'index.php?logout=logout&uid='.api_get_user_id();
            
            $message_link  = null;
            
            if (api_get_setting('allow_message_tool') == 'true') {
                $message_link = '<a href="'.api_get_path(WEB_CODE_PATH).'messages/inbox.php">'.get_lang('Inbox').'</a>';
            }            
            
            if (api_get_setting('allow_social_tool')=='true') {
                $profile_url = '<a href="'.api_get_path(WEB_CODE_PATH).'social/home.php">'.get_lang('Profile').'</a>';
            } else {
                $profile_url = '<a href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php">'.get_lang('Profile').'</a>';
            }
            //start user section line with name, my course, my profile, scorm info, etc            
            $menu .= '<ul class="nav nav-pills pull-right">';
                //echo '<li><span>'.get_lang('LoggedInAsX').' '.$login.'</span></li>';
                $menu .= '<li class="dropdown">';                
                $menu .= '<a class="dropdown-toggle" data-toggle="dropdown" href="#"><img src="'.$user_info['avatar_small'].'"/> '.$user_info['complete_name'].'<b class="caret"></b></a>
                                <ul class="dropdown-menu">
                                    <li>
                                        '.$profile_url.'
                                        '.$message_link.'                                        
                                    </li>
                                </ul>';
                $menu .= '</li>';
                $menu .= '<li><a class="close" title="'.get_lang('Logout').'" href="'.$logout_link.'">&times;</a></li>'; 
            $menu .= '</ul>';    
        }      
        
        if (!empty($lis)) {
            $menu .= '<ul class="nav nav-pills">';
            $menu .= $lis;
            $menu .= '</ul>';
        }
    }    
    return $menu;
}

function return_breadcrumb($interbreadcrumb, $language_file, $nameTools) {  
	 
    $session_id     = api_get_session_id();
    $session_name   = api_get_session_name($session_id);
    $_course        = api_get_course_info();    
    
    /*  Plugins for banner section */
    $web_course_path = api_get_path(WEB_COURSE_PATH);
    
    /*
     * if the user is a coach he can see the users who are logged in its session
     */
    $navigation = array();
    // part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
    // hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
    $session_name = cut($session_name, MAX_LENGTH_BREADCRUMB);
    $my_session_name = is_null($session_name) ? '' : '&nbsp;('.$session_name.')';
    if (!empty($_course) && !isset($_GET['hide_course_breadcrumb'])) {
    	
        $navigation_item['url'] = $web_course_path . $_course['path'].'/index.php'.(!empty($session_id) ? '?id_session='.$session_id : '');
        
        $course_title = cut($_course['name'], MAX_LENGTH_BREADCRUMB);
        
        switch (api_get_setting('breadcrumbs_course_homepage')) {
            case 'get_lang':
                $navigation_item['title'] = Display::img(api_get_path(WEB_CSS_PATH).'home.png', get_lang('CourseHomepageLink')).' '.get_lang('CourseHomepageLink');
                break;
            case 'course_code':
                $navigation_item['title'] = Display::img(api_get_path(WEB_CSS_PATH).'home.png', $_course['official_code']).' '.$_course['official_code'];
                break;
            case 'session_name_and_course_title':
                $navigation_item['title'] = Display::img(api_get_path(WEB_CSS_PATH).'home.png', $_course['name'].$my_session_name).' '.$course_title.$my_session_name;
                break;
            default:
                if (api_get_setting('use_session_mode') == 'true' && api_get_session_id() != -1 ) { 
                    $navigation_item['title'] = Display::img(api_get_path(WEB_CSS_PATH).'home.png', $_course['name'].$my_session_name).' '.$course_title.$my_session_name;
                } else {
                    $navigation_item['title'] = Display::img(api_get_path(WEB_CSS_PATH).'home.png', $_course['name']).' '.$course_title;
                }
                break;
        }
        /*

         * @todo could be useful adding the My courses in the breadcrumb
        $navigation_item_my_courses['title'] = get_lang('MyCourses');
        $navigation_item_my_courses['url'] = api_get_path(WEB_PATH).'user_portal.php';        
        $navigation[] = $navigation_item_my_courses;
        */
        $navigation[] = $navigation_item;
    }
    
    // part 2: Interbreadcrumbs. If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb (which is the tool itself)
    if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {        
        foreach ($interbreadcrumb as $breadcrumb_step) {
            if ($breadcrumb_step['url'] != '#') {                
                $sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
                $navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
            } else {
                $navigation_item['url'] = '#';
            }                        
            $navigation_item['title'] = $breadcrumb_step['name'];
            // titles for shared folders
            if ($breadcrumb_step['name'] == 'shared_folder') {
                $navigation_item['title'] = get_lang('UserFolders');
            } elseif(strstr($breadcrumb_step['name'], 'shared_folder_session_')) {          
                $navigation_item['title'] = get_lang('UserFolders');
            } elseif(strstr($breadcrumb_step['name'], 'sf_user_')) {            
                $userinfo = Database::get_user_info_from_id(substr($breadcrumb_step['name'], 8));
                $navigation_item['title'] = api_get_person_name($userinfo['firstname'], $userinfo['lastname']); 
            } elseif($breadcrumb_step['name'] == 'chat_files') {            
                $navigation_item['title'] = get_lang('ChatFiles');
            } elseif($breadcrumb_step['name'] == 'images') {            
                $navigation_item['title'] = get_lang('Images');
            } elseif($breadcrumb_step['name'] == 'video') {         
                $navigation_item['title'] = get_lang('Video');
            } elseif($breadcrumb_step['name'] == 'audio') {         
                $navigation_item['title'] = get_lang('Audio');
            } elseif($breadcrumb_step['name'] == 'flash') {         
                $navigation_item['title'] = get_lang('Flash');
            } elseif($breadcrumb_step['name'] == 'gallery') {           
                $navigation_item['title'] = get_lang('Gallery');
            }
            //Fixes breadcrumb title now we applied the Security::remove_XSS and we cut the string depending of the MAX_LENGTH_BREADCRUMB value
            
            $navigation_item['title'] = cut($navigation_item['title'], MAX_LENGTH_BREADCRUMB);            
            $navigation_item['title'] = Security::remove_XSS($navigation_item['title']);
            $navigation[] = $navigation_item;
        }
    }
    
    
    // part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
    // is the same as the first part of the breadcrumbs (see part 1)
    if (isset($nameTools) && $language_file != 'course_home') { // TODO: This condition $language_file != 'course_home' might bring surprises.    	
        $navigation_item['url'] = '#';
        $navigation_item['title'] = $nameTools;
        $navigation[] = $navigation_item;
    }
    
    $final_navigation = array();
    $counter = 0;
    
    foreach ($navigation as $index => $navigation_info) {
        if (!empty($navigation_info['title'])) {
                     
            if ($navigation_info['url'] == '#') {
                $final_navigation[$index] = '<span>'.$navigation_info['title'].'</span>';                
            } else {
                $final_navigation[$index] = '<a href="'.$navigation_info['url'].'" class="" target="_top"><span>'.$navigation_info['title'].'</span></a>';
            }
            $counter++;
        }
    }
    
    $html = '';

    if (!empty($final_navigation)) {        
        $lis = '';
        $i = 0;
        //$home_link = Display::url(Display::img(api_get_path(WEB_CSS_PATH).'home.png', get_lang('Homepage'), array('align'=>'middle')), api_get_path(WEB_PATH), array('class'=>'home'));
       
        //$lis.= Display::tag('li', Display::url(get_lang('Homepage').'<span class="divider">/</span>', api_get_path(WEB_PATH)));      
        $final_navigation_count = count($final_navigation);
        
        if (!empty($final_navigation)) {
            
           // $home_link.= '<span class="divider">/</span>';
            
            $lis.= Display::tag('li', $home_link);
            foreach ($final_navigation as $bread) {  
                $bread_check = trim(strip_tags($bread));                
                if (!empty($bread_check)) {
                    if ($final_navigation_count-1 > $i) {
                        $bread .= '<span class="divider">/</span>';
                    }
                    
                    $lis.= Display::tag('li', $bread);
                    $i++;
                }
            }
        } else {
            $lis.= Display::tag('li', $home_link);    
        }
        $html .= Display::tag('ul', $lis, array('class'=>'breadcrumb'));        
    }
    return $html ;
}