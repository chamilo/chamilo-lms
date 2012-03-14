<?php
/* For licensing terms, see /license.txt */
/**
	@author Bart Mollet
	@author Julio Montoya <gugli100@gmail.com> BeezNest 2011
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';            

global $_configuration;

$current_access_url_id = api_get_current_access_url_id();

// Blocks the possibility to delete a user
$delete_user_available = true;
if (isset($_configuration['deny_delete_users']) &&  $_configuration['deny_delete_users']) {
	$delete_user_available = false;
}
$url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=get_user_courses';
        
$htmlHeadXtra[] = '<script type="text/javascript">
function load_course_list (div_course,my_user_id) {
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
            $("div#"+div_course).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.$url.'",
		data: "user_id="+my_user_id,
		success: function(datos) {
			$("div#"+div_course).html(datos);
			$("div#div_"+my_user_id).attr("class","blackboard_show");
			$("div#div_"+my_user_id).attr("style","");
		}
	});
}

function active_user(element_div) {
	id_image=$(element_div).attr("id");
	image_clicked=$(element_div).attr("src");
	image_clicked_info = image_clicked.split("/");
	image_real_clicked = image_clicked_info[image_clicked_info.length-1];
	var status = 1;
	if (image_real_clicked == "accept.png") {
		status = 0;
	}
	user_id=id_image.split("_");
	ident="#img_"+user_id[1];
	if (confirm("'.get_lang('AreYouSureToEditTheUserStatus', '').'")) {
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
				$(ident).attr("src","'.api_get_path(WEB_IMG_PATH).'loading1.gif'.'"); }, //candy eye stuff
			type: "GET",
			url: "'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=active_user",
			data: "user_id="+user_id[1]+"&status="+status,
			success: function(data) {
				if (data == 1) {
					$(ident).attr("src","'.api_get_path(WEB_IMG_PATH).'icons/16/accept.png'.'");
					$(ident).attr("title","'.get_lang('Lock').'");
				} 
                if (data == 0) {
					$(ident).attr("src","'.api_get_path(WEB_IMG_PATH).'icons/16/error.png'.'");
					$(ident).attr("title","'.get_lang('Unlock').'");
				}
                if (data == -1) {
                    $(ident).attr("src","'.api_get_path(WEB_IMG_PATH).'icons/16/warning.png'.'");
					$(ident).attr("title","'.get_lang('ActionNotAllowed').'");
                }
			}
		});
	}
}

function clear_course_list (div_course) {
	$("div#"+div_course).html("&nbsp;");
	$("div#"+div_course).hide("");
}

function display_advanced_search_form () {
        if ($("#advanced_search_form").css("display") == "none") {
                $("#advanced_search_form").css("display","block");
                $("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedSearch').'\');
        } else {
                $("#advanced_search_form").css("display","none");
                $("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedSearch').'\');
        }
}

$(document).ready(function() {

    var select_val = $("#input_select_extra_data").val();
    if ( document.getElementById(\'extra_data_text\')) {
    
        if (select_val != 0) {
            document.getElementById(\'extra_data_text\').style.display="block";
            if (document.getElementById(\'input_extra_text\')) 
                document.getElementById(\'input_extra_text\').value = "";
        } else {
            document.getElementById(\'extra_data_text\').style.display="none";
        }
    }
    
    
    $(".agenda_opener").live("click", function() {
        var url = this.href;
        var dialog = $("#dialog");
                
        if ($("#dialog").length == 0) {
            dialog = $(\'<div id="dialog" style="display:hidden"></div> \').appendTo(\'body\');
        }     
        // load remote content
        dialog.load(
                url,
                {},
                function(responseText, textStatus, XMLHttpRequest) {
                    dialog.dialog({width:720, height:550, modal:true});
                }
            );
        //prevent the browser to follow the link
        return false;
    });    
});

//Load user calendar
function load_calendar(user_id, month, year) {  
 	var url = "'.api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?a=get_user_agenda&user_id=" +user_id + "&month="+month+"&year="+year;
	$("#dialog").load( url    	 	
	);    	
}
</script>';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

/**
*	Make sure this function is protected because it does NOT check password!
*
*	This function defines globals.
*   @param  int     User ID
*   @return bool    False on failure, redirection on success
*	@author Evie Embrechts
*   @author Yannick Warnier <yannick.warnier@dokeos.com>
*/
function login_user($user_id) {    
    $user_id = intval($user_id);
    
    if (empty($user_id)) {
        return false;
    }
    if ($user_id != strval(intval($user_id))) {
    	return false;
    }
    
    //Only superadmins can login to admin accounts
    if (!api_global_admin_can_edit_admin($user_id)) {
        return false;
    }    

    //Load $_user to be sure we clean it before logging in
	global $uidReset, $loginFailed, $_configuration, $_user;

	$main_user_table      = Database::get_main_table(TABLE_MAIN_USER);
	$main_admin_table     = Database::get_main_table(TABLE_MAIN_ADMIN);
	$track_e_login_table  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

	unset($_user['user_id']); // uid not in session ? prevent any hacking

    $user_info = api_get_user_info($user_id);

    // check if the user is allowed to 'login_as'
    $can_login_as = (api_is_platform_admin() OR (api_is_session_admin() && $user_info['status'] == 5 ));
    if (!$can_login_as) { return false; }

	$firstname  = $user_info['firstname'];
	$lastname   = $user_info['lastname'];
	$user_id    = $user_info['user_id'];

	//$message = "Attempting to login as ".api_get_person_name($firstname, $lastname)." (id ".$user_id.")";
	if (api_is_western_name_order()) {
		$message = sprintf(get_lang('AttemptingToLoginAs'),$firstname,$lastname,$user_id);
	} else {
		$message = sprintf(get_lang('AttemptingToLoginAs'), $lastname, $firstname, $user_id);
	}

	$loginFailed = false;
	$uidReset = false;

	if ($user_id) { // a uid is given (log in succeeded)

		$sql_query = "SELECT user.*, a.user_id is_admin,
			UNIX_TIMESTAMP(login.login_date) login_date
			FROM $main_user_table
			LEFT JOIN $main_admin_table a
			ON user.user_id = a.user_id
			LEFT JOIN $track_e_login_table login
			ON user.user_id = login.login_user_id
			WHERE user.user_id = '".$user_id."'
			ORDER BY login.login_date DESC LIMIT 1";		

		$sql_result = Database::query($sql_query);


		if (Database::num_rows($sql_result) > 0) {
			// Extracting the user data

			$user_data = Database::fetch_array($sql_result);

            //Delog the current user

			LoginDelete($_SESSION["_user"]["user_id"]);

			// Cleaning session variables
			unset($_SESSION['_user']);
			unset($_SESSION['is_platformAdmin']);
			unset($_SESSION['is_allowedCreateCourse']);
			unset($_SESSION['_uid']);


			$_user['firstName'] 	= $user_data['firstname'];
			$_user['lastName'] 		= $user_data['lastname'];
			$_user['mail'] 			= $user_data['email'];
			$_user['lastLogin'] 	= $user_data['login_date'];
			$_user['official_code'] = $user_data['official_code'];
			$_user['picture_uri'] 	= $user_data['picture_uri'];
			$_user['user_id']		= $user_data['user_id'];
            $_user['status']        = $user_data['status'];

			$is_platformAdmin = (bool) (!is_null($user_data['is_admin']));
			$is_allowedCreateCourse = (bool) ($user_data['status'] == 1);

			// Filling session variables with new data
			$_SESSION['_uid']                   = $user_id;
			$_SESSION['_user']                  = $_user;
			$_SESSION['is_platformAdmin']       = $is_platformAdmin;
			$_SESSION['is_allowedCreateCourse'] = $is_allowedCreateCourse;
			$_SESSION['login_as']               = true; // will be useful later to know if the user is actually an admin or not (example reporting)s

			$target_url = api_get_path(WEB_PATH)."user_portal.php";
			//$message .= "<br/>Login successful. Go to <a href=\"$target_url\">$target_url</a>";
			$message .= '<br />'.sprintf(get_lang('LoginSuccessfulGoToX'),'<a href="'.$target_url.'">'.$target_url.'</a>');
			Display :: display_header(get_lang('UserList'));
			Display :: display_normal_message($message,false);
			Display :: display_footer();
			exit;
		} else {
			exit ("<br />WARNING UNDEFINED UID !! ");
		}
	}
}
/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_users() {
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT COUNT(u.user_id) AS total_number_of_items FROM $user_table u";

	// adding the filter to see the user's only of the current access_url
    if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

    if (isset($_GET['keyword_extra_data'])) {
        $keyword_extra_data = Database::escape_string($_GET['keyword_extra_data']);
        if (!empty($keyword_extra_data)) {
            $extra_info = UserManager::get_extra_field_information_by_name($keyword_extra_data);
            $field_id = $extra_info['id'];
            $sql.= " INNER JOIN user_field_values ufv ON u.user_id=ufv.user_id AND ufv.field_id=$field_id ";
        }
    }

	if ( isset ($_GET['keyword'])) {
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR concat(u.firstname,' ',u.lastname) LIKE '%".$keyword."%'  OR concat(u.lastname,' ',u.firstname) LIKE '%".$keyword."%' OR u.username LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%') ";
	} elseif (isset ($_GET['keyword_firstname'])) {
		$admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
		$keyword_firstname = Database::escape_string($_GET['keyword_firstname']);
		$keyword_lastname = Database::escape_string($_GET['keyword_lastname']);
		$keyword_email = Database::escape_string($_GET['keyword_email']);
		$keyword_officialcode = Database::escape_string($_GET['keyword_officialcode']);
		$keyword_username = Database::escape_string($_GET['keyword_username']);
		$keyword_status = Database::escape_string($_GET['keyword_status']);
		$query_admin_table = '';
		$keyword_admin = '';
		if ($keyword_status == SESSIONADMIN) {
			$keyword_status = '%';
			$query_admin_table = " , $admin_table a ";
			$keyword_admin = ' AND a.user_id = u.user_id ';
		}

        $keyword_extra_value = '';
        if (isset($_GET['keyword_extra_data'])) {
            if (!empty($_GET['keyword_extra_data']) && !empty($_GET['keyword_extra_data_text'])) {
                $keyword_extra_data_text = Database::escape_string($_GET['keyword_extra_data_text']);
                $keyword_extra_value = " AND ufv.field_value LIKE '%".trim($keyword_extra_data_text)."%' ";
            }
        }

		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table .
				" WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'" .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin.$keyword_extra_value;
		if($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

	$res = Database::query($sql);
	$obj = Database::fetch_object($res);
	return $obj->total_number_of_items;
}
/**
 * Get the users to display on the current page (fill the sortable-table)
 * @param   int     offset of first user to recover
 * @param   int     Number of users to get
 * @param   int     Column to sort on
 * @param   string  Order (ASC,DESC)
 * @see SortableTable#get_table_data($from)
 */
function get_user_data($from, $number_of_items, $column, $direction) {
	global $origin;

	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
	$sql = "SELECT
                 u.user_id				AS col0,
                 u.official_code		AS col2,
				 ".(api_is_western_name_order()
                 ? "u.firstname 			AS col3,
                 u.lastname 			AS col4,"
                 : "u.lastname 			AS col3,
                 u.firstname 			AS col4,")."
                 u.username				AS col5,
                 u.email				AS col6,
                 u.status				AS col7,
                 u.active				AS col8,
                 u.user_id				AS col9 ".
                 ", u.expiration_date      AS exp ".
            " FROM $user_table u ";

    // adding the filter to see the user's only of the current access_url
    if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

    if (isset($_GET['keyword_extra_data'])) {
        $keyword_extra_data = Database::escape_string($_GET['keyword_extra_data']);
        if (!empty($keyword_extra_data)) {
            $extra_info = UserManager::get_extra_field_information_by_name($keyword_extra_data);
            $field_id = $extra_info['id'];
            $sql.= " INNER JOIN user_field_values ufv ON u.user_id=ufv.user_id AND ufv.field_id=$field_id ";
        }
    }

	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%' OR concat(u.firstname,' ',u.lastname) LIKE '%".$keyword."%' OR concat(u.lastname,' ',u.firstname) LIKE '%".$keyword."%' OR u.username LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%' )";
	} elseif (isset ($_GET['keyword_firstname'])) {
		$keyword_firstname = Database::escape_string($_GET['keyword_firstname']);
		$keyword_lastname = Database::escape_string($_GET['keyword_lastname']);
		$keyword_email = Database::escape_string($_GET['keyword_email']);
		$keyword_officialcode = Database::escape_string($_GET['keyword_officialcode']);
		$keyword_username = Database::escape_string($_GET['keyword_username']);
		$keyword_status = Database::escape_string($_GET['keyword_status']);

		$query_admin_table = '';
		$keyword_admin = '';

		if ($keyword_status == SESSIONADMIN) {
			$keyword_status = '%';
			$query_admin_table = " , $admin_table a ";
			$keyword_admin = ' AND a.user_id = u.user_id ';
		}

		$keyword_extra_value = '';
		
        if (isset($_GET['keyword_extra_data'])) {
        	if (!empty($_GET['keyword_extra_data']) && !empty($_GET['keyword_extra_data_text'])) {
            	$keyword_extra_data_text = Database::escape_string($_GET['keyword_extra_data_text']);
                $keyword_extra_value = " AND ufv.field_value LIKE '%".trim($keyword_extra_data_text)."%' ";
			}
		}

		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table." WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'    " .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin.$keyword_extra_value;

		if ($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

    if (!in_array($direction, array('ASC','DESC'))) {
    	$direction = 'ASC';
    }
    $column = intval($column);
    $from 	= intval($from);
    $number_of_items = intval($number_of_items);

	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";

	$res = Database::query($sql);

	$users = array ();
    $t = time();
	while ($user = Database::fetch_row($res)) {
		$image_path 	= UserManager::get_user_picture_path_by_id($user[0], 'web', false, true);
		$user_profile 	= UserManager::get_picture_user($user[0], $image_path['file'], 22, USER_IMAGE_SIZE_SMALL, ' width="22" height="22" ');
		if (!api_is_anonymous()) {
			$photo = '<center><a href="'.api_get_path(WEB_PATH).'whoisonline.php?origin=user_list&id='.$user[0].'" title="'.get_lang('Info').'"><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($user[2],$user[3]).'"  title="'.api_get_person_name($user[2], $user[3]).'" /></a></center>';
		} else {
			$photo = '<center><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($user[2], $user[3]).'" title="'.api_get_person_name($user[2], $user[3]).'" /></center>';
		}
        if ($user[7] == 1 && $user[9] != '0000-00-00 00:00:00') {
            // check expiration date
            $expiration_time = convert_sql_date($user[9]);
            // if expiration date is passed, store a special value for active field
            if ($expiration_time < $t) {
        	   $user[7] = '-1';
            }
        }
        // forget about the expiration date field
        $users[] = array($user[0],$photo,$user[1],$user[2],$user[3],$user[4],$user[5],$user[6],$user[7],$user[8]);        
	}
	return $users;
}
/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email) {
	return Display :: encrypted_mailto_link($email, $email);
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function user_filter($name, $params, $row) {
	return '<a href="'.api_get_path(WEB_PATH).'whoisonline.php?origin=user_list&id='.$row[0].'">'.$name.'</a>';
}

/**
 * Build the modify-column of the table
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($user_id, $url_params, $row) {
	global $charset, $_user, $_admins_list, $delete_user_available;
    
	$is_admin   = in_array($user_id,$_admins_list);
	$statusname = api_get_status_langvars();
	$user_is_anonymous = false;
	if ($row['7'] == $statusname[ANONYMOUS]) {
		$user_is_anonymous =true;
	}
	$result = '';
	if (!$user_is_anonymous) {
		$result .= '<a href="javascript:void(0)" onclick="load_course_list(\'div_'.$user_id.'\','.$user_id.')" >
					<img onmouseout="clear_course_list (\'div_'.$user_id.'\')" src="../img/course.gif" title="'.get_lang('Courses').'" alt="'.get_lang('Courses').'"/>
					<div class="blackboard_hide" id="div_'.$user_id.'">&nbsp;&nbsp;</div>
					</a>&nbsp;&nbsp;';
	} else {
		$result .= Display::return_icon('course_na.gif',get_lang('Courses')).'&nbsp;&nbsp;';
	}

	if (api_is_platform_admin()) {
		if (!$user_is_anonymous) {
			$result .= '<a href="user_information.php?user_id='.$user_id.'">'.Display::return_icon('synthese_view.gif', get_lang('Info')).'</a>&nbsp;&nbsp;';
		} else {
			$result .= Display::return_icon('synthese_view_na.gif', get_lang('Info')).'&nbsp;&nbsp;';
		}
	}

    //only allow platform admins to login_as, or session admins only for students (not teachers nor other admins)
    if (api_is_platform_admin() || (api_is_session_admin() && $row['7'] == $statusname[STUDENT])) {
    	if (!$user_is_anonymous) {
            if (api_global_admin_can_edit_admin($user_id)) {
                $result .= '<a href="user_list.php?action=login_as&amp;user_id='.$user_id.'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>&nbsp;&nbsp;';
            } else {
                $result .= Display::return_icon('login_as_na.gif', get_lang('LoginAs')).'&nbsp;&nbsp;';                
            }
            //$result .= '<a href="user_list.php?action=login_as&amp;user_id='.$user_id.'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>&nbsp;&nbsp;';
    	} else {
    		$result .= Display::return_icon('login_as_na.gif', get_lang('LoginAs')).'&nbsp;&nbsp;';
    	}
    } else {
    	$result .= Display::return_icon('login_as_na.gif', get_lang('LoginAs')).'&nbsp;&nbsp;';
    }
	if ($row['7'] != $statusname[STUDENT]) {
		$result .= Display::return_icon('statistics_na.gif', get_lang('Reporting')).'&nbsp;&nbsp;';
	} else {
		$result .= '<a href="../mySpace/myStudents.php?student='.$user_id.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;&nbsp;';
	}

	if (api_is_platform_admin()) {
		if (!$user_is_anonymous && api_global_admin_can_edit_admin($user_id)) {            
            $result .= '<a href="user_edit.php?user_id='.$user_id.'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>&nbsp;';        
		} else {
            $result .= Display::return_icon('edit_na.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>&nbsp;';
		}
	}
	if ($is_admin) {
		$result .= Display::return_icon('admin_star.png', get_lang('IsAdministrator'),array('width'=> ICON_SIZE_SMALL, 'heigth'=> ICON_SIZE_SMALL));
	} else {
		$result .= Display::return_icon('admin_star_na.png', get_lang('IsNotAdministrator'));
	}
	

	// actions for assigning sessions, courses or users
	if (api_is_session_admin()) {
		/*if ($row[0] == api_get_user_id()) {
			$result .= '<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.Display::return_icon('view_more_stats.gif', get_lang('AssignSessions')).'</a>&nbsp;&nbsp;';
		}*/
	} else {
		if ($row['7'] == $statusname[DRH] || UserManager::is_admin($row[0])) {
			$result .= '<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.Display::return_icon('user_subscribe_course.png', get_lang('AssignUsers'),'',ICON_SIZE_SMALL).'</a>';
			$result .= '<a href="dashboard_add_courses_to_user.php?user='.$user_id.'">'.Display::return_icon('course_add.gif', get_lang('AssignCourses')).'</a>&nbsp;&nbsp;';
			$result .= '<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.Display::return_icon('view_more_stats.gif', get_lang('AssignSessions')).'</a>&nbsp;&nbsp;';
		} else if ($row['7'] == $statusname[SESSIONADMIN]) {
			$result .= '<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.Display::return_icon('view_more_stats.gif', get_lang('AssignSessions')).'</a>&nbsp;&nbsp;';
		}
	}
	
    if (api_is_platform_admin()) {        
        $result .= ' <a href="'.api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?a=get_user_agenda&amp;user_id='.$user_id.'" class="agenda_opener">'.Display::return_icon('month.png', get_lang('FreeBusyCalendar'), array(), ICON_SIZE_SMALL).'</a>';
        if ($delete_user_available) {
            if ($row[0] != $_user['user_id'] && !$user_is_anonymous && api_global_admin_can_edit_admin($user_id)) {                  
                // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
                $result .= ' <a href="user_list.php?action=delete_user&amp;user_id='.$user_id.'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
            } else {
                $result .= Display::return_icon('delete_na.png', get_lang('Delete'), array(), ICON_SIZE_SMALL);
            }
        }       
    }
	return $result;
}


