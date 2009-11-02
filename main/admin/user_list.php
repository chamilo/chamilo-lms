<?php // $Id: user_list.php 22292 2009-07-22 18:32:32Z herodoto $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
	@author Bart Mollet
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
$cidReset = true;
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
require_once(api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
function load_course_list (div_course,my_user_id) {
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("div#"+div_course).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "course_user_list.php",
		data: "user_id="+my_user_id,
		success: function(datos) {
			$("div#"+div_course).html(datos);
			$("div#div_"+my_user_id).attr("class","blackboard_show");
			$("div#div_"+my_user_id).attr("style","");
		}
	});
}
function clear_course_list (div_course) {
	$("div#"+div_course).html("&nbsp;");
	$("div#"+div_course).hide("");
}
</script>';
$htmlHeadXtra[] = '<style type="text/css" media="screen, projection">
.blackboard_show {
	float:left;
	position:absolute;
	border:1px solid black;
	width: 200px;
	background-color:white;
	z-index:99; padding: 3px;
	display: inline;
}
.blackboard_hide {
	display: none;
}
';
// xajax
$xajax = new xajax();
$xajax->registerFunction('courses_of_user');
//$xajax->registerFunction('empty_courses_of_user');
$xajax->processRequests();


/**
 * Get a formatted list of courses for given user
 * @param   int     User ID
 * @return  resource    XAJAX response
 */
function courses_of_user($arg)
{
	// do some stuff based on $arg like query data from a database and
	// put it into a variable like $newContent
    //$newContent = 'werkt het? en met een beetje meer text, wordt dat goed opgelost? ';
    $personal_course_list = UserManager::get_personal_session_course_list($arg);
    $newContent = '';
    if(count($personal_course_list)>0)
    {
	    foreach ($personal_course_list as $key=>$course)
	    {
	    	$newContent .= $course['i'].'<br />';
	    }
    }
    else
    {
    	$newContent .= '- '.get_lang('None').' -<br />';
    }
    $newContent = api_convert_encoding($newContent,'utf-8',api_get_setting('platform_charset'));

	// Instantiate the xajaxResponse object
	$objResponse = new xajaxResponse();

	// add a command to the response to assign the innerHTML attribute of
	// the element with id="SomeElementId" to whatever the new content is
	$objResponse->addAssign("user".$arg,"innerHTML", $newContent);
	$objResponse->addReplace("coursesofuser".$arg,"alt", $newContent);
	$objResponse->addReplace("coursesofuser".$arg,"title", $newContent);

	$objResponse->addAssign("user".$arg,"style.display", "block");

	//return the  xajaxResponse object
	return $objResponse;
}
/**
 * Empties the XAJAX object representing the courses list
 * @param   int     User ID
 * @return  resource    XAJAX object
 */
function empty_courses_of_user($arg)
{
	// do some stuff based on $arg like query data from a database and
	// put it into a variable like $newContent
    $newContent = '';
	// Instantiate the xajaxResponse object
	$objResponse = new xajaxResponse();
	// add a command to the response to assign the innerHTML attribute of
	// the element with id="SomeElementId" to whatever the new content is
	$objResponse->addAssign("user".$arg,"innerHTML", $newContent);


	//return the  xajaxResponse object
	return $objResponse;
}


