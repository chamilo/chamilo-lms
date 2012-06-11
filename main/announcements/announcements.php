<?php
/* For licensing terms, see /license.txt */
/**
 * @author Frederik Vermeire <frederik.vermeire@pandora.be>, UGent Internship
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: code cleaning
 * @author Julio Montoya <gugli100@gmail.com>, MORE code cleaning 2011
 *
 * @abstract The task of the internship was to integrate the 'send messages to specific users' with the
 *			 Announcements tool and also add the resource linker here. The database also needed refactoring
 *			 as there was no title field (the title was merged into the content field)
 * @package chamilo.announcements
 * @todo make AWACS out of the configuration settings
 * @todo this file is 1300+ lines without any functions -> needs to be split into
 * multiple functions
*/
/*
		INIT SECTION
*/
// name of the language file that needs to be included

use \ChamiloSession as Session;

$language_file = array('announcements', 'group', 'survey');

// use anonymous mode when accessing this course tool
$use_anonymous = true;

// setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_ANNOUNCEMENT;
$this_section=SECTION_COURSES;
$nameTools = get_lang('ToolAnnouncement');

//session
if(isset($_GET['id_session'])) {
	$_SESSION['id_session'] = intval($_GET['id_session']);
}

/* ACCESS RIGHTS */
api_protect_course_script();

// Configuration settings
$display_announcement_list	 = true;
$display_form				 = false;
$display_title_list 		 = true;

// Maximum title messages to display
$maximum 	= '12';

// Length of the titles
$length 	= '36';

// Database Table Definitions
$tbl_courses			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_sessions			= Database::get_main_table(TABLE_MAIN_SESSION);

$tbl_announcement		= Database::get_course_table(TABLE_ANNOUNCEMENT);
$tbl_item_property  	= Database::get_course_table(TABLE_ITEM_PROPERTY);

/*	Libraries	*/

$lib = api_get_path(LIBRARY_PATH); //avoid useless function calls
require_once $lib.'groupmanager.lib.php';
require_once $lib.'mail.lib.inc.php';
require_once $lib.'tracking.lib.php';
require_once $lib.'fckeditor/fckeditor.php';
require_once $lib.'fileUpload.lib.php';
require_once 'announcements.inc.php';

$course_id = api_get_course_int_id();

/*	Tracking	*/
event_access_tool(TOOL_ANNOUNCEMENT);


/*	POST TO	*/
$safe_emailTitle = $_POST['emailTitle'];
$safe_newContent = $_POST['newContent'];

$content_to_modify = $title_to_modify 	= '';

if (!empty($_POST['To'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
		api_not_allowed();
	}
	$display_form = true;

	$form_elements = array ('emailTitle'=>$safe_emailTitle, 'newContent'=>$safe_newContent, 'id'=>$_POST['id'], 'emailoption'=>$_POST['email_ann']);
    $_SESSION['formelements'] = $form_elements;

    $form_elements            	= $_SESSION['formelements'];
	$title_to_modify            = $form_elements["emailTitle"];
	$content_to_modify          = $form_elements["newContent"];
	$announcement_to_modify     = $form_elements["id"];
}

/*
	Show/hide user/group form
*/

$setting_select_groupusers = true;
if (empty($_POST['To']) and !$_SESSION['select_groupusers']) {
	$_SESSION['select_groupusers'] = "hide";
}
$select_groupusers_status=$_SESSION['select_groupusers'];
if (!empty($_POST['To']) and ($select_groupusers_status=="hide")) {
	$_SESSION['select_groupusers'] = "show";
}
if (!empty($_POST['To']) and ($select_groupusers_status=="show")) {
	$_SESSION['select_groupusers'] = "hide";
}

/* 	Action handling */

// display the form
if (((!empty($_GET['action']) && $_GET['action'] == 'add') && $_GET['origin'] == "") || (!empty($_GET['action']) && $_GET['action'] == 'edit') || !empty($_POST['To'])) {
	if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
		api_not_allowed();
	}
	$display_form = true;
}

// clear all resources
if ((empty($originalresource) || ($originalresource!=='no')) and (!empty($action) && $action=='add')) {
	$_SESSION['formelements']=null;
}

$htmlHeadXtra[] = AnnouncementManager::to_javascript();

/*	Filter user/group */

if(!empty($_GET['toolgroup'])){
	if($_GET['toolgroup'] == strval(intval($_GET['toolgroup']))){ //check is integer
		$toolgroup = $_GET['toolgroup'];
		$_SESSION['select_groupusers'] = 'hide';
	} else {
		$toolgroup = 0;
	}
	Session::write("toolgroup", $toolgroup);
}

/*	Sessions */

$ctok = $_SESSION['sec_token'];
$stok = Security::get_token();
$to = null;
$email_ann = null;