/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $active the current state of the account
 * @param int $user_id The user id
 * @param string $url_params
 * @return string Some HTML-code with the lock/unlock button
 */
function active_filter($active, $url_params, $row) {
	global $_user;

	if ($active=='1') {
		$action='Lock';
		$image='accept';
	} elseif ($active=='-1') {
    	$action='edit';
        $image='warning';
    } elseif ($active=='0') {
		$action='Unlock';
		$image='error';

	}
    $result = '';
    if ($action=='edit') {
        $result = Display::return_icon($image.'.png', get_lang('AccountExpired'), array(), 16);
    } elseif ($row['0']<>$_user['user_id']) {
    	// you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.		
		$result = Display::return_icon($image.'.png', get_lang(ucfirst($action)), array('onclick'=>'active_user(this);', 'id'=>'img_'.$row['0']), 16).'</a>';
	}
	return $result;
}

/**
 * Instead of displaying the integer of the status, we give a translation for the status
 *
 * @param integer $status
 * @return string translation
 *
 * @version march 2008
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function status_filter($status) {
	$statusname = api_get_status_langvars();
	return $statusname[$status];
}


/**	INIT SECTION  */

$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;

if (isset($_GET['keyword']) || isset($_GET['keyword_firstname'])) {
    $interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
    $tool_name = get_lang('SearchUsers');
} else {
    $interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
    $tool_name = get_lang('UserList');
}

