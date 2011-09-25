<?php
/* For licensing terms, see /license.txt */
/**
 * Code
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

	// Campus Homepage
	$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
	$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

	// My Courses
	if(api_get_setting('use_session_mode')=='true') {
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
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php?view=month&'.(!empty($_course['path']) ? 'coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if(api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
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
        $total_invitations = (!empty($total_invitations)?' ('.$total_invitations.')':'');        
        
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
		//$navigation['platform_admin']['url'] = $rootAdminWeb;
		$navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}
	return $navigation;
}

function show_header_1($language_file, $nameTools) {
    global $noPHP_SELF;
    $_course = api_get_course_info();        
    echo '<div id="header1">';
        echo '<div id="top_corner"></div>';        
            $logo = api_get_path(SYS_CODE_PATH).'css/'.api_get_visual_theme().'/images/header-logo.png';            
            $site_name = api_get_setting('siteName');
            if (file_exists($logo)) {
                $site_name = api_get_setting('Institution').' - '.$site_name;
                echo '<div id="logo">';
                    $image_url = api_get_path(WEB_CSS_PATH).api_get_visual_theme().'/images/header-logo.png';           
                    $logo = Display::img($image_url, $site_name, array('title'=>$site_name));
                    echo Display::url($logo, api_get_path(WEB_PATH).'index.php');
                echo '</div>';
            } else {         
                echo '<a href="'.api_get_path(WEB_PATH).'index.php" target="_top">'.$site_name.'</a>';                    
                $iurl  = api_get_setting('InstitutionUrl');
                $iname = api_get_setting('Institution');
                
                if (!empty($iname)) {
                   echo '-&nbsp;<a href="'.$iurl.'" target="_top">'.$iname.'</a>';
                }           
                // External link section a.k.a Department - Department URL          
                if (isset($_course['extLink']) && $_course['extLink']['name'] != '') {
                    echo '<span class="extLinkSeparator"> - </span>';
                    if ($_course['extLink']['url'] != '') {
                        echo '<a class="extLink" href="'.$_course['extLink']['url'].'" target="_top">';
                        echo $_course['extLink']['name'];
                        echo '</a>';
                    } else {
                        echo $_course['extLink']['name'];
                    }
                }
            }        
        
    /*  Course title section */
    
    if (!empty($_cid) and $_cid != -1 and isset($_course)) {
        //Put the name of the course in the header  
        echo '<div id="my_courses">';
        /* <div id="my_courses"><a href="<?php echo api_get_path(WEB_COURSE_PATH).$_course['path']; ?>/index.php" target="_top">&nbsp;  
        echo $_course['name'].' ';
        
        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
            echo $_course['official_code'];
        }
        
        if (api_get_setting('use_session_mode') == 'true' && isset($_SESSION['session_name'])) {
            echo '&nbsp;('.$_SESSION['session_name'].')&nbsp;';
        }
        
        if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
            echo ' - ';
        }   
        if (api_get_setting('display_teacher_in_courselist') == 'true') {
            //This is still necessary? There is the course teacher in the footer
            echo stripslashes($_course['titular']);
        }   
        echo '</a>';*/        
        echo '</div>';        
    } elseif (isset($nameTools) && $language_file != 'course_home') {
        //Put the name of the user-tools in the header
        if (!isset($_user['user_id'])) {
            //echo '<div id="my_courses"></div>';
        } elseif (!$noPHP_SELF) {
            echo '<div id="my_courses"><a href="'.api_get_self().'?'.api_get_cidreq(), '" target="_top">'.$nameTools.'</a></div>';
        } else {
            echo '<div id="my_courses">'.$nameTools.'</div>';
        }   
    } else {        
        //echo '<div id="my_courses"></div>';
    }
    
    echo '<div id="plugin-header">';
    api_plugin('header');
    echo '</div>';
    
    //Don't let the header disappear if there's nothing on the left
    //echo '<div class="clear">&nbsp;</div>';
    echo '</div>';
}