if (!empty($_SESSION['formelements']) and !empty($_GET['originalresource']) and $_GET['originalresource'] == 'no') {
	$form_elements			= $_SESSION['formelements'];
	$title_to_modify		= $form_elements['emailTitle'];
	$content_to_modify		= $form_elements['newContent'];
	$announcement_to_modify	= $form_elements['id'];
	$to						= $form_elements['to'];
	//load_edit_users('announcement',$announcement_to_modify);
	$email_ann				= $form_elements['emailoption'];
}
if(!empty($_GET['remind_inactive'])) {
	$to[] = 'USER:'.intval($_GET['remind_inactive']);
}
if (!empty($_SESSION['toolgroup'])){
	$_clean_toolgroup=intval($_SESSION['toolgroup']);
	$group_properties  = GroupManager :: get_group_properties($_clean_toolgroup);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_clean_toolgroup, "name"=> get_lang('GroupSpace').' '.$group_properties['name']);
}

$announcement_id = intval($_GET['id']);
$message = null;

if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
	//we are not in the learning path
	Display::display_header($nameTools,get_lang('Announcements'));
}

if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
/*
	Change visibility of announcement
*/
	// $_GET['isStudentView']<>"false" is added to prevent that the visibility
	// is changed after you do the following:
	// change visibility -> studentview -> course manager view
	if (!isset($_GET['isStudentView']) || $_GET['isStudentView']!='false') {
		if (isset($_GET['id']) AND $_GET['id'] AND isset($_GET['action']) AND $_GET['action']=="showhide") {
			if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
				api_not_allowed();
			}
			if (!api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $_GET['id'])) {
				if ($ctok == $_GET['sec_token']) {

					AnnouncementManager::change_visibility_announcement($_course, $_GET['id']);
					$message = get_lang('VisibilityChanged');
				}
			}
		}
	}

	/*
		Delete announcement
	*/
	if (!empty($_GET['action']) && $_GET['action']=='delete' && isset($_GET['id'])) {
		$id=intval($_GET['id']);
		if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
			api_not_allowed();
		}

		if (!api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
			// tooledit : visibility = 2 : only visible for platform administrator
			if ($ctok == $_GET['sec_token']) {
				AnnouncementManager::delete_announcement($_course, $id);
				//delete_added_resource("Ad_Valvas", $delete);

				$id = null;
				$emailTitle = null;
				$newContent = null;
				$message = get_lang('AnnouncementDeleted');
			}
		}
	}

      //delete attachment file
    if (isset($_GET['action']) && $_GET['action'] == 'delete_attachment') {
        $id = $_GET['id_attach'];
        if ($ctok == $_GET['sec_token']) {
            if (api_is_allowed_to_edit()) {
                AnnouncementManager::delete_announcement_attachment_file($id);
            }
        }
    }

	/*
		Delete all announcements
	*/
	if (!empty($_GET['action']) and $_GET['action']=='delete_all') {
		if (api_is_allowed_to_edit()) {
			AnnouncementManager::delete_all_announcements($_course);
			$id = null;
			$emailTitle = null;
			$newContent = null;
			$message = get_lang('AnnouncementDeletedAll');
		}
	}

	/*
		Modify announcement
	*/

	if (!empty($_GET['action']) and $_GET['action']=='modify' AND isset($_GET['id'])) {
		if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
			api_not_allowed();
		}

		$display_form = true;

		// RETRIEVE THE CONTENT OF THE ANNOUNCEMENT TO MODIFY
		$id = intval($_GET['id']);

		if (!api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
			$sql="SELECT * FROM  $tbl_announcement WHERE c_id = $course_id AND id = '$id'";
			$rs 	= Database::query($sql);
			$myrow  = Database::fetch_array($rs);
			$last_id = $id;
			$edit_attachment = AnnouncementManager::edit_announcement_attachment_file($last_id, $_FILES['user_upload'], $file_comment);

			if ($myrow) {
				$announcement_to_modify 	= $myrow['id'];
				$content_to_modify 			= $myrow['content'];
				$title_to_modify 			= $myrow['title'];

				if ($originalresource!=="no")  {
					$to=AnnouncementManager::load_edit_users("announcement", $announcement_to_modify);
				}
				$display_announcement_list = false;
			}

			if ($to=="everyone" OR !empty($_SESSION['toolgroup'])) {
				$_SESSION['select_groupusers']="hide";
			} else {
				$_SESSION['select_groupusers']="show";
			}
		}
	}

	/*
		Move announcement up/down
	*/

	if (isset($_GET['sec_token']) && $ctok == $_GET['sec_token']) {
		if (!empty($_GET['down'])) {
			$thisAnnouncementId = intval($_GET['down']);
			$sortDirection = "DESC";
		}

		if (!empty($_GET['up'])) {
			$thisAnnouncementId = intval($_GET['up']);
			$sortDirection = "ASC";
		}
	}

	if (!empty($sortDirection)) {
		if (!in_array(trim(strtoupper($sortDirection)), array('ASC', 'DESC'))) {
			$sortDirection='ASC';
		}
		$my_sql = "SELECT announcement.id, announcement.display_order " .
				"FROM $tbl_announcement announcement, " .
				"$tbl_item_property itemproperty " .
				"WHERE
				announcement.c_id =  $course_id AND
				itemproperty.c_id =  $course_id AND
					itemproperty.ref=announcement.id " .
				"AND itemproperty.tool='".TOOL_ANNOUNCEMENT."' " .
				"AND itemproperty.visibility<>2 " .
				"ORDER BY display_order $sortDirection";
		$result = Database::query($my_sql);

		while (list ($announcementId, $announcementOrder) = Database::fetch_row($result)) {
			// STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
			//          COMMIT ORDER SWAP ON THE DB

			if ($thisAnnouncementOrderFound) {
				$nextAnnouncementId = $announcementId;
				$nextAnnouncementOrder = $announcementOrder;
				Database::query("UPDATE $tbl_announcement SET display_order = '$nextAnnouncementOrder'  WHERE c_id = $course_id AND id =  '$thisAnnouncementId'");
				Database::query("UPDATE $tbl_announcement  SET display_order = '$thisAnnouncementOrder' WHERE c_id = $course_id AND id =  '$nextAnnouncementId.'");
				break;
			}
			// STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
			if ($announcementId == $thisAnnouncementId) {
				$thisAnnouncementOrder = $announcementOrder;
				$thisAnnouncementOrderFound = true;
			}
		}
		// show message
		$message = get_lang('AnnouncementMoved');
	}

	/*
		Submit announcement
	*/
	//if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {

	$emailTitle=(!empty($_POST['emailTitle'])?$safe_emailTitle:'');
	$newContent=(!empty($_POST['newContent'])?$safe_newContent:'');

	$submitAnnouncement=isset($_POST['submitAnnouncement'])?$_POST['submitAnnouncement']:0;

	$id = 0;
	if (!empty($_POST['id'])) {
		$id=intval($_POST['id']);
	}

	if ($submitAnnouncement && empty($emailTitle)) {
		$error_message = get_lang('TitleIsRequired');
		$content_to_modify = $newContent;
	} else if ($submitAnnouncement) {

		if (isset($id) && $id) {
			// there is an Id => the announcement already exists => update mode
			if ($ctok == $_POST['sec_token']) {
				$file_comment = $_POST['file_comment'];
				$file = $_FILES['user_upload'];
				AnnouncementManager::edit_announcement($id,	$emailTitle, $newContent, $_POST['selectedform'], $file, $file_comment);

                /*		MAIL FUNCTION	*/
                if ($_POST['email_ann'] && empty($_POST['onlyThoseMails'])) {
                    AnnouncementManager::send_email($id);
                }
				$message = get_lang('AnnouncementModified');
			}
		} else {
			//insert mode
			if ($ctok == $_POST['sec_token']) {

				//if (!$surveyid) {
				    $sql = "SELECT MAX(display_order) FROM $tbl_announcement WHERE c_id = $course_id AND (session_id=".api_get_session_id()." OR session_id=0)";
					$result = Database::query($sql);
					list($orderMax) = Database::fetch_row($result);
					$order = $orderMax + 1;
					$file = $_FILES['user_upload'];
					$file_comment = $_POST['file_comment'];
					if (!empty($_SESSION['toolgroup'])) {
						$insert_id = AnnouncementManager::add_group_announcement($safe_emailTitle,$safe_newContent,$order,array('GROUP:'.$_SESSION['toolgroup']),$_POST['selectedform'],$file,$file_comment);
					} else {
						$insert_id = AnnouncementManager::add_announcement($safe_emailTitle, $safe_newContent, $order, $_POST['selectedform'], $file, $file_comment);
					}
				    //store_resources($_SESSION['source_type'],$insert_id);
				    $_SESSION['select_groupusers']="hide";
				    $message = get_lang('AnnouncementAdded');

                    /*		MAIL FUNCTION	*/
                    if ($_POST['email_ann'] && empty($_POST['onlyThoseMails'])) {
                        AnnouncementManager::send_email($insert_id);
                    }

			} // end condition token
		}	// isset

		// UNSET VARIABLES
		unset($form_elements);
		$_SESSION['formelements']=null;

		$newContent = null;
		$emailTitle = null;

		unset($emailTitle);
		unset($newContent);
		unset($content_to_modify);
		unset($title_to_modify);

	}	// if $submit Announcement
}