$message = '';

if (!empty($action)) {
	$check = Security::check_token('get');    
	if ($check) {
		switch ($action) {
            case 'add_user_to_my_url':
                $user_id = $_REQUEST["user_id"];
                $result = UrlManager::add_user_to_url($user_id, $current_access_url_id);
                if ($result ) {
                    $user_info = api_get_user_info($user_id);
                    $message = get_lang('UserAdded').' '.$user_info['firstname'].' '.$user_info['lastname'].' ('.$user_info['username'].')';
                    $message  = Display::return_message($message, 'confirmation');
                }                
                break;
            case 'login_as':
                $login_as_user_id = $_GET["user_id"];
                if (isset ($login_as_user_id)) {
                    login_user($login_as_user_id);
                }
                break;
			case 'show_message' :
                if (!empty($_GET['warn'])) {
                	// to prevent too long messages
                	if ($_GET['warn'] == 'session_message'){
                		$_GET['warn'] = $_SESSION['session_message_import_users'];
                	}
                	$message = Display::return_message(urldecode($_GET['warn']),'warning', false);
                }
                if (!empty($_GET['message'])) {
                    $message = Display :: return_message(stripslashes($_GET['message']), 'confirmation');
                }
				break;
			case 'delete_user' :
				if (api_is_platform_admin()) {
                    $user_to_delete = $_GET['user_id'];
                    $current_user_id = api_get_user_id();
                    
					if ($delete_user_available && api_global_admin_can_edit_admin($_GET['user_id'])) {
						if ($user_to_delete != $current_user_id && UserManager :: delete_user($_GET['user_id'])) {
							$message = Display :: return_message(get_lang('UserDeleted'), 'confirmation');
						} else {
							$message = Display :: return_message(get_lang('CannotDeleteUserBecauseOwnsCourse'), 'error');
						}
					} else {
						$message = Display :: return_message(get_lang('CannotDeleteUser'),'error');
					}
				}
				break;
		}
		Security::clear_token();
	}
}

