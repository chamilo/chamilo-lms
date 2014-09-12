<?php
/* For licensing terms, see /license.txt */

/**
 * @author Frederik Vermeire <frederik.vermeire@pandora.be>, UGent Internship
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: code cleaning
 * @author Julio Montoya <gugli100@gmail.com>, MORE code cleaning 2013
 *
 * @abstract The task of the internship was to integrate the 'send messages to specific users' with the
 *              Announcements tool and also add the resource linker here. The database also needed refactoring
 *              as there was no title field (the title was merged into the content field)
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

$language_file = array('announcements', 'group', 'survey', 'document');

// use anonymous mode when accessing this course tool
$use_anonymous = true;

// setting the global file that gets the general configuration, the databases, the languages, ...
//require_once '../inc/global.inc.php';

$ctok = Security::getCurrentToken();
$stok = Security::get_token();

$current_course_tool = TOOL_ANNOUNCEMENT;
$this_section = SECTION_COURSES;
$nameTools = get_lang('ToolAnnouncement');

/* ACCESS RIGHTS */
api_protect_course_script(true);

// Configuration settings
$display_announcement_list = true;
$display_form = false;
$display_title_list = true;

// Maximum title messages to display
$maximum = '12';

// Database Table Definitions
$tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

/* 	Libraries	 */

$course_id = api_get_course_int_id();

/* 	Tracking	 */
Event::event_access_tool(TOOL_ANNOUNCEMENT);

if (!empty($_POST['To'])) {
    if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
        api_not_allowed(true);
    }
    $display_form = true;
}

$origin = empty($_GET['origin']) ? '' : Security::remove_XSS($_GET['origin']);

// display the form
if (((!empty($_GET['action']) && $_GET['action'] == 'add') && $_GET['origin'] == "") || (!empty($_GET['action']) && $_GET['action'] == 'edit') || !empty($_POST['To'])) {
    if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
        api_not_allowed(true);
    }
    $display_form = true;
}

//$htmlHeadXtra[] = AnnouncementManager::to_javascript();

$email_ann = null;
$group_id = api_get_group_id();

if (!empty($group_id)) {
    $group_properties = GroupManager::get_group_properties($group_id);
    $interbreadcrumb[] = array("url" => "../group/group.php", "name" => get_lang('Groups'));
    $interbreadcrumb[] = array(
        "url" => "../group/group_space.php?gidReq=".$group_id,
        "name" => get_lang('GroupSpace').' '.$group_properties['name']
    );
}

$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$message = null;

if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
    // we are not in the learning path
    Display::display_header($nameTools, get_lang('Announcements'));
}