/*  	Tool introduction  */

if (empty($_GET['origin']) || $_GET['origin'] !== 'learnpath') {
	Display::display_introduction_section(TOOL_ANNOUNCEMENT);
}

/* DISPLAY LEFT COLUMN */

//condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id, true, true);

if (api_is_allowed_to_edit(false,true))  {
 	// check teacher status
  	if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {

  		if (api_get_group_id() == 0) {
			$group_condition = "";
		} else {
			$group_condition = " AND (ip.to_group_id='".api_get_group_id()."' OR ip.to_group_id = 0)";
		}
		$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
				FROM $tbl_announcement announcement, $tbl_item_property ip
				WHERE   announcement.c_id   = $course_id AND
                        ip.c_id             = $course_id AND
                        announcement.id     = ip.ref AND
                        ip.tool             = 'announcement' AND
                        ip.visibility       <> '2'
                        $group_condition
                        $condition_session
				GROUP BY ip.ref
				ORDER BY display_order DESC
				LIMIT 0,$maximum";
	}
} else {
	// students only get to see the visible announcements
		if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
			$group_memberships=GroupManager::get_group_ids($_course['real_id'], $_user['user_id']);

			if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {

				if (api_get_group_id() == 0) {
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR ( ip.to_user_id='".$_user['user_id']."'" .
						"OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))) ";
				} else {
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."'
					OR ip.to_group_id IN (0, ".api_get_group_id()."))";
				}
			} else {
				if (api_get_group_id() == 0) {
						$cond_user_id = " AND ( ip.to_user_id='".$_user['user_id']."'" .
							"OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).")) ";
					} else {
						$cond_user_id = " AND ( ip.to_user_id='".$_user['user_id']."'" .
							"OR ip.to_group_id IN (0, ".api_get_group_id().")) ";
					}
			}

			// the user is member of several groups => display personal announcements AND his group announcements AND the general announcements
			if (is_array($group_memberships) && count($group_memberships)>0) {
				$sql="SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE
					announcement.c_id = $course_id AND
					ip.c_id = $course_id AND
					announcement.id = ip.ref AND
					ip.tool='announcement'
					AND ip.visibility='1'
					$cond_user_id
					$condition_session
					GROUP BY ip.ref
					ORDER BY display_order DESC
					LIMIT 0,$maximum";
			} else {
				// the user is not member of any group
				// this is an identified user => show the general announcements AND his personal announcements
				if ($_user['user_id']) {

					if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
						$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR ( ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0')) ";
					} else {
						$cond_user_id = " AND ( ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0') ";
					}
					$sql="SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
						announcement.c_id = $course_id AND
						ip.c_id = $course_id AND
						announcement.id = ip.ref
						AND ip.tool='announcement'
						AND ip.visibility='1'
						$cond_user_id
						$condition_session
						GROUP BY ip.ref
						ORDER BY display_order DESC
						LIMIT 0,$maximum";
				} else {

					if (api_get_course_setting('allow_user_edit_announcement')) {
						$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR ip.to_group_id='0') ";
					} else {
						$cond_user_id = " AND ip.to_group_id='0' ";
					}

					// the user is not identiefied => show only the general announcements
					$sql="SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
						announcement.c_id = $course_id AND
						ip.c_id = $course_id AND
						announcement.id = ip.ref
						AND ip.tool='announcement'
						AND ip.visibility='1'
						AND ip.to_group_id='0'
						$condition_session
						GROUP BY ip.ref
						ORDER BY display_order DESC
						LIMIT 0,$maximum";
				}
			}
		}
}

