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
 * multiple functions
 */

// name of the language file that needs to be included

// use anonymous mode when accessing this course tool
$use_anonymous = true;

// setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';

/*	Sessions */

$ctok = Security::get_existing_token();
$stok = Security::get_token();

$current_course_tool  = TOOL_ANNOUNCEMENT;
$this_section = SECTION_COURSES;
$nameTools = get_lang('ToolAnnouncement');

/* ACCESS RIGHTS */
api_protect_course_script(true);

// Configuration settings
$display_announcement_list = true;
$display_form = false;
$display_title_list = true;

// Maximum title messages to display
$maximum 	= '12';

// Length of the titles
$length 	= '36';

// Database Table Definitions
$tbl_courses = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_sessions = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);

$course_id = api_get_course_int_id();
$_course = api_get_course_info_by_id($course_id);
$group_id = api_get_group_id();

api_protect_course_group(GroupManager::GROUP_TOOL_ANNOUNCEMENT);

/*	Tracking	*/
Event::event_access_tool(TOOL_ANNOUNCEMENT);

$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : null;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'list';

$announcement_number = AnnouncementManager::getNumberAnnouncements();

$homeUrl = api_get_self().'?action=list&'.api_get_cidreq();
$content = null;

switch ($action) {
    case 'move':
        /* Move announcement up/down */
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

            $announcementInfo = AnnouncementManager::get_by_id($course_id, $thisAnnouncementId);

            $sql = "SELECT DISTINCT announcement.id, announcement.display_order
                FROM $tbl_announcement announcement,
				$tbl_item_property itemproperty
				WHERE
				    announcement.c_id =  $course_id AND
				    itemproperty.c_id =  $course_id AND
					itemproperty.ref = announcement.id AND
                    itemproperty.tool = '".TOOL_ANNOUNCEMENT."'  AND
                    itemproperty.visibility <> 2
                ORDER BY display_order $sortDirection";
            $result = Database::query($sql);
            $thisAnnouncementOrderFound = false;
            $thisAnnouncementOrder = null;

            while (list($announcementId, $announcementOrder) = Database::fetch_row($result)) {
                if ($thisAnnouncementOrderFound) {
                    $nextAnnouncementId = $announcementId;
                    $nextAnnouncementOrder = $announcementOrder;
                    $sql = "UPDATE $tbl_announcement SET display_order = '$nextAnnouncementOrder'
                            WHERE c_id = $course_id AND id = $thisAnnouncementId";
                    Database::query($sql);
                    $sql = "UPDATE $tbl_announcement  SET display_order = '$thisAnnouncementOrder'
                            WHERE c_id = $course_id AND id =  $nextAnnouncementId";

                    Database::query($sql);
                    break;
                }
                // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
                if ($announcementId == $thisAnnouncementId) {
                    $thisAnnouncementOrder = $announcementOrder;
                    $thisAnnouncementOrderFound = true;
                }
            }
            Display::addFlash(Display::return_message(get_lang('AnnouncementMoved')));
            header('Location: '.$homeUrl);
            exit;
        }
        break;
    case 'view':
        $content = AnnouncementManager::display_announcement($announcement_id);
        break;
    case 'list':
        $content = AnnouncementManager::getAnnouncements($stok, $announcement_number);
        break;
    case 'delete':
        /* Delete announcement */
        $id = intval($_GET['id']);
        if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false, true) == false) {
            api_not_allowed();
        }

        if (!api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $id)) {
            // tooledit : visibility = 2 : only visible for platform administrator
            if ($ctok == $_GET['sec_token']) {
                AnnouncementManager::delete_announcement($_course, $id);
                Display::addFlash(Display::return_message(get_lang('AnnouncementDeleted')));
            }
        }
        header('Location: '.$homeUrl);
        exit;
        break;
    case 'delete_all':
        if (api_is_allowed_to_edit()) {
            AnnouncementManager::delete_all_announcements($_course);
            Display::addFlash(Display::return_message(get_lang('AnnouncementDeletedAll')));
            header('Location: '.$homeUrl);
            exit;
        }
        break;
    case 'delete_attachment':
        $id = $_GET['id_attach'];
        if ($ctok == $_GET['sec_token']) {
            if (api_is_allowed_to_edit()) {
                AnnouncementManager::delete_announcement_attachment_file($id);
            }
        }
        header('Location: '.$homeUrl);
        exit;
        break;
    case 'showhide':
        if (!isset($_GET['isStudentView']) || $_GET['isStudentView'] != 'false') {
            if (isset($_GET['id']) AND $_GET['id']) {
                if (api_get_session_id() != 0 &&
                    api_is_allowed_to_session_edit(false, true) == false) {
                    api_not_allowed();
                }

                if (!api_is_course_coach() ||
                    api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $_GET['id'])
                ) {
                    if ($ctok == $_GET['sec_token']) {
                        AnnouncementManager::change_visibility_announcement(
                            $_course,
                            $_GET['id']
                        );
                        Display::addFlash(Display::return_message(get_lang('VisibilityChanged')));
                        header('Location: '.$homeUrl);
                        exit;
                    }
                }
            }
        }
        break;
    case 'add':
    case 'modify':
        if (api_get_session_id() != 0 &&
            api_is_allowed_to_session_edit(false, true) == false
        ) {
            api_not_allowed(true);
        }

        // DISPLAY ADD ANNOUNCEMENT COMMAND
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $url = api_get_self().'?action='.$action.'&id=' . $id . '&' . api_get_cidreq();

        $form = new FormValidator(
            'f1',
            'post',
            $url,
            null,
            array('enctype' => 'multipart/form-data')
        );

        if (empty($id)) {
            $form_name = get_lang('AddAnnouncement');
        } else {
            $form_name = get_lang('ModifyAnnouncement');
        }
        $form->addElement('header', $form_name);

        if (empty($group_id)) {
            if (isset($_GET['remind_inactive'])) {
                $email_ann = '1';
                $content_to_modify = sprintf(
                    get_lang('RemindInactiveLearnersMailContent'),
                    api_get_setting('siteName'),
                    7
                );
                $title_to_modify = sprintf(
                    get_lang('RemindInactiveLearnersMailSubject'),
                    api_get_setting('siteName')
                );
            } elseif (isset($_GET['remindallinactives']) && $_GET['remindallinactives'] == 'true') {
                // we want to remind inactive users. The $_GET['since'] parameter
                // determines which users have to be warned (i.e the users who have been inactive for x days or more
                $since = isset($_GET['since']) ? intval($_GET['since']) : 6;
                // getting the users who have to be reminded
                $to = Tracking:: getInactiveStudentsInCourse(
                    api_get_course_int_id(),
                    $since,
                    api_get_session_id()
                );
                // setting the variables for the form elements: the users who need to receive the message
                foreach ($to as &$user) {
                    $user = 'USER:' . $user;
                }
                // setting the variables for the form elements: the message has to be sent by email
                $email_ann = '1';
                // setting the variables for the form elements: the title of the email
                $title_to_modify = sprintf(
                    get_lang('RemindInactiveLearnersMailSubject'),
                    api_get_setting('siteName')
                );
                // setting the variables for the form elements: the message of the email
                $content_to_modify = sprintf(
                    get_lang('RemindInactiveLearnersMailContent'),
                    api_get_setting('siteName'),
                    $since
                );
                // when we want to remind the users who have never been active
                // then we have a different subject and content for the announcement
                if ($_GET['since'] == 'never') {
                    $title_to_modify = sprintf(
                        get_lang('RemindInactiveLearnersMailSubject'),
                        api_get_setting('siteName')
                    );
                    $content_to_modify = get_lang(
                        'YourAccountIsActiveYouCanLoginAndCheckYourCourses'
                    );
                }
            }

            $element = CourseManager::addUserGroupMultiSelect($form, array());
            $form->setRequired($element);

            if (!isset($announcement_to_modify)) {
                $announcement_to_modify = '';
            }

            $form->addElement(
                'checkbox',
                'email_ann',
                null,
                get_lang('EmailOption')
            );
        } else {
            if (!isset($announcement_to_modify)) {
                $announcement_to_modify = "";
            }
            $element = CourseManager::addGroupMultiSelect($form, $group_id, array());
            $form->setRequired($element);

            $form->addElement(
                'checkbox',
                'email_ann',
                null,
                get_lang('EmailOption')
            );
        }

        $announcementInfo = AnnouncementManager::get_by_id($course_id, $id);

        if (isset($announcementInfo) && !empty($announcementInfo)) {
            $to = AnnouncementManager::load_edit_users("announcement", $id);

            $defaults = array(
                'title' => $announcementInfo['title'],
                'content' => $announcementInfo['content'],
                'id' => $announcementInfo['id'],
                'users' => $to
            );
        } else {
            $defaults = array();
        }

        $form->addElement('text', 'title', get_lang('EmailTitle'));
        $form->addElement('hidden', 'id');
        $form->addHtmlEditor(
            'content',
            get_lang('Description'),
            false,
            false,
            array('ToolbarSet' => 'Announcements')
        );

        $form->addElement('file', 'user_upload', get_lang('AddAnAttachment'));
        $form->addElement('textarea', 'file_comment', get_lang('FileComment'));
        $form->addElement('hidden', 'sec_token', $stok);

        if (api_get_session_id() == 0) {
            $form->addCheckBox('send_to_users_in_session', null, get_lang('SendToUsersInSessions'));
        }

        $form->addCheckBox('send_to_hrm_users', null, get_lang('SendAnnouncementCopyToDRH'));

        $form->addButtonSave(get_lang('ButtonPublishAnnouncement'));
        $form->setDefaults($defaults);

        if ($form->validate()) {
            $data = $form->getSubmitValues();

            $sendToUsersInSession = isset($data['send_to_users_in_session']) ? true : false;

            if (isset($id) && $id) {
                // there is an Id => the announcement already exists => update mode
                if ($ctok == $_POST['sec_token']) {
                    $file_comment = $_POST['file_comment'];
                    $file = $_FILES['user_upload'];

                    AnnouncementManager::edit_announcement(
                        $id,
                        $data['title'],
                        $data['content'],
                        $data['users'],
                        $file,
                        $file_comment,
                        $sendToUsersInSession
                    );

                    /*		MAIL FUNCTION	*/
                    if (isset($_POST['email_ann']) && empty($_POST['onlyThoseMails'])) {
                        AnnouncementManager::send_email(
                            $id,
                            $sendToUsersInSession,
                            isset($data['send_to_hrm_users'])
                        );
                    }

                    Display::addFlash(
                        Display::return_message(
                            get_lang('AnnouncementModified'),
                            'success'
                        )
                    );
                    header('Location: '.$homeUrl);
                    exit;
                }
            } else {
                // Insert mode
                if ($ctok == $_POST['sec_token']) {
                    $file = $_FILES['user_upload'];
                    $file_comment = $data['file_comment'];

                    if (empty($group_id)) {
                        $insert_id = AnnouncementManager::add_announcement(
                            $data['title'],
                            $data['content'],
                            $data['users'],
                            $file,
                            $file_comment,
                            $sendToUsersInSession
                        );
                    } else {
                        $insert_id = AnnouncementManager::add_group_announcement(
                            $data['title'],
                            $data['content'],
                            array('GROUP:' . $group_id),
                            $data['users'],
                            $file,
                            $file_comment,
                            $sendToUsersInSession
                        );
                    }
                    Display::addFlash(
                        Display::return_message(
                            get_lang('AnnouncementAdded'),
                            'success'
                        )
                    );

                    /* MAIL FUNCTION */
                    if (isset($data['email_ann']) && $data['email_ann']) {
                        AnnouncementManager::send_email(
                            $insert_id,
                            $sendToUsersInSession
                        );
                    }
                    header('Location: '.$homeUrl);
                    exit;

                } // end condition token
            }
        }

        $content = $form->returnForm();
        break;
}