if (AnnouncementManager::user_can_edit_announcement()) {
    /* Change visibility of announcement */
    // change visibility -> studentview -> course manager view
    if (!isset($_GET['isStudentView']) || $_GET['isStudentView'] != 'false') {
        if (isset($_GET['id']) and $_GET['id'] and isset($_GET['action']) and $_GET['action'] == "showhide") {
            if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
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

    /* Delete announcement */
    if (!empty($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
            api_not_allowed();
        }

        if (api_is_platform_admin() || !api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
            // tooledit : visibility = 2 : only visible for platform administrator
            if ($ctok == $_GET['sec_token']) {
                AnnouncementManager::delete_announcement($_course, $id);

                $id = null;
                $emailTitle = null;
                $newContent = null;
                $message = get_lang('AnnouncementDeleted');
            }
        }
    }

    // Delete attachment file
    if (isset($_GET['action']) && $_GET['action'] == 'delete_attachment') {
        $id = $_GET['id_attach'];
        if ($ctok == $_GET['sec_token']) {
            if (api_is_allowed_to_edit()) {
                AnnouncementManager::delete_announcement_attachment_file($id);
            }
        }
    }

    /*  Delete all announcements */
    if (!empty($_GET['action']) and $_GET['action'] == 'delete_all') {
        if (api_is_allowed_to_edit()) {
            AnnouncementManager::delete_all_announcements($_course);
            $id = null;
            $emailTitle = null;
            $newContent = null;
            $message = get_lang('AnnouncementDeletedAll');
        }
    }

    /* Edit announcement */
    if (!empty($_GET['action']) and $_GET['action'] == 'modify' and isset($_GET['id'])) {

        if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
            api_not_allowed();
        }

        $display_form = true;

        // RETRIEVE THE CONTENT OF THE ANNOUNCEMENT TO MODIFY
        $id = intval($_GET['id']);

        if (api_is_platform_admin() || !api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
            $announcementInfo = AnnouncementManager::get_by_id($course_id, $id);
            if ($announcementInfo) {
                $announcement_to_modify = $announcementInfo['id'];
                $content_to_modify = $announcementInfo['content'];
                $title_to_modify = $announcementInfo['title'];
                $to = AnnouncementManager::load_edit_users("announcement", $announcement_to_modify);
                $display_announcement_list = false;
            }
        }
    }

    /* Move announcement up/down  */

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
            $sortDirection = 'ASC';
        }
        $my_sql = "SELECT announcement.id, announcement.display_order ".
            "FROM $tbl_announcement announcement, ".
            "$tbl_item_property itemproperty ".
            "WHERE
				announcement.c_id =  $course_id AND
				itemproperty.c_id =  $course_id AND
					itemproperty.ref=announcement.id ".
            "AND itemproperty.tool='".TOOL_ANNOUNCEMENT."' ".
            "AND itemproperty.visibility<>2 ".
            "ORDER BY display_order $sortDirection";
        $result = Database::query($my_sql);

        $thisAnnouncementOrderFound = false;
        $thisAnnouncementOrder = 1;
        while (list ($announcementId, $announcementOrder) = Database::fetch_row($result)) {
            // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER. COMMIT ORDER SWAP ON THE DB

            if ($thisAnnouncementOrderFound) {
                $nextAnnouncementId = $announcementId;
                $nextAnnouncementOrder = $announcementOrder;
                $sql = "UPDATE $tbl_announcement SET display_order = '$nextAnnouncementOrder'
                WHERE c_id = $course_id AND id = '$thisAnnouncementId'";
                Database::query($sql);

                $sql = "UPDATE $tbl_announcement  SET display_order = '$thisAnnouncementOrder'
                WHERE c_id = $course_id AND id = '$nextAnnouncementId.'";
                Database::query($sql);
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

    /* Submit announcement */

    $submitAnnouncement = isset($_POST['submitAnnouncement']) ? $_POST['submitAnnouncement'] : 0;
    $id = 0;

    if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);
    }

    if ($submitAnnouncement) {
        $selected_form = isset($_POST['users']) ? $_POST['users'] : null;
        $sendEmail = isset($_POST['email_ann']) ? $_POST['email_ann'] : null;

        if (isset($id) && $id) {
            // there is an Id => the announcement already exists => update mode
            if ($ctok == $_POST['sec_token']) {
                $file_comment = $_POST['file_comment'];
                $file = $_FILES['user_upload'];
                AnnouncementManager::edit_announcement(
                    $id,
                    $_POST['emailTitle'],
                    $_POST['newContent'],
                    $selected_form,
                    $file,
                    $file_comment,
                    $sendEmail
                );
                $message = get_lang('AnnouncementModified');
            }
        } else {
            // Insert mode
            //if (1) {
            if ($ctok == $_REQUEST['sec_token']) {
                $file = $_FILES['user_upload'];
                $file_comment = $_POST['file_comment'];

                if (!empty($group_id)) {
                    $insert_id = AnnouncementManager::add_group_announcement(
                        $_POST['emailTitle'],
                        $_POST['newContent'],
                        array('GROUP:'.$group_id),
                        $selected_form,
                        $file,
                        $file_comment,
                        $sendEmail
                    );
                } else {
                    $insert_id = AnnouncementManager::add_announcement(
                        $_POST['emailTitle'],
                        $_POST['newContent'],
                        $selected_form,
                        $file,
                        $file_comment,
                        $sendEmail
                    );
                }
                $message = get_lang('AnnouncementAdded');
            }
        }
    }
}

/* Tool introduction  */

if (empty($_GET['origin']) || $_GET['origin'] !== 'learnpath') {
    Display::display_introduction_section(TOOL_ANNOUNCEMENT);
}

/* DISPLAY LEFT COLUMN */

//condition for the session
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id, true, true);