$result = Database::query($sql);
$announcement_number = Database::num_rows($result);

/*
	ADD ANNOUNCEMENT / DELETE ALL
*/

$show_actions = false;
if ((api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) and (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath')) {
	echo '<div class="actions">';
	if (isset($_GET['action']) && in_array($_GET['action'], array('add', 'modify','view'))) {
        echo "<a href='".api_get_self()."?".api_get_cidreq()."&origin=".(empty($_GET['origin'])?'':$_GET['origin'])."'>".Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM)."</a>";
	} else {
	   echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add&origin=".(empty($_GET['origin'])?'':$_GET['origin'])."'>".Display::return_icon('new_announce.png',get_lang('AddAnnouncement'),'',ICON_SIZE_MEDIUM)."</a>";
	}
	$show_actions = true;
} else {
    if (in_array($_GET['action'], array('view'))) {
        echo '<div class="actions">';
        echo "<a href='".api_get_self()."?".api_get_cidreq()."&origin=".(empty($_GET['origin'])?'':$_GET['origin'])."'>".Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM)."</a>";
        echo '</div>';
    }
}

if (api_is_allowed_to_edit() && $announcement_number > 1) {
	if (api_get_group_id() == 0 ) {
		if (!$show_actions)
			echo '<div class="actions">';
			if (!in_array($_GET['action'], array('add', 'modify','view')))
                echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang("ConfirmYourChoice")."')) return false;\">".Display::return_icon('delete_announce.png',get_lang('AnnouncementDeleteAll'),'',ICON_SIZE_MEDIUM)."</a>";
    	}	// if announcementNumber > 1
}