$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<style>
.tooltipLinkInner {
	position:relative;
	float:left;
	color:blue;
	text-decoration:none;
}
</style>';

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
	//init ---------------------------------------------------------------------
    //Load $_user to be sure we clean it before logging in
	global $uidReset, $loginFailed, $_configuration, $_user;

	$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$main_admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
	$track_e_login_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

	//logic --------------------------------------------------------------------
	unset($_user['user_id']); // uid not in session ? prevent any hacking
	if (!isset ($user_id)) {
		$uidReset = true;
		return;
	}
    if ($user_id != strval(intval($user_id))) {
    	return false;
    }

	$sql_query = "SELECT * FROM $main_user_table WHERE user_id='$user_id'";
	$sql_result = Database::query($sql_query, __FILE__, __LINE__);
	$result = Database :: fetch_array($sql_result);

    // check if the user is allowed to 'login_as'
    $can_login_as = (api_is_platform_admin() OR (api_is_session_admin() && $result['status'] == 5 ));
    if (!$can_login_as) { return false; }

	$firstname = $result['firstname'];
	$lastname = $result['lastname'];
	$user_id = $result['user_id'];

	//$message = "Attempting to login as ".api_get_person_name($firstname, $lastname)." (id ".$user_id.")";
	if (api_is_western_name_order()) {
		$message = sprintf(get_lang('AttemptingToLoginAs'),$firstname,$lastname,$user_id);
	} else {
		$message = sprintf(get_lang('AttemptingToLoginAs'), $lastname, $firstname, $user_id);
	}

	$loginFailed = false;
	$uidReset = false;

	if ($user_id) { // a uid is given (log in succeeded)
		if ($_configuration['tracking_enabled']) {
			$sql_query = "SELECT user.*, a.user_id is_admin,
				UNIX_TIMESTAMP(login.login_date) login_date
				FROM $main_user_table
				LEFT JOIN $main_admin_table a
				ON user.user_id = a.user_id
				LEFT JOIN $track_e_login_table login
				ON user.user_id = login.login_user_id
				WHERE user.user_id = '".$user_id."'
				ORDER BY login.login_date DESC LIMIT 1";
		} else {
			$sql_query = "SELECT user.*, a.user_id is_admin
				FROM $main_user_table
				LEFT JOIN $main_admin_table a
				ON user.user_id = a.user_id
				WHERE user.user_id = '".$user_id."'";
		}

		$sql_result = Database::query($sql_query, __FILE__, __LINE__);


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
			$_SESSION['_uid'] = $user_id;
			$_SESSION['_user'] = $_user;
			$_SESSION['is_platformAdmin'] = $is_platformAdmin;
			$_SESSION['is_allowedCreateCourse'] = $is_allowedCreateCourse;
			$_SESSION['login_as'] = true; // will be usefull later to know if the user is actually an admin or not (example reporting)s

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
function get_number_of_users()
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT COUNT(u.user_id) AS total_number_of_items FROM $user_table u";

	// adding the filter to see the user's only of the current access_url
    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

	if ( isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR u.username LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%') ";
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
		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table .
				" WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'    " .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin;
		if($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

	$res = Database::query($sql, __FILE__, __LINE__);
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
function get_user_data($from, $number_of_items, $column, $direction)
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
	$sql = "SELECT
                 u.user_id				AS col0,
                 u.official_code		AS col1,
				 ".(api_is_western_name_order()
                 ? "u.firstname 			AS col2,
                 u.lastname 			AS col3,"
                 : "u.lastname 			AS col2,
                 u.firstname 			AS col3,")."
                 u.username				AS col4,
                 u.email				AS col5,
                 u.status				AS col6,
                 u.active				AS col7,
                 u.user_id				AS col8 ".
                 ", u.expiration_date      AS exp ".
            " FROM $user_table u ";

    // adding the filter to see the user's only of the current access_url
    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR u.username LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%' )";
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
		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table." WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'    " .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin;

		if ($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

    if (!in_array($direction, array('ASC','DESC'))) {
    	$direction = 'ASC';
    }
    $column = intval($column);
    $from = intval($from);
    $number_of_items = intval($number_of_items);

	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = Database::query($sql, __FILE__, __LINE__);

	$users = array ();
    $t = time();
	while ($user = Database::fetch_row($res)) {
        if ($user[7] == 1 && $user[9] != '0000-00-00 00:00:00') {
            // check expiration date
            $expiration_time = convert_mysql_date($user[9]);
            // if expiration date is passed, store a special value for active field
            if ($expiration_time < $t) {
        	   $user[7] = '-1';
            }
        }
        // forget about the expiration date field		      
        $users[] = array($user[0],$user[1],$user[2],$user[3],$user[4],$user[5],$user[6],$user[7],$user[8]);
	}
	return $users;
}
/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email)
{
	return Display :: encrypted_mailto_link($email, $email);
}
/**
 * Build the modify-column of the table
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($user_id,$url_params,$row)
{
	global $charset;
	global $_user;
	global $_admins_list;
	$is_admin = in_array($user_id,$_admins_list);
	$statusname = api_get_status_langvars();
	$user_is_anonymous = false;
	if ($row['6'] == $statusname[ANONYMOUS]) {
		$user_is_anonymous =true;
	}
	if (!$user_is_anonymous) {
		$result .= '<a  href="javascript:void(0)" onclick="load_course_list(\'div_'.$user_id.'\','.$user_id.')">
					<img onclick="load_course_list(\'div_'.$user_id.'\','.$user_id.')" onmouseout="clear_course_list (\'div_'.$user_id.'\')" src="../img/course.gif" title="'.get_lang('Courses').'" alt="'.get_lang('Courses').'"/>
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
    if (api_is_platform_admin() || (api_is_session_admin() && $row['6'] == $statusname[STUDENT])) {
    	if (!$user_is_anonymous) {
        	$result .= '<a href="user_list.php?action=login_as&amp;user_id='.$user_id.'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>&nbsp;&nbsp;';
    	} else {
    		$result .= Display::return_icon('login_as_na.gif', get_lang('LoginAs')).'&nbsp;&nbsp;';
    	}
    } else {
    	$result .= Display::return_icon('login_as_na.gif', get_lang('LoginAs')).'&nbsp;&nbsp;';
    }
	if ($row['6'] != $statusname[STUDENT]) {
		$result .= Display::return_icon('statistics_na.gif', get_lang('Reporting')).'&nbsp;&nbsp;';
	} else {
		$result .= '<a href="../mySpace/myStudents.php?student='.$user_id.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;&nbsp;';
	}

	if (api_is_platform_admin()) {
		if (!$user_is_anonymous) {
			$result .= '<a href="user_edit.php?user_id='.$user_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>&nbsp;&nbsp;';
		} else {
				$result .= Display::return_icon('edit_na.gif', get_lang('Edit')).'</a>&nbsp;&nbsp;';
		}

		if ($row[0]<>$_user['user_id'] && $user_is_anonymous == false) {
			// you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
			$result .= '<a href="user_list.php?action=delete_user&amp;user_id='.$user_id.'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
		} else {
			$result .= Display::return_icon('delete_na.gif', get_lang('Delete'));
		}
	}
		if ($is_admin) {
			$result .= Display::return_icon('admin_star.png', get_lang('IsAdministrator'),array('width'=> 22, 'heigth'=> 22));
		
		} else {
			$result .= Display::return_icon('admin_star_na.png', get_lang('IsNotAdministrator'));		
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
		$action='lock';
		$image='right';
	} elseif ($active=='-1') {
    	$action='edit';
        $image='expired';
    } elseif ($active=='0') {
		$action='unlock';
		$image='wrong';
	}

    if ($action=='edit') {
        $result = Display::return_icon($image.'.gif', get_lang('AccountExpired'));
    }elseif ($row['0']<>$_user['user_id']) { // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
		$result = '<a href="user_list.php?action='.$action.'&amp;user_id='.$row['0'].'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';
	}
	return $result;
}

/**
 * Lock or unlock a user
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $status, do we want to lock the user ($status=lock) or unlock it ($status=unlock)
 * @param int $user_id The user id
 * @return language variable
 */
function lock_unlock_user($status,$user_id)
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	if ($status=='lock')
	{
		$status_db='0';
		$return_message=get_lang('UserLocked');
	}
	if ($status=='unlock')
	{
		$status_db='1';
		$return_message=get_lang('UserUnlocked');
	}

	if(($status_db=='1' OR $status_db=='0') AND is_numeric($user_id))
	{
		$sql="UPDATE $user_table SET active='".Database::escape_string($status_db)."' WHERE user_id='".Database::escape_string($user_id)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
	}

	if ($result)
	{
		return $return_message;
	}
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
function status_filter($status)
{
	$statusname = api_get_status_langvars();
	return $statusname[$status];
}


/**
==============================================================================
		INIT SECTION
==============================================================================
*/
$action = $_GET["action"];
$login_as_user_id = $_GET["user_id"];



// Login as ...
if ($_GET['action'] == "login_as" && isset ($login_as_user_id))
{
	login_user($login_as_user_id);
}

if (isset ($_GET['search']) && $_GET['search'] == 'advanced')
{
	$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
	$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
	$tool_name = get_lang('SearchAUser');
	Display :: display_header($tool_name);
	//api_display_tool_title($tool_name);
	$form = new FormValidator('advanced_search','get');
	$form->addElement('header', '', $tool_name);
	$form->add_textfield('keyword_firstname',get_lang('FirstName'),false);
	$form->add_textfield('keyword_lastname',get_lang('LastName'),false);
	$form->add_textfield('keyword_username',get_lang('LoginName'),false);
	$form->add_textfield('keyword_email',get_lang('Email'),false);
	$form->add_textfield('keyword_officialcode',get_lang('OfficialCode'),false);
	$status_options = array();
	$status_options['%'] = get_lang('All');
	$status_options[STUDENT] = get_lang('Student');
	$status_options[COURSEMANAGER] = get_lang('Teacher');
	$status_options[SESSIONADMIN] = get_lang('Administrator');//
	$form->addElement('select','keyword_status',get_lang('Status'),$status_options);
	$active_group = array();
	$active_group[] = $form->createElement('checkbox','keyword_active','',get_lang('Active'));
	$active_group[] = $form->createElement('checkbox','keyword_inactive','',get_lang('Inactive'));
	$form->addGroup($active_group,'',get_lang('ActiveAccount'),'<br/>',false);
	$form->addElement('style_submit_button', 'submit',get_lang('SearchUsers'),'class="search"');
	$defaults['keyword_active'] = 1;
	$defaults['keyword_inactive'] = 1;
	$form->setDefaults($defaults);
	$form->display();
}
else
{
	$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
	$tool_name = get_lang('UserList');
	Display :: display_header($tool_name, "");

	//api_display_tool_title($tool_name);
	if (isset ($_GET['action'])) {
		$check = Security::check_token('get');
		if($check) {
			switch ($_GET['action']) {
				case 'show_message' :
                    if (!empty($_GET['warn'])) {
                    	// to prevent too long messages
                    	if ($_GET['warn'] == 'session_message'){
                    		$_GET['warn'] = $_SESSION['session_message_import_users'];
                    	} 
                    	Display::display_warning_message(urldecode($_GET['warn']),false);
                    }
                    if (!empty($_GET['message'])) {
                        Display :: display_confirmation_message(stripslashes($_GET['message']));
                    }
					break;
				case 'delete_user' :
					if (api_is_platform_admin()) {
						if ($user_id != $_user['user_id'] && UserManager :: delete_user($_GET['user_id'])) {
							Display :: display_confirmation_message(get_lang('UserDeleted'));
						} else {
							Display :: display_error_message(get_lang('CannotDeleteUserBecauseOwnsCourse'));
						}
					}
					break;
				case 'lock' :
					$message=lock_unlock_user('lock',$_GET['user_id']);
					Display :: display_normal_message($message);
					break;
				case 'unlock';
					$message=lock_unlock_user('unlock',$_GET['user_id']);
					Display :: display_normal_message($message);
					break;

			}
			Security::clear_token();
		}
	}
	if (isset ($_POST['action']))
	{
		$check = Security::check_token('get');
		if($check)
		{
			switch ($_POST['action'])
			{
				case 'delete' :
					if (api_is_platform_admin()) {
						$number_of_selected_users = count($_POST['id']);
						$number_of_deleted_users = 0;
						if (is_array($_POST['id'])) {
							foreach ($_POST['id'] as $index => $user_id)
							{
								if($user_id != $_user['user_id'])
								{
									if(UserManager :: delete_user($user_id))
									{
										$number_of_deleted_users++;
									}
								}
							}
						}
						if($number_of_selected_users == $number_of_deleted_users)
						{
							Display :: display_confirmation_message(get_lang('SelectedUsersDeleted'));
						}
						else
						{
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
	$form->addElement('text','keyword',get_lang('keyword'));
	$form->addElement('style_submit_button', 'submit',get_lang('Search'),'class="search"');
	$form->addElement('static','search_advanced_link',null,'<a href="user_list.php?search=advanced">'.get_lang('AdvancedSearch').'</a>');
	echo '<div class="actions" style="width:100%;">';
	if (api_is_platform_admin()) {
		echo '<span style="float:right; padding-top:7px;">'.
			 '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_add.php">'.Display::return_icon('add_user_big.gif',get_lang('AddUsers')).get_lang('AddUsers').'</a>'.
			 '</span>';
	}
	$form->display();
	echo '</div>';
	if (isset ($_GET['keyword'])) {
		$parameters = array ('keyword' => Security::remove_XSS($_GET['keyword']));
	} elseif (isset ($_GET['keyword_firstname'])) {
		$parameters['keyword_firstname'] 	= Security::remove_XSS($_GET['keyword_firstname']);
		$parameters['keyword_lastname']	 	= Security::remove_XSS($_GET['keyword_lastname']);
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

	$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('OfficialCode'));
	if (api_is_western_name_order()) {
		$table->set_header(2, get_lang('FirstName'));
		$table->set_header(3, get_lang('LastName'));
	} else {
		$table->set_header(2, get_lang('LastName'));
		$table->set_header(3, get_lang('FirstName'));
	}
	$table->set_header(4, get_lang('LoginName'));
	$table->set_header(5, get_lang('Email'));
	$table->set_header(6, get_lang('Status'));
	$table->set_header(7, get_lang('Active'));
	$table->set_header(8, get_lang('Action'), false,'width="170px"');
	$table->set_column_filter(5, 'email_filter');
	$table->set_column_filter(6, 'status_filter');
	$table->set_column_filter(7, 'active_filter');
	$table->set_column_filter(8, 'modify_filter');
	if (api_is_platform_admin())
		$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
	$table->display();
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>