function show_header_2() {
    $_course    = api_get_course_info(); 
    $course_id  = api_get_course_id();
    $user_id    = api_get_user_id();
    
    echo '<div id="header2">';
    echo '<div id="Header2Right">';
    
    echo '<ul>';
    
    if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting('showonline', 'users') == 'true' AND $user_id) OR (api_get_setting('showonline', 'course') == 'true' AND $user_id AND $course_id)) {
        $number = who_is_online_count(api_get_setting('time_limit_whosonline'));            
        
        $number_online_in_course = 0;
        if(!empty($_course['id'])) {
            $number_online_in_course = who_is_online_in_this_course_count($user_id, api_get_setting('time_limit_whosonline'), $_course['id']);
        }       
        echo '<li>';
        
        // Display the who's online of the platform
        if ($number) {
            if ((api_get_setting('showonline', 'world') == 'true' AND !$user_id) OR (api_get_setting('showonline', 'users') == 'true' AND $user_id)) {
                echo '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_top" title="'.get_lang('UsersOnline').'" ><img width="13px" src="'.api_get_path(WEB_IMG_PATH).'members.gif" title="'.get_lang('UsersOnline').'"> '.$number.'</a></li>';
            }
        }
    
        // Display the who's online for the course
        if ($number_online_in_course) {
            if (is_array($_course) AND api_get_setting('showonline', 'course') == 'true' AND isset($_course['sysCode'])) {
                echo '<li>| <a href="'.api_get_path(WEB_PATH).'whoisonline.php?cidReq='.$_course['sysCode'].'" target="_top">'.Display::return_icon('course.gif', get_lang('UsersOnline').' '.get_lang('InThisCourse'), array('width'=>'13px')).' '.$number_online_in_course.' </a></li>';
            }
        }
        
        // Display the who's online for the session 
        if (api_get_setting('use_session_mode') == 'true' && isset($user_id) && api_get_session_id() != 0) {
            //echo '<li><a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php?id_coach='.$user_id.'&amp;referer='.urlencode($_SERVER['REQUEST_URI']).'" target="_top">'.get_lang('UsersConnectedToMySessions').'</a></li>';        
            echo '<li>| <a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php?id_coach='.$user_id.'&amp;referer='.urlencode($_SERVER['REQUEST_URI']).'" target="_top">'.Display::return_icon('session.png', get_lang('UsersConnectedToMySessions'), array('width'=>'13px')).' </a></li>';
        }        
        echo '</li>';
    }
    
    if ($user_id && isset($course_id)) {
        if ((api_is_course_admin() || api_is_platform_admin()) && api_get_setting('student_view_enabled') == 'true') {
            echo '<li>&nbsp;|&nbsp;';
            api_display_tool_view_option();
            echo '</li>';
        }
    }
    
    if (api_get_setting('accessibility_font_resize') == 'true') {
        echo '<li class="resize_font">';
        echo '<span class="decrease_font" title="'.get_lang('DecreaseFontSize').'">A</span> <span class="reset_font" title="'.get_lang('ResetFontSize').'">A</span> <span class="increase_font" title="'.get_lang('IncreaseFontSize').'">A</span>';
        echo '</li>';
    }   
    echo '</ul>'; 
    echo '</div>';
    echo '</div>';
}