if (!empty($_GET['remind_inactive'])) {
    $to[] = 'USER:'.intval($_GET['remind_inactive']);
}

if (!empty($group_id)) {
    $group_properties  = GroupManager :: get_group_properties($group_id);
    $interbreadcrumb[] = array("url" => "../group/group.php?".api_get_cidreq(), "name" => get_lang('Groups'));
    $interbreadcrumb[] = array("url"=>"../group/group_space.php?".api_get_cidreq(), "name"=> get_lang('GroupSpace').' '.$group_properties['name']);
}

if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
    //we are not in the learning path
    Display::display_header($nameTools,get_lang('Announcements'));
}

// Tool introduction
if (empty($_GET['origin']) || $_GET['origin'] !== 'learnpath') {
    Display::display_introduction_section(TOOL_ANNOUNCEMENT);
}

// Actions
$show_actions = false;
if ((api_is_allowed_to_edit(false,true) ||
    (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) &&
    (empty($_GET['origin']) || $_GET['origin'] !== 'learnpath')
) {
    echo '<div class="actions">';
    if (in_array($action, array('add', 'modify','view'))) {
        echo "<a href='".api_get_self()."?".api_get_cidreq()."&origin=".$origin."'>".
            Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM)."</a>";
    } else {
        echo "<a href='".api_get_self()."?".api_get_cidreq()."&action=add&origin=".$origin."'>".
            Display::return_icon('new_announce.png',get_lang('AddAnnouncement'),'',ICON_SIZE_MEDIUM)."</a>";
    }
    $show_actions = true;
} else {
    if (in_array($action, array('view'))) {
        echo '<div class="actions">';
        echo "<a href='".api_get_self()."?".api_get_cidreq()."&origin=".$origin."'>".
            Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM)."</a>";
        echo '</div>';
    }
}

if (api_is_allowed_to_edit() && $announcement_number > 1) {
    if (api_get_group_id() == 0 ) {
        if (!$show_actions)
            echo '<div class="actions">';
        if (!isset($_GET['action'])) {
            echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete_all\" onclick=\"javascript:if(!confirm('".get_lang("ConfirmYourChoice")."')) return false;\">".
                Display::return_icon('delete_announce.png',get_lang('AnnouncementDeleteAll'),'',ICON_SIZE_MEDIUM)."</a>";
        }
    }
}

if ($show_actions)
    echo '</div>';

Display::showFlash();

echo $content;

if (empty($_GET['origin']) or $_GET['origin'] !== 'learnpath') {
    //we are not in learnpath tool
    Display::display_footer();
}