if (isset ($_POST['action'])) {
	$check = Security::check_token('get');
	if ($check) {
		switch ($_POST['action']) {
			case 'delete' :
				if (api_is_platform_admin()) {
					$number_of_selected_users = count($_POST['id']);
					$number_of_deleted_users = 0;
					if (is_array($_POST['id'])) {
						foreach ($_POST['id'] as $index => $user_id) {
							if($user_id != $_user['user_id']) {
								if(UserManager :: delete_user($user_id)) {
									$number_of_deleted_users++;
								}
							}
						}
					}
					if($number_of_selected_users == $number_of_deleted_users) {
						Display :: display_confirmation_message(get_lang('SelectedUsersDeleted'));
					} else {
						Display :: display_error_message(get_lang('SomeUsersNotDeleted'));
					}
				}
				break;
		}
		Security::clear_token();
	}
}

// Create a search-box
$form = new FormValidator('search_simple','get','','',null,false);
$renderer =& $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->addElement('text','keyword',get_lang('keyword'), 'size="25"');
$form->addElement('style_submit_button', 'submit',get_lang('Search'),'class="search"');
$form->addElement('static','search_advanced_link',null,'<a href="javascript://" class = "advanced_parameters" onclick="display_advanced_search_form();"><span id="img_plus_and_minus">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).' '.get_lang('AdvancedSearch').'</span></a>');

$actions  = '';
if (api_is_platform_admin()) {
	$actions .= '<span style="float:right;">'.
		 '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_add.php">'.Display::return_icon('new_user.png',get_lang('AddUsers'),'',ICON_SIZE_MEDIUM).'</a>'.
		 '</span>';
}
$actions .=$form->return_form();


if (isset ($_GET['keyword'])) {
	$parameters = array ('keyword' => Security::remove_XSS($_GET['keyword']));
} elseif (isset ($_GET['keyword_firstname'])) {
	$parameters['keyword_firstname'] 	= Security::remove_XSS($_GET['keyword_firstname']);
	$parameters['keyword_lastname']	 	= Security::remove_XSS($_GET['keyword_lastname']);
	$parameters['keyword_username']	 	= Security::remove_XSS($_GET['keyword_username']);
	$parameters['keyword_email'] 	 	= Security::remove_XSS($_GET['keyword_email']);
	$parameters['keyword_officialcode'] = Security::remove_XSS($_GET['keyword_officialcode']);
	$parameters['keyword_status'] 		= Security::remove_XSS($_GET['keyword_status']);
	$parameters['keyword_active'] 		= Security::remove_XSS($_GET['keyword_active']);
	$parameters['keyword_inactive'] 	= Security::remove_XSS($_GET['keyword_inactive']);
}
// Create a sortable table with user-data
$parameters['sec_token'] = Security::get_token();

// get the list of all admins to mark them in the users list
$admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
$sql_admin = "SELECT user_id FROM $admin_table";
$res_admin = Database::query($sql_admin);
$_admins_list = array();
while ($row_admin = Database::fetch_row($res_admin)) {
	$_admins_list[] = $row_admin[0];
}

// display advaced search form
$form = new FormValidator('advanced_search','get');

$form->addElement('html','<div id="advanced_search_form" style="display:none;">');

$form->addElement('header', '', get_lang('AdvancedSearch'));

//$form->addElement('html', '<strong>'.get_lang('SearchAUser').'</strong>');

$form->addElement('html', '<table>');

$form->addElement('html', '<tr><td>');
$form->add_textfield('keyword_firstname',get_lang('FirstName'),false,array('style'=>'margin-left:17px'));
$form->addElement('html', '</td><td width="200px;">');
$form->add_textfield('keyword_lastname',get_lang('LastName'),false,array('style'=>'margin-left:17px'));
$form->addElement('html', '</td></tr>');

$form->addElement('html', '<tr><td>');
$form->add_textfield('keyword_username',get_lang('LoginName'),false,array('style'=>'margin-left:17px'));
$form->addElement('html', '</td>');
$form->addElement('html', '<td>');
$form->add_textfield('keyword_email',get_lang('Email'),false,array('style'=>'margin-left:17px'));
$form->addElement('html', '</td></tr>');

$form->addElement('html', '<tr><td>');
$form->add_textfield('keyword_officialcode',get_lang('OfficialCode'),false,array('style'=>'margin-left:17px'));
$form->addElement('html', '</td><td>');

$status_options = array();
$status_options['%'] = get_lang('All');
$status_options[STUDENT] = get_lang('Student');
$status_options[COURSEMANAGER] = get_lang('Teacher');
$status_options[DRH] = get_lang('Drh');
$status_options[SESSIONADMIN] = get_lang('Administrator');
$form->addElement('select','keyword_status',get_lang('Profile'),$status_options, array('style'=>'margin-left:17px'));
$form->addElement('html', '</td></tr>');

$form->addElement('html', '<tr><td>');
$active_group = array();
$active_group[] = $form->createElement('checkbox','keyword_active','',get_lang('Active'), array('style'=>'margin-left:17px'));
$active_group[] = $form->createElement('checkbox','keyword_inactive','',get_lang('Inactive'),array('style'=>'margin-left:17px'));
$form->addGroup($active_group,'',get_lang('ActiveAccount'),'<br/>',false);
$form->addElement('html', '</td><td>');


/*
 * @todo fix this code
$extra_data = UserManager::get_extra_fields( 0,10,5, 'ASC', true, 1);
var_dump($extra_data);
$extra_options = array();
if (!empty($extra_data)) {
    $extra_options[0] = get_lang('All');
    // get information about extra data for adding to input select
    foreach ($extra_data as $field_variable => $field_value) {
        $extra = UserManager::get_extra_field_information_by_name($field_variable);
        $extra_options[$field_variable] = $extra['field_display_text'];
    }

    $form->addElement('select', 'keyword_extra_data', get_lang('ExtraData'), $extra_options, array('id'=>'input_select_extra_data', 'style'=>'margin-left:17px', 'onchange'=>'if(this.value!=0){document.getElementById(\'extra_data_text\').style.display=\'block\';document.getElementById(\'input_extra_text\').value = "";}else{document.getElementById(\'extra_data_text\').style.display=\'none\';}'));
    $form->addElement('html', '<div id="extra_data_text" style="display:none;">');
    $form->add_textfield('keyword_extra_data_text', '', false, array('style'=>'margin-left:17px', 'id'=>'input_extra_text'));
    $form->addElement('html', '</div>');
} else {
    $form->addElement('html', '<div id="extra_data_text" style="display:none;">');
}*/

$form->addElement('html', '</td></tr>');

$form->addElement('html', '<tr><td>');
$form->addElement('style_submit_button', 'submit',get_lang('SearchUsers'),'class="search"');
$form->addElement('html', '</td></tr>');

$form->addElement('html', '</table>');

$defaults = array();
$defaults['keyword_active'] = 1;
$defaults['keyword_inactive'] = 1;
$form->setDefaults($defaults);
$form->addElement('html','</div>');

$form = $form->return_form();

$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false, 'width="18px"');
$table->set_header(1, get_lang('Photo'), false);
$table->set_header(2, get_lang('OfficialCode'));
if (api_is_western_name_order()) {
	$table->set_header(3, get_lang('FirstName'));
	$table->set_header(4, get_lang('LastName'));
} else {
	$table->set_header(3, get_lang('LastName'));
	$table->set_header(4, get_lang('FirstName'));
}
$table->set_header(5, get_lang('LoginName'));
$table->set_header(6, get_lang('Email'));
$table->set_header(7, get_lang('Profile'));
$table->set_header(8, get_lang('Active'),true, 'width="15px"');
$table->set_header(9, get_lang('Action'), false,'width="220px"');