if (api_is_allowed_to_edit(false, true)) {
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
        $group_memberships = GroupManager::get_group_ids($_course['real_id'], api_get_user_id());

        if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
            if (api_get_group_id() == 0) {
                $cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id(
                )."' OR ( ip.to_user_id='".api_get_user_id()."'".
                    "OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))) ";
            } else {
                $cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id()."'
                OR ip.to_group_id IN (0, ".api_get_group_id()."))";
            }
        } else {
            if (api_get_group_id() == 0) {
                $cond_user_id = " AND ( ip.to_user_id='".api_get_user_id()."'".
                    "OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).")) ";
            } else {
                $cond_user_id = " AND (
                    (ip.to_user_id='".api_get_user_id()."' AND ip.to_group_id = ".api_get_group_id().") OR
                    ip.to_group_id IN (".api_get_group_id().") AND ip.to_user_id = 0 ) ";
            }
        }

        // the user is member of several groups => display personal announcements AND his group announcements AND the general announcements
        if (is_array($group_memberships) && count($group_memberships) > 0) {
            $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                FROM $tbl_announcement announcement, $tbl_item_property ip
                WHERE
                    announcement.c_id = $course_id AND
                    ip.c_id = $course_id AND
                    announcement.id = ip.ref AND
                    ip.tool='announcement' AND
                    ip.visibility='1'
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
                    $cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id(
                    )."' OR ( ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0')) ";
                } else {
                    $cond_user_id = " AND ( ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0') ";
                }
                $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
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
                $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
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
if (AnnouncementManager::user_can_edit_announcement()) {
    echo '<div class="actions">';
    if (isset($_GET['action']) && in_array($_GET['action'], array('add', 'modify', 'view'))) {
        echo "<a href='".api_get_self()."?".api_get_cidreq(
        )."&origin=".$origin."'>".Display::return_icon(
            'back.png',
            get_lang('Back'),
            '',
            ICON_SIZE_MEDIUM
        )."</a>";
    } else {
        echo "<a href='".api_get_self()."?".api_get_cidreq(
        )."&action=add&origin=".$origin."'>".Display::return_icon(
            'new_announce.png',
            get_lang('AddAnnouncement'),
            '',
            ICON_SIZE_MEDIUM
        )."</a>";
    }
    $show_actions = true;
} else {
    if (isset($_GET['action']) && in_array($_GET['action'], array('view'))) {
        echo '<div class="actions">';
        echo "<a href='".api_get_self()."?".api_get_cidreq(
        )."&origin=".$origin."'>".Display::return_icon(
            'back.png',
            get_lang('Back'),
            '',
            ICON_SIZE_MEDIUM
        )."</a>";
        echo '</div>';
    }
}

if (api_is_allowed_to_edit() && $announcement_number > 1) {
    if ($group_id == 0) {
        if (!$show_actions) {
            echo '<div class="actions">';
        }
        if (!isset($_GET['action']) or !in_array($_GET['action'], array('add', 'modify', 'view'))) {
            echo "<a href=\"".api_get_self()."?".api_get_cidreq(
            )."&action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang(
                "ConfirmYourChoice"
            )."')) return false;\">".Display::return_icon(
                'delete_announce.png',
                get_lang('AnnouncementDeleteAll'),
                '',
                ICON_SIZE_MEDIUM
            )."</a>";
        }
    }
}

if ($show_actions) {
    echo '</div>';
}

//	ANNOUNCEMENTS LIST

if ($message) {
    Display::display_confirmation_message($message);
    $display_announcement_list = true;
    $display_form = false;
}
if (!empty($error_message)) {
    Display::display_error_message($error_message);
    $display_announcement_list = false;
    $display_form = true;
}

/*  DISPLAY FORM  */