if ($show_actions)
    echo '</div>';


//	ANNOUNCEMENTS LIST

if ($message) {
	Display::display_confirmation_message($message);
	$display_announcement_list = true;
	$display_form             = false;
}
if (!empty($error_message)) {
	Display::display_error_message($error_message);
	$display_announcement_list = false;
	$display_form             = true;
}

/*
		DISPLAY FORM
*/

if ($display_form) {

	$content_to_modify 	= stripslashes($content_to_modify);
	$title_to_modify 	= stripslashes($title_to_modify);

	// DISPLAY ADD ANNOUNCEMENT COMMAND
	//echo '<form method="post" name="f1" enctype = "multipart/form-data" action="'.api_get_self().'?publish_survey='.Security::remove_XSS($surveyid).'&id='.Security::remove_XSS($_GET['id']).'&db_name='.$db_name.'&cidReq='.Security::remove_XSS($_GET['cidReq']).'" style="margin:0px;">';
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	echo '<form class="form-horizontal" method="post" name="f1" enctype = "multipart/form-data" action="'.api_get_self().'?id='.$id.'&'.api_get_cidreq().'" style="margin:0px;">';
	if (empty($_GET['id'])) {
		$form_name = get_lang('AddAnnouncement');
	} else {
		$form_name = get_lang('ModifyAnnouncement');
	}
	echo '<legend>'.$form_name.'</legend>';

	//this variable defines if the course administrator can send a message to a specific user / group or not
    //@todo use formvalidator

	if (empty($_SESSION['toolgroup'])) {
		echo '	<div class="control-group">
					<label class="control-label">'.
						Display::return_icon('group.png', get_lang('ModifyRecipientList'), array ('align' => 'absmiddle'),ICON_SIZE_SMALL).'<a href="#" onclick="toggle_sendto();">'.get_lang('SentTo').'</a>
					</label>
					<div class="controls">';
		if (isset($_GET['id']) && is_array($to)) {
			echo '<span id="recipient_overview">&nbsp;</span>';
		} elseif (isset($_GET['remind_inactive'])) {
			$email_ann = '1';
			$_SESSION['select_groupusers']="show";
			$content_to_modify = sprintf(get_lang('RemindInactiveLearnersMailContent'), api_get_setting('siteName'), 7);
			$title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('siteName'));
		} elseif (isset($_GET['remindallinactives']) && $_GET['remindallinactives']=='true') {
			// we want to remind inactive users. The $_GET['since'] parameter determines which users have to be warned (i.e the users who have been inactive for x days or more
			$since = isset($_GET['since']) ? intval($_GET['since']) : 6;
			// getting the users who have to be reminded
			$to = Tracking :: get_inactives_students_in_course($_course['id'],$since, api_get_session_id());
			// setting the variables for the form elements: the users who need to receive the message
			foreach($to as &$user) {
				$user = 'USER:'.$user;
			}
			// setting the variables for the form elements: the 'visible to' form element has to be expanded
			$_SESSION['select_groupusers']="show";
			// setting the variables for the form elements: the message has to be sent by email
			$email_ann = '1';
			// setting the variables for the form elements: the title of the email
			//$title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('siteName'),' > ',$_course['name']);
			$title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('siteName'));
			// setting the variables for the form elements: the message of the email
			//$content_to_modify = sprintf(get_lang('RemindInactiveLearnersMailContent'),api_get_setting('siteName'),' > ',$_course['name'],$since);
			$content_to_modify = sprintf(get_lang('RemindInactiveLearnersMailContent'),api_get_setting('siteName'),$since);
			// when we want to remind the users who have never been active then we have a different subject and content for the announcement
			if ($_GET['since'] == 'never') {
				$title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('siteName'));
				$content_to_modify = get_lang('YourAccountIsActiveYouCanLoginAndCheckYourCourses');
			}
		} else {
			echo '<span id="recipient_overview">' . get_lang('Everybody') . '</span>';
		}
		AnnouncementManager::show_to_form($to);
		echo '		</div>
					</div>';

		if (!isset($announcement_to_modify) ) $announcement_to_modify ='';

        ($email_ann=='1')?$checked='checked':$checked='';
        echo '	<div class="control-group">
                    <div class="controls">
                        <label class="checkbox" for="email_ann">
                            <input id="email_ann" class="checkbox" type="checkbox" value="1" name="email_ann" checked> '.get_lang('EmailOption').'</label>
                    </div>
                </div>';


	} else {
		if (!isset($announcement_to_modify) ) {
			$announcement_to_modify ="";
		}

			($email_ann=='1' || !empty($surveyid))?$checked='checked':$checked='';
			echo '<div class="control-group">
				  <div class="controls">
				  <input class="checkbox" type="checkbox" value="1" name="email_ann" '.$checked.'>
				  '.get_lang('EmailOption').': <span id="recipient_overview">'.get_lang('MyGroup').'</span>
				  <a href="#" onclick="toggle_sendto();">'.get_lang('ModifyRecipientList').'</a>';
			      AnnouncementManager::show_to_form_group($_SESSION['toolgroup']);
			echo '</div></div>';
	}

	// the announcement title
	echo '	<div class="control-group">
				<div id="msg_error" style="display:none;color:red;margin-left:20%"></div>
				<label class="control-label">
					<span class="form_required">*</span> '.get_lang('EmailTitle').'
				</label>
				<div class="controls">
					<input type="text" id="emailTitle" name="emailTitle" value="'.Security::remove_XSS($title_to_modify).'" class="span4">
				</div>
			</div>';

	unset($title_to_modify);
	$title_to_modify = null;

	if (!isset($announcement_to_modify) ) $announcement_to_modify ="";
	if (!isset($content_to_modify) ) 		$content_to_modify ="";
	if (!isset($title_to_modify)) 		$title_to_modify = "";

	echo '<input type="hidden" name="id" value="'.$announcement_to_modify.'" />';

    $oFCKeditor = new FCKeditor('newContent') ;
	$oFCKeditor->Width		= '100%';
	$oFCKeditor->Height		= '300';

	if(!api_is_allowed_to_edit()) {
		$oFCKeditor->ToolbarSet = "AnnouncementsStudent";
	} else {
		$oFCKeditor->ToolbarSet = "Announcements";
	}

	$oFCKeditor->Value		= $content_to_modify;

	echo '<div class="row"><div class="formw">';

	echo Display::display_normal_message(get_lang('Tags').' <br /><br />'.implode('<br />', AnnouncementManager::get_tags()), false);

	echo $oFCKeditor->CreateHtml();
	echo '</div></div>';

	//File attachment
	echo '	<div class="control-group">
				<div class="controls">
				    <a href="javascript://" onclick="return plus_attachment();"><span id="plus"><img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AddAnAttachment').'</span></a>
				    <br />
					<table id="options" style="display: none;">
					<tr>
						<td colspan="2">
					        <label for="file_name">'.get_lang('FileName').'&nbsp;</label>
					        <input type="file" name="user_upload"/>
					    </td>
					 </tr>
					 <tr>
					    <td colspan="2">
					    	<label for="comment">'.get_lang('FileComment').'</label><br />
					    	<textarea name="file_comment" rows ="4" cols = "34" ></textarea>
					    </td>
				    </tr>
			    </table>
			 </div>
			</div>';

	echo'<br />';
	echo '<div class="row"><div class="formw">';

	if (empty($_SESSION['toolgroup'])) {
		echo '<input type="hidden" name="submitAnnouncement" value="OK">';
		echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';
		echo '<button class="btn save" type="button"  value="'.'  '.get_lang('Send').'  '.'" onclick="selectAll(this.form.elements[3],true)" >'.get_lang('ButtonPublishAnnouncement').'</button><br /><br />';
	} else {
		echo '<input type="hidden" name="submitAnnouncement" value="OK">';
		echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';
		echo '<button class="btn save" type="button"  value="'.'  '.get_lang('Send').'  '.'" onclick="selectAll(this.form.elements[4],true)" >'.get_lang('ButtonPublishAnnouncement').'</button><br /><br />';
	}
	echo '</div></div>';
	echo '</form><br />';

	if ((isset($_GET['action']) && isset($_GET['id']) && is_array($to))||isset($_GET['remindallinactives'])||isset($_GET['remind_inactive'])) {
		echo '<script>toggle_sendto();</script>';
	}

} // displayform