function show_header_3() {
    
    $navigation = $menu_navigation = array();
    $possible_tabs = get_tabs();
        
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
				if (api_get_setting('show_tabs', 'reports') == 'true') {
					if ((api_is_platform_admin() || api_is_drh() || api_is_session_admin()) && Rights::hasRight('show_tabs:reports')) {
						$navigation['reports'] = $possible_tabs['reports'];
					}
				} else{
					$menu_navigation['reports'] = $possible_tabs['reports'];
				}

				// Custom tabs
				for ($i=1;$i<=3;$i++)
					if (api_get_setting('show_tabs', 'custom_tab_'.$i) == 'true') {
						$navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
					} else{
						$menu_navigation['custom_tab_'.$i] = $possible_tabs['custom_tab_'.$i];
					}
    }
    
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
        $errorMsg = get_lang('HomePageFilesNotReadable');
    }
        
    $home_top = api_to_system_encoding($home_top, api_detect_encoding(strip_tags($home_top)));
    
    //if (api_get_self() != '/main/admin/configure_homepage.php') {        
    $open = str_replace('{rel_path}',api_get_path(REL_PATH), $home_top);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    
    $lis = '';
    if (!empty($open)) {
        $lis .= Display::tag('li', $open);
        $show_bar = true;
    }
    //} else {     
    //This code was moved in the admin/configure_homepage.php file  
        /*  
        $home_menu = '';
        if (file_exists($homep.$menutabs.'_'.$lang.$ext)) {
            $home_menu = @file($homep.$menutabs.'_'.$lang.$ext);
        } else {
            $home_menu = @file($homep.$menutabs.$ext);
        }
        if (empty($home_menu)) {
            $home_menu = array();
        }
        if (!empty($home_menu)) {
            $home_menu = implode("\n", $home_menu);
            $home_menu = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
            $home_menu = explode("\n", $home_menu);
        }
        $tab_counter = 0;
        if (!empty($home_menu)) {
            $show_bar = true;    
        }        
        foreach ($home_menu as $enreg) {
            $enreg = trim($enreg);
            if (!empty($enreg)) {
                $edit_link = '<a href="'.api_get_self().'?action=edit_tabs&amp;link_index='.$tab_counter.'" ><span>'.Display::return_icon('edit.gif', get_lang('Edit')).'</span></a>';
                $delete_link = '<a href="'.api_get_self().'?action=delete_tabs&amp;link_index='.$tab_counter.'"  onclick="javascript: if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;"><span>'.Display::return_icon('delete.gif', get_lang('Delete')).'</span></a>';
                $tab_string = str_replace(array('href="'.api_get_path(WEB_PATH).'index.php?include=', '</li>'), array('href="'.api_get_path(WEB_CODE_PATH).'admin/'.basename(api_get_self()).'?action=open_link&link=', $edit_link.$delete_link.'</li>'), $enreg);                
                $lis .= $tab_string;
                $tab_counter++;
            }
        }
        $lis .= '<li id="insert-link"><a href="'.api_get_self().'?action=insert_tabs" style="padding-right:0px;"><span>'. Display::return_icon('addd.gif', get_lang('InsertLink'), array('style' => 'vertical-align:middle')).' '.get_lang('InsertLink').'</span></a></li>';
        */
    //}
    
    if (count($navigation) > 1 || !empty($lis)) {        
        $pre_lis = '';
        foreach ($navigation as $section => $navigation_info) {
            if (isset($GLOBALS['this_section'])) {
                $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
            } else {
                $current = '';
            }
            $pre_lis .= '<li'.$current.'><a  href="'.$navigation_info['url'].'" target="_top"><span id="tab_active">'.$navigation_info['title'].'</span></a></li>';
        }
        $lis = $pre_lis.$lis;
        $show_bar = true;
    }
    
    
    // Logout    
    if ($show_bar) {
        echo '<div id="header3">';
    
        if (api_get_user_id()) {
            $login = '';
            if (api_is_anonymous()) {
                $login = get_lang('Anonymous');
            } else {
                $uinfo = api_get_user_info(api_get_user_id());
                $login = $uinfo['username'];
            }
            
            //start user section line with name, my course, my profile, scorm info, etc            
            echo '<ul id="logout">';
                //echo '<li><span>'.get_lang('LoggedInAsX').' '.$login.'</span></li>';
                //echo '<li><a href="'.api_get_path(WEB_PATH).'main/auth/profile.php" target="_top"><span>'.get_lang('Profile').'</span></a></li>';
                echo '<li><a href="'.api_get_path(WEB_PATH).'index.php?logout=logout&uid='.api_get_user_id().'" target="_top"><span>'.get_lang('Logout').' ('.$login.')</span></a></li>';
            echo '</ul>';    
        }   
      
        echo '<ul>';
        echo $lis;
        echo '</ul>';
        echo '</div>';
    }    
    return $menu_navigation;
}

//Header 4
function show_header_4($interbreadcrumb, $language_file, $nameTools) {  
	 
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
    $my_session_name = is_null($session_name) ? '' : '&nbsp;('.$session_name.')';
    if (!empty($_course) && !isset($_GET['hide_course_breadcrumb'])) {
    	
        $navigation_item['url'] = $web_course_path . $_course['path'].'/index.php'.(!empty($session_id) ? '?id_session='.$session_id : ''); 
        switch (api_get_setting('breadcrumbs_course_homepage')) {
            case 'get_lang':
                $navigation_item['title'] = get_lang('CourseHomepageLink');
                break;
            case 'course_code':
                $navigation_item['title'] = $_course['official_code'];
                break;
            case 'session_name_and_course_title':
                $navigation_item['title'] = $_course['name'].$my_session_name;
                break;
            default:
                if (api_get_setting('use_session_mode') == 'true' && api_get_session_id() != -1 ) { 
                    $navigation_item['title'] = $_course['name'].$my_session_name;
                } else {
                    $navigation_item['title'] = $_course['name'];
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
            if (api_strlen($navigation_item['title']) > MAX_LENGTH_BREADCRUMB) {
            	$navigation_item['title'] = api_substr($navigation_item['title'], 0, MAX_LENGTH_BREADCRUMB).' ...';
            }
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
        $html .= '<div id="header4">';
        $lis = '';
        $i = 0;
        $lis.= Display::tag('li', Display::url(Display::img(api_get_path(WEB_CSS_PATH).'home.png', get_lang('Homepage'), array('align'=>'middle')), api_get_path(WEB_PATH), array('class'=>'home')));        
        foreach ($final_navigation as $bread) {       
            $lis.= Display::tag('li', $bread);
            $i++;
        }
        $html .= Display::tag('ul',$lis, array('class'=>'bread'));
        $html .= '</div>';        
    } else {
        $html .= '<div id="header4">';
        $html .= '</div>';
    } 
    $html .= '<div class="clear"></div>';
    
    return $html ;
}


function load_navigation_menu() {
    
}