if ($display_form) {

    // DISPLAY ADD ANNOUNCEMENT COMMAND
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $url = api_get_self().'?id='.$id.'&'.api_get_cidreq();
    $form = new FormValidator('f1', 'post', $url, array('enctype' => 'multipart/form-data'));

    if (empty($_GET['id'])) {
        $form_name = get_lang('AddAnnouncement');
    } else {
        $form_name = get_lang('ModifyAnnouncement');
    }

    $form->addElement('header', $form_name);

    if (empty($group_id)) {

        if (isset($_GET['remind_inactive'])) {
            $email_ann = '1';
            $_SESSION['select_groupusers'] = "show";
            $content_to_modify = sprintf(get_lang('RemindInactiveLearnersMailContent'), api_get_setting('platform.site_name'), 7);
            $title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('platform.site_name'));
        } elseif (isset($_GET['remindallinactives']) && $_GET['remindallinactives'] == 'true') {
            // we want to remind inactive users. The $_GET['since'] parameter determines which users have to be warned (i.e the users who have been inactive for x days or more
            $since = isset($_GET['since']) ? intval($_GET['since']) : 6;
            // getting the users who have to be reminded
            $to = Tracking :: get_inactives_students_in_course(api_get_course_int_id(), $since, api_get_session_id());
            // setting the variables for the form elements: the users who need to receive the message
            foreach ($to as &$user) {
                $user = 'USER:'.$user;
            }
            // setting the variables for the form elements: the message has to be sent by email
            $email_ann = '1';
            // setting the variables for the form elements: the title of the email
            $title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('platform.site_name'));
            // setting the variables for the form elements: the message of the email
            $content_to_modify = sprintf(
                get_lang('RemindInactiveLearnersMailContent'),
                api_get_setting('platform.site_name'),
                $since
            );
            // when we want to remind the users who have never been active then we have a different subject and content for the announcement
            if ($_GET['since'] == 'never') {
                $title_to_modify = sprintf(get_lang('RemindInactiveLearnersMailSubject'), api_get_setting('platform.site_name'));
                $content_to_modify = get_lang('YourAccountIsActiveYouCanLoginAndCheckYourCourses');
            }
        }

        CourseManager::addUserGroupMultiSelect($form, array());

        if (!isset($announcement_to_modify)) {
            $announcement_to_modify = '';
        }
        $form->addElement('checkbox', 'email_ann', null, get_lang('EmailOption'));
    } else {

        if (!isset($announcement_to_modify)) {
            $announcement_to_modify = "";
        }
        CourseManager::addGroupMultiSelect($form, $group_id, array());
        $form->addElement('checkbox', 'email_ann', null, get_lang('EmailOption'));
    }

    if (isset($announcementInfo) && !empty($announcementInfo)) {
        $defaults = array(
            'emailTitle' => $title_to_modify,
            'newContent' => $content_to_modify,
            'id' => $announcement_to_modify,
            'users' => $to
        );
    } else {
        $defaults = array();
    }

    $form->addElement('text', 'emailTitle', get_lang('EmailTitle'));
    $form->addElement('hidden', 'id');
    $form->add_html_editor('newContent', get_lang('Description'));

    $form->addElement('file', 'user_upload', get_lang('AddAnAttachment'));
    $form->addElement('textarea', 'file_comment', get_lang('FileComment'));
    $form->addElement('hidden', 'submitAnnouncement', 'OK');
    $form->addElement('hidden', 'sec_token', $stok);
    $form->addElement('button', 'submit', get_lang('ButtonPublishAnnouncement'));

    $form->setDefaults($defaults);

    echo $form->return_form();
}

/*
  DISPLAY ANNOUNCEMENT LIST
 */