/*
		DISPLAY ANNOUNCEMENT LIST
*/

$course_id = api_get_course_int_id();

//if ($display_announcement_list && !$surveyid) {
if ($display_announcement_list) {
	// by default we use the id of the current user. The course administrator can see the announcement of other users by using the user / group filter
	//$user_id=$_user['user_id'];
	if (isset($_SESSION['user'])) {
		//$user_id=$_SESSION['user'];
	}
	$user_id = api_get_user_id();

	if (isset($_SESSION['group'])) {
		//$group_id=$_SESSION['group'];
	}
	$group_id = api_get_group_id();

	$group_memberships = GroupManager::get_group_ids($course_id, api_get_user_id());

	//$is_group_member = GroupManager :: is_tutor(api_get_user_id());

	if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
		// A.1. you are a course admin with a USER filter
		// => see only the messages of this specific user + the messages of the group (s)he is member of.
		if (!empty($_SESSION['user'])) {

			if (is_array($group_memberships) && count($group_memberships) > 0 ) {
				$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE 	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
							announcement.id = ip.ref AND
							ip.tool			= 'announcement' AND
							(ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
							$condition_session

					ORDER BY display_order DESC";

			} else {
				$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE	announcement.c_id 	= $course_id AND
							ip.c_id 			= $course_id AND
							announcement.id 	= ip.ref AND
							ip.tool				='announcement' AND
							(ip.to_user_id		= $user_id OR ip.to_group_id='0') AND
							ip.visibility='1'
					$condition_session
					ORDER BY display_order DESC";

			}
		} elseif (api_get_group_id() != 0 ) {
			// A.2. you are a course admin with a GROUP filter
			// => see only the messages of this specific group
			$sql="SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
				FROM $tbl_announcement announcement, $tbl_item_property ip
				WHERE	announcement.c_id = $course_id AND
						ip.c_id = $course_id AND
						announcement.id = ip.ref
						AND ip.tool='announcement'
						AND ip.visibility<>'2'
						AND (ip.to_group_id=$group_id OR ip.to_group_id='0')
						$condition_session
				GROUP BY ip.ref
				ORDER BY display_order DESC";
		} else {

			// A.3 you are a course admin without any group or user filter
			// A.3.a you are a course admin without user or group filter but WITH studentview
			// => see all the messages of all the users and groups without editing possibilities

			if (isset($isStudentView) and $isStudentView=="true") {
				$sql="SELECT
					announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
							announcement.id = ip.ref
							AND ip.tool='announcement'
							AND ip.visibility='1'
							$condition_session
					GROUP BY ip.ref
					ORDER BY display_order DESC";
			} else {
				// A.3.a you are a course admin without user or group filter and WTIHOUT studentview (= the normal course admin view)
				// => see all the messages of all the users and groups with editing possibilities
				 $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE 	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
							announcement.id = ip.ref
							AND ip.tool='announcement'
							AND (ip.visibility='0' or ip.visibility='1')
							$condition_session
					GROUP BY ip.ref
					ORDER BY display_order DESC";
			}
		}
	} else {
		//STUDENT

		if (is_array($group_memberships) && count($group_memberships)>0) {
			if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
				if (api_get_group_id() == 0) {
					//No group
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR ( ip.to_user_id='".$_user['user_id']."'" .
									" OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))) ";
				} else {
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."'
					OR ip.to_group_id IN (0, ".api_get_group_id()."))";
				}
				//$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR (ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )) ";

			} else {
				if (api_get_group_id() == 0) {
					$cond_user_id = " AND (ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).")) ";
				} else {
					$cond_user_id = " AND (ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".api_get_group_id()."))";
				}
			}

			$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
    				FROM $tbl_announcement announcement, $tbl_item_property ip
    				WHERE	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
	        				announcement.id = ip.ref
	        				AND ip.tool='announcement'
	        				$cond_user_id
	        				$condition_session
	        				AND ip.visibility='1'
    				ORDER BY display_order DESC";
		} else {
			if ($_user['user_id']) {
				if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR (ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0')) ";
				} else {
					$cond_user_id = " AND (ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0') ";
				}

				$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
    						announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
    						announcement.id = ip.ref AND
    						ip.tool='announcement'
    						$cond_user_id
    						$condition_session
    						AND ip.visibility='1'
    						AND announcement.session_id IN(0,".api_get_session_id().")
						ORDER BY display_order DESC";
			} else {

				if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
					$cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."' OR ip.to_group_id='0' ) ";
				} else {
					$cond_user_id = " AND ip.to_group_id='0' ";
				}

				$sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
						FROM $tbl_announcement announcement, $tbl_item_property ip
						WHERE
						announcement.c_id = $course_id AND
						ip.c_id = $course_id AND
						announcement.id = ip.ref
						AND ip.tool='announcement'
						$cond_user_id
						$condition_session
						AND ip.visibility='1'
						AND announcement.session_id IN(0,".api_get_session_id().")";
			}
		}
	}

	$result		= Database::query($sql);
	$num_rows 	= Database::num_rows($result);

    // DISPLAY: NO ITEMS

	if (!isset($_GET['action']) || !in_array($_GET['action'], array('add', 'modify','view')))
	if ($num_rows == 0) {
		if ((api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) and (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath')) {
	        echo '<div id="no-data-view">';
            echo '<h2>'.get_lang('Announcements').'</h2>';
            echo Display::return_icon('valves.png', '', array(), 64);
            echo '<div class="controls">';
            echo Display::url(get_lang('AddAnnouncement'), api_get_self()."?".api_get_cidreq()."&action=add&origin=".(empty($_GET['origin'])?'':$_GET['origin']) , array('class' => 'btn'));
            echo '</div>';
            echo '</div>';
        } else {
            //echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add&origin=".(empty($_GET['origin'])?'':$_GET['origin'])."'>".Display::return_icon('new_announce.png',get_lang('AddAnnouncement'),'',ICON_SIZE_MEDIUM)."</a>";
           Display::display_warning_message(get_lang('NoAnnouncements'));
        }

	} else {
    	$iterator = 1;
    	$bottomAnnouncement = $announcement_number;

    	echo '<table width="100%" class="data_table">';
        $ths = Display::tag('th', get_lang('Title'));
        $ths .= Display::tag('th', get_lang('By') );
        $ths .= Display::tag('th', get_lang('LastUpdateDate') );
        if (api_is_allowed_to_edit(false,true) OR (api_is_course_coach() && api_is_element_in_the_session(TOOL_ANNOUNCEMENT,$myrow['id']))
                 OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
            $ths .= Display::tag('th', get_lang('Modify'));
        }

    	echo Display::tag('tr', $ths);
    	$displayed = array();

    	while ($myrow = Database::fetch_array($result, 'ASSOC')) {
    		if (!in_array($myrow['id'], $displayed)) {
    		    $sent_to_icon = '';
		       // the email icon
                if ($myrow['email_sent'] == '1') {
                    $sent_to_icon = ' '.Display::return_icon('email.gif', get_lang('AnnounceSentByEmail'));
                }

    			$title		 = $myrow['title'].$sent_to_icon;

    			/* DATE */
    			$last_post_datetime = $myrow['end_date'];

                $item_visibility = api_get_item_visibility($_course, TOOL_ANNOUNCEMENT, $myrow['id'], $session_id);
                $myrow['visibility'] = $item_visibility;

    			// the styles
    			if ($myrow['visibility'] == '0') {
    				$style='invisible';
    			} else {
    				$style = '';
    			}

    			echo "<tr>";

    		    // show attachment list
                $attachment_list = array();
                $attachment_list = AnnouncementManager::get_attachment($myrow['id']);

                $attachment_icon = '';
                if (count($attachment_list)>0) {
                    $attachment_icon = ' '.Display::return_icon('attachment.gif',get_lang('Attachment'));
                }

                /* TITLE */
    		    $title = Display::url($title.$attachment_icon, '?action=view&id='.$myrow['id']);
                echo Display::tag('td', Security::remove_XSS($title), array('class' => $style));

                $user_info		= api_get_user_info($myrow['insert_user_id']);
                $username = sprintf(get_lang("LoginX"), $user_info['username']);
                $username_span = Display::tag('span', api_get_person_name($user_info['firstName'], $user_info['lastName']), array('title'=>$username));
    			echo Display::tag('td', $username_span);
                echo Display::tag('td', api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG));

    			// we can edit if : we are the teacher OR the element belongs to the session we are coaching OR the option to allow users to edit is on
    			$modify_icons = '';
    			if (api_is_allowed_to_edit(false,true) OR (api_is_course_coach() && api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $myrow['id']))
    			     OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {

    				$modify_icons = "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=modify&id=".$myrow['id']."\">".Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL)."</a>";
    				if ($myrow['visibility']==1) {
    					$image_visibility="visible";
    					$alt_visibility=get_lang('Hide');
    				} else {
    					$image_visibility="invisible";
    					$alt_visibility=get_lang('Visible');
    				}
    				$modify_icons .=  "<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".(!empty($_GET['origin'])?Security::remove_XSS($_GET['origin']):'')."&action=showhide&id=".$myrow['id']."&sec_token=".$stok."\">".
    						Display::return_icon($image_visibility.'.png', $alt_visibility,'',ICON_SIZE_SMALL)."</a>";

    				// DISPLAY MOVE UP COMMAND only if it is not the top announcement
    				if ($iterator != 1) {
    					$modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&up=".$myrow["id"]."&sec_token=".$stok."\">".Display::return_icon('up.gif', get_lang('Up'))."</a>";
    				} else {
    				    $modify_icons .= Display::return_icon('up_na.gif', get_lang('Up'));
    				}
    				if ($iterator < $bottomAnnouncement) {
    					$modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&down=".$myrow["id"]."&sec_token=".$stok."\">".Display::return_icon('down.gif', get_lang('Down'))."</a>";
    				} else {
    				    $modify_icons .= Display::return_icon('down_na.gif', get_lang('Down'));
    				}
    			    if (api_is_allowed_to_edit(false,true)) {
                        $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete&id=".$myrow['id']."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".
                            Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).
                            "</a>";
                    }
    				$iterator ++;
    				echo Display::tag('td', $modify_icons);
    			}
    			echo "</tr>";
    		}
    		$displayed[]=$myrow['id'];
    	}	// end while
    	echo "</table>";
	}
}	// end: if ($displayAnnoucementList)


if (isset($_GET['action']) && $_GET['action'] == 'view') {
	AnnouncementManager::display_announcement($announcement_id);
}

/*		FOOTER		*/
if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
	//we are not in learnpath tool
	Display::display_footer();
}