$table->set_column_filter(3, 'user_filter');
$table->set_column_filter(4, 'user_filter');

$table->set_column_filter(6, 'email_filter');
$table->set_column_filter(7, 'status_filter');
$table->set_column_filter(8, 'active_filter');
$table->set_column_filter(9, 'modify_filter');

if (api_is_platform_admin())
	$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
$table_result = $table->return_table();

/* */

$extra_search_options = '';
//Try to search the user everywhere
if ($table->get_total_number_of_items() ==0) {
    
    if (api_get_multiple_access_url() && isset($_REQUEST['keyword'])) {        
        $keyword = Database::escape_string($_REQUEST['keyword']);
        //$conditions = array('firstname' => $keyword, 'lastname' => $keyword, 'username' => $keyword);
        $conditions = array('username' => $keyword);
        $user_list = UserManager::get_user_list($conditions, array(), false, ' OR ');        
        if (!empty($user_list)) {
            
            $extra_search_options = '<h3>'.get_lang('UsersFoundInOtherPortals').'</h3>';
            
            $table = new HTML_Table(array('class' => 'data_table'));
            $column = 0;
            $row = 0;            
            $headers = array(get_lang('User'), 'URL', get_lang('Actions'));
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            
            foreach ($user_list as $user) {      
                $column = 0;
                $access_info = UrlManager::get_access_url_from_user($user['user_id']);
                $access_info_to_string = '';
                $add_user = true;
                if (!empty($access_info)) {
                    foreach ($access_info as $url_info) {
                        if ($current_access_url_id == $url_info['access_url_id']) {
                            $add_user = false;                            
                        }
                        $access_info_to_string .= $url_info['url'].'<br />';
                    }
                }
                if ($add_user) {   
                    $row_table = array();
                    $row_table[] =  api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].') ';
                    $row_table[] =  $access_info_to_string;
                    $url = api_get_self().'?action=add_user_to_my_url&user_id='.$user['user_id'].'&sec_token='.$_SESSION['sec_token'];
                    $row_table[] =  Display::url(get_lang('AddUserToMyURL'), $url);
                    	
                    foreach ($row_table as $cell) {
                        $table->setCellContents($row, $column, $cell);
                        $table->updateCellAttributes($row, $column, 'align="center"');
                        $column++;
                    }
                    $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
                    $row++;
                }
            }
            $extra_search_options .= $table->toHtml();
            $table_result = '';
        }
    }    
}

$tpl = new Template($tool_name);

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $form.$table_result.$extra_search_options);
$tpl->display_one_col_template();