if ($display_announcement_list) {

    $user_id = api_get_user_id();
    $group_id = api_get_group_id();

    $group_memberships = GroupManager::get_group_ids($course_id, api_get_user_id());

    if (api_is_allowed_to_edit(false, true) OR (api_get_course_setting(
        'allow_user_edit_announcement'
    ) && !api_is_anonymous())
    ) {
        // A.1. you are a course admin with a USER filter
        // => see only the messages of this specific user + the messages of the group (s)he is member of.
        if (!empty($_SESSION['user'])) {

            if (is_array($group_memberships) && count($group_memberships) > 0) {
                $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE 	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
							announcement.id = ip.ref AND
							ip.tool			= 'announcement' AND
							(ip.to_user_id = $user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
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
        } elseif (api_get_group_id() != 0) {
            // A.2. you are a course admin with a GROUP filter
            // => see only the messages of this specific group
            $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
				FROM $tbl_announcement announcement, $tbl_item_property ip
				WHERE	announcement.c_id = $course_id AND
						ip.c_id = $course_id AND
						announcement.id = ip.ref
						AND ip.tool='announcement'
						AND ip.visibility<>'2'
						AND (ip.to_group_id = $group_id OR ip.to_group_id='0')
						$condition_session
				GROUP BY ip.ref
				ORDER BY display_order DESC";
        } else {

            // A.3 you are a course admin without any group or user filter
            // A.3.a you are a course admin without user or group filter but WITH studentview
            // => see all the messages of all the users and groups without editing possibilities

            if (isset($isStudentView) and $isStudentView == "true") {
                $sql = "SELECT
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
                // A.3.a you are a course admin without user or group filter and WITHOUT studentview (= the normal course admin view)
                // => see all the messages of all the users and groups with editing possibilities
                $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
					FROM $tbl_announcement announcement, $tbl_item_property ip
					WHERE 	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
							announcement.id = ip.ref AND
                            ip.tool='announcement' AND
                            (ip.visibility='0' or ip.visibility='1') AND
                            to_group_id = 0
							$condition_session
					GROUP BY ip.ref
					ORDER BY display_order DESC";
            }
        }
    } else {
        //STUDENT

        if (is_array($group_memberships) && count($group_memberships) > 0) {
            if (AnnouncementManager::user_can_edit_announcement()) {
                if (api_get_group_id() == 0) {
                    //No group
                    $cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id(
                    )."' OR ( ip.to_user_id='".$_user['user_id']."'".
                        " OR ip.to_group_id IN (0, ".implode(", ", $group_memberships)."))) ";
                } else {
                    $cond_user_id = " AND (
                        ip.lastedit_user_id = '".api_get_user_id()."' OR
                        ip.to_group_id IN (".api_get_group_id().")
                    )";
                }
            } else {
                if (api_get_group_id() == 0) {
                    $cond_user_id = " AND (ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(
                        ", ",
                        $group_memberships
                    ).")) ";
                } else {
                    $cond_user_id = " AND (
                            (ip.to_user_id = $user_id AND ip.to_group_id = ".api_get_group_id().") OR
                            (ip.to_group_id IN (".api_get_group_id().") AND ip.to_user_id = 0 )
                    )";
                }
            }

            $visibility_condition = " ip.visibility='1'";
            if (GroupManager::is_tutor_of_group(api_get_user_id(), $group_id)) {
                $visibility_condition = " ip.visibility IN ('0', '1') ";
            }

            $sql = "SELECT announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.insert_date
    				FROM $tbl_announcement announcement, $tbl_item_property ip
    				WHERE	announcement.c_id = $course_id AND
							ip.c_id = $course_id AND
	        				announcement.id = ip.ref
	        				AND ip.tool='announcement'
	        				$cond_user_id
	        				$condition_session AND $visibility_condition
    				ORDER BY display_order DESC";
        } else {
            if ($_user['user_id']) {
                if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
                    $cond_user_id = " AND (ip.lastedit_user_id = '".api_get_user_id(
                    )."' OR (ip.to_user_id='".$_user['user_id']."' OR ip.to_group_id='0')) ";
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

    $result = Database::query($sql);
    $num_rows = Database::num_rows($result);

    // DISPLAY: NO ITEMS

    if (!isset($_GET['action']) || !in_array($_GET['action'], array('add', 'modify', 'view'))) {
        if ($num_rows == 0) {
            if ((api_is_allowed_to_edit(false, true) OR (api_get_course_setting(
                'allow_user_edit_announcement'
            ) && !api_is_anonymous())) and (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath')
            ) {
                echo '<div id="no-data-view">';
                echo '<h2>'.get_lang('Announcements').'</h2>';
                echo Display::return_icon('valves.png', '', array(), 64);
                echo '<div class="controls">';
                echo Display::url(
                    get_lang('AddAnnouncement'),
                    api_get_self()."?".api_get_cidreq(
                    )."&action=add&origin=".$origin,
                    array('class' => 'btn')
                );
                echo '</div>';
                echo '</div>';
            } else {
                Display::display_warning_message(get_lang('NoAnnouncements'));
            }
        } else {
            $iterator = 1;
            $bottomAnnouncement = $announcement_number;

            echo '<table width="100%" class="data_table">';
            $ths = Display::tag('th', get_lang('Title'));
            $ths .= Display::tag('th', get_lang('By'));
            $ths .= Display::tag('th', get_lang('LastUpdateDate'));
            if (api_is_allowed_to_edit(false, true) OR (api_is_course_coach() && api_is_element_in_the_session(
                TOOL_ANNOUNCEMENT,
                $announcementInfo['id']
            ))
                OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
            ) {
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

                    $title = $myrow['title'].$sent_to_icon;

                    $item_visibility = api_get_item_visibility(
                        $_course,
                        TOOL_ANNOUNCEMENT,
                        $myrow['id'],
                        $session_id
                    );
                    $myrow['visibility'] = $item_visibility;

                    // the styles
                    if ($myrow['visibility'] == '0') {
                        $style = 'invisible';
                    } else {
                        $style = '';
                    }

                    echo "<tr>";

                    // show attachment list
                    $attachment_list = AnnouncementManager::get_attachment($myrow['id']);

                    $attachment_icon = '';
                    if (count($attachment_list) > 0) {
                        $attachment_icon = ' '.Display::return_icon('attachment.gif', get_lang('Attachment'));
                    }

                    /* TITLE */
                    $title = Display::url($title.$attachment_icon, '?action=view&id='.$myrow['id']);
                    echo Display::tag('td', Security::remove_XSS($title), array('class' => $style));

                    $user_info = api_get_user_info($myrow['insert_user_id']);
                    $username = sprintf(get_lang("LoginX"), $user_info['username']);
                    $username_span = Display::tag(
                        'span',
                        api_get_person_name($user_info['firstName'], $user_info['lastName']),
                        array('title' => $username)
                    );
                    echo Display::tag('td', $username_span);
                    echo Display::tag(
                        'td',
                        api_convert_and_format_date($myrow['insert_date'], DATE_TIME_FORMAT_LONG)
                    );

                    // we can edit if : we are the teacher OR the element belongs to the session we are coaching OR the option to allow users to edit is on
                    $modify_icons = '';
                    if (api_is_allowed_to_edit(false, true) OR (api_is_course_coach() && api_is_element_in_the_session(
                        TOOL_ANNOUNCEMENT,
                        $myrow['id']
                    ))
                        OR (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
                    ) {

                        $modify_icons = "<a href=\"".api_get_self()."?".api_get_cidreq(
                        )."&action=modify&id=".$myrow['id']."\">".Display::return_icon(
                            'edit.png',
                            get_lang('Edit'),
                            '',
                            ICON_SIZE_SMALL
                        )."</a>";
                        if ($myrow['visibility'] == 1) {
                            $image_visibility = "visible";
                            $alt_visibility = get_lang('Hide');
                        } else {
                            $image_visibility = "invisible";
                            $alt_visibility = get_lang('Visible');
                        }
                        $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                        )."&origin=".$origin."&action=showhide&id=".$myrow['id']."&sec_token=".$stok."\">".
                            Display::return_icon(
                                $image_visibility.'.png',
                                $alt_visibility,
                                '',
                                ICON_SIZE_SMALL
                            )."</a>";

                        // DISPLAY MOVE UP COMMAND only if it is not the top announcement
                        if ($iterator != 1) {
                            $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                            )."&up=".$myrow["id"]."&sec_token=".$stok."\">".Display::return_icon(
                                'up.gif',
                                get_lang('Up')
                            )."</a>";
                        } else {
                            $modify_icons .= Display::return_icon('up_na.gif', get_lang('Up'));
                        }
                        if ($iterator < $bottomAnnouncement) {
                            $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                            )."&down=".$myrow["id"]."&sec_token=".$stok."\">".Display::return_icon(
                                'down.gif',
                                get_lang('Down')
                            )."</a>";
                        } else {
                            $modify_icons .= Display::return_icon('down_na.gif', get_lang('Down'));
                        }
                        if (api_is_allowed_to_edit(false, true)) {
                            $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq(
                            )."&action=delete&id=".$myrow['id']."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(
                                api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)
                            )."')) return false;\">".
                                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).
                                "</a>";
                        }
                        $iterator++;
                        echo Display::tag('td', $modify_icons);
                    }
                    echo "</tr>";
                }
                $displayed[] = $myrow['id'];
            } // end while
            echo "</table>";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'view') {
    AnnouncementManager::display_announcement($announcement_id);
}

/* 		FOOTER		 */
if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
    //we are not in learnpath tool
    Display::display_footer();
}
