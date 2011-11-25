<?php
/* For licensing terms, see /license.txt */

/**
 *	This page allows the administrator to manage the system announcements.
 *	@package chamilo.admin.announcement
 */

/* INIT SECTION */

// Language files that need to be included.
$language_file = array('admin', 'agenda');

// Resetting the course id.
$cidReset = true;

// Including the global initialization file.
require_once '../inc/global.inc.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';
require_once api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

// Setting the section (for the tabs).
$this_section=SECTION_PLATFORM_ADMIN;
$_SESSION['this_section']=$this_section;

// Access restrictions
api_protect_admin_script(true);

// Setting breadcrumbs.
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

$tool_name = get_lang('SystemAnnouncements');

if (empty($_GET['lang'])) {
    $_GET['lang'] = $_SESSION['user_language_choice'];
}

// Displaying the header.
Display :: display_header($tool_name);

/* MAIN CODE */

if ($_GET['action'] != 'add' && $_GET['action'] != 'edit') {
    echo '<div class="actions">';
    echo '<a href="?action=add">'.Display::return_icon('add.png', get_lang('langAddAnnouncement'), array(), 32).'</a>';
    echo '</div>';
}
$form_action = '';
$show_announcement_list = true;
if (isset ($_GET['action']) && $_GET['action'] == 'make_visible') {
    switch ($_GET['person']) {
        case VISIBLE_TEACHER :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_TEACHER, true);
            break;
        case VISIBLE_STUDENT :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_STUDENT, true);
            break;
        case VISIBLE_GUEST :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_GUEST, true);
            break;
    }
}

if (isset ($_GET['action']) && $_GET['action'] == 'make_invisible') {
    switch ($_GET['person']) {
        case VISIBLE_TEACHER :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_TEACHER, false);
            break;
        case VISIBLE_STUDENT :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_STUDENT, false);
            break;
        case VISIBLE_GUEST :
            SystemAnnouncementManager :: set_visibility($_GET['id'], VISIBLE_GUEST, false);
            break;
    }
}

// Form was posted?
if (isset ($_POST['action'])) {
    $action_todo = true;
}

// Delete an announcement.
if (isset ($_GET['action']) && $_GET['action'] == 'delete') {
    SystemAnnouncementManager :: delete_announcement($_GET['id']);
    Display :: display_confirmation_message(get_lang('AnnouncementDeleted'));
}

// Delete selected announcements.
if (isset ($_POST['action']) && $_POST['action'] == 'delete_selected') {
    foreach($_POST['id'] as $index => $id) {
        SystemAnnouncementManager :: delete_announcement($id);
    }
    Display :: display_confirmation_message(get_lang('AnnouncementDeleted'));
    $action_todo = false;
}

// Add an announcement.
if (isset ($_GET['action']) && $_GET['action'] == 'add') {
    $values['action'] = 'add';
    // Set default time window: NOW -> NEXT WEEK
    $values['start'] = date('Y-m-d H:i:s',api_strtotime(api_get_local_time()));
    $values['end']   = date('Y-m-d H:i:s',api_strtotime(api_get_local_time()) + (7 * 24 * 60 * 60));
    $action_todo = true;
}

// Edit an announcement.
if (isset ($_GET['action']) && $_GET['action'] == 'edit') {

    $announcement 				= SystemAnnouncementManager :: get_announcement($_GET['id']);
    $values['id'] 				= $announcement->id;
    $values['title'] 			= $announcement->title;
    $values['content']			= $announcement->content;
    $values['start'] 			= api_get_local_time($announcement->date_start);
    $values['end'] 				= api_get_local_time($announcement->date_end);
    $values['visible_teacher'] 	= $announcement->visible_teacher;
    $values['visible_student'] 	= $announcement->visible_student ;
    $values['visible_guest'] 	= $announcement->visible_guest ;
    $values['lang'] 			= $announcement->lang;
    $values['action']			= 'edit';
  $groups = SystemAnnouncementManager :: get_announcement_groups($announcement->id);
  $values['group'] = isset($groups[0]['group_id']) ? $groups[0]['group_id'] : 0;
    $action_todo = true;
}

if ($action_todo) {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'add') {
        $form_title = get_lang('AddNews');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
        $form_title = get_lang('EditNews');
    }

    $form = new FormValidator('system_announcement');
    $form->addElement('header', '', $form_title);
    $form->add_textfield('title', get_lang('Title'), true, array('size'=>'60px'));
    $language_list = api_get_languages();
    $language_list_with_keys = array();
    $language_list_with_keys['all'] = get_lang('All');
    for($i=0; $i<count($language_list['name']) ; $i++) {
        $language_list_with_keys[$language_list['folder'][$i]] = $language_list['name'][$i];
    }

    $form->addElement('select', 'lang',get_lang('Language'), $language_list_with_keys);
    if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
        $form->addElement('textarea', 'content', get_lang('Content'));
    } else {
        $form->add_html_editor('content', get_lang('Content'), true, false, array('ToolbarSet' => 'PortalNews', 'Width' => '100%', 'Height' => '300'));
    }
    $form->add_timewindow('start','end',get_lang('StartTimeWindow'),get_lang('EndTimeWindow'));
    $form->addElement('checkbox', 'visible_teacher', get_lang('Visible'), get_lang('Teacher'));
    $form->addElement('checkbox', 'visible_student', null, get_lang('Student'));
    $form->addElement('checkbox', 'visible_guest', null, get_lang('Guest'));
    
    $form->addElement('hidden', 'id');

  $group_list = GroupPortalManager::get_groups_list();
  $group_list[0]  = get_lang('AllGroups');
	$form->addElement('select', 'group',get_lang('AnnouncementForGroup'),$group_list);
  $values['group'] = isset($values['group']) ? $values['group'] : '0';

    $form->addElement('checkbox', 'send_mail', get_lang('SendMail'));    

    if (isset($_REQUEST['action']) && $_REQUEST['action']=='add') {
        $form->addElement('checkbox', 'add_to_calendar', get_lang('AddToCalendar'));
        $text=get_lang('AddNews');
        $class='add';
        $form->addElement('hidden', 'action','add');

    } elseif (isset($_REQUEST['action']) && $_REQUEST['action']=='edit') {
        $text=get_lang('EditNews');
        $class='save';
        $form->addElement('hidden', 'action','edit');
    }

    $form->addElement('style_submit_button', 'submit', $text,'class="'.$class.'"');
    if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
        $values['content'] = WCAG_Rendering::HTML_to_text($values['content']);
    }
    $form->setDefaults($values);
    if ($form->validate()) {
        $values = $form->exportValues();
        if ( !isset($values['visible_teacher'])) {
            $values['visible_teacher'] = false;
        }
        if ( !isset($values['visible_student'])) {
            $values['visible_student'] = false;
        }
        if ( !isset($values['visible_guest'])) {
            $values['visible_guest'] = false;
        }
        if ($values['lang'] == 'all') {
            $values['lang'] = null;
        }
        if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
            $values['content'] = WCAG_Rendering::text_to_HTML($values['content']);
        }
        switch ($values['action']) {
            case 'add':
          $announcement_id = SystemAnnouncementManager::add_announcement($values['title'],$values['content'],$values['start'],$values['end'],$values['visible_teacher'],$values['visible_student'],$values['visible_guest'], $values['lang'],$values['send_mail'],  $values['add_to_calendar']);
          if ($announcement_id !== false )  {
          SystemAnnouncementManager::announcement_for_groups($announcement_id, array($values['group']));
                    Display :: display_confirmation_message(get_lang('AnnouncementAdded'));
                } else {
                    $show_announcement_list = false;
                    $form->display();
                }
                break;
            case 'edit':
                if (SystemAnnouncementManager::update_announcement($values['id'], $values['title'], $values['content'], $values['start'], $values['end'], $values['visible_teacher'], $values['visible_student'], $values['visible_guest'], $values['lang'], $values['send_mail'])) {
          SystemAnnouncementManager::announcement_for_groups($values['id'], array($values['group']));
                    Display :: display_confirmation_message(get_lang('AnnouncementUpdated'));
                } else {
                    $show_announcement_list = false;
                    $form->display();
                }
                break;
            default:
                break;
        }
        $show_announcement_list = true;
    } else {
        if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
            echo '<div class="WCAG-form">';
        }
        $form->display();
        if (api_get_setting('wcag_anysurfer_public_pages') == 'true') {
            echo '</div>';
        }
        $show_announcement_list = false;
    }
}

if ($show_announcement_list) {
    $announcements = SystemAnnouncementManager :: get_all_announcements();
    $announcement_data = array ();
    foreach ($announcements as $index => $announcement) {
        $row = array();
        $row[] = $announcement->id;
        $row[] = Display::return_icon(($announcement->visible ? 'accept.png' : 'exclamation.png'), ($announcement->visible ? get_lang('AnnouncementAvailable') : get_lang('AnnouncementNotAvailable')));
        $row[] = $announcement->title;        
        $row[] = api_convert_and_format_date($announcement->date_start);
        $row[] = api_convert_and_format_date($announcement->date_end);
        $row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_TEACHER."&amp;action=". ($announcement->visible_teacher ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_teacher  ? 'visible.gif' : 'invisible.gif'), get_lang('ShowOrHide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_STUDENT."&amp;action=". ($announcement->visible_student  ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_student  ? 'visible.gif' : 'invisible.gif'), get_lang('ShowOrHide'))."</a>";
        $row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_GUEST."&amp;action=". ($announcement->visible_guest ? 'make_invisible' : 'make_visible')."\">".Display::return_icon(($announcement->visible_guest  ? 'visible.gif' : 'invisible.gif'), get_lang('ShowOrHide'))."</a>";
        
        $row[] = $announcement->lang;
        $row[] = "<a href=\"?action=edit&id=".$announcement->id."\">".Display::return_icon('edit.png', get_lang('Edit'), array(), 22)."</a> <a href=\"?action=delete&id=".$announcement->id."\"  onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), array(), 22)."</a>";
        $announcement_data[] = $row;
    }
    $table = new SortableTableFromArray($announcement_data);
    $table->set_header(0, '', false);
    $table->set_header(1, get_lang('Active'));
    $table->set_header(2, get_lang('Title'));
    $table->set_header(3, get_lang('StartTimeWindow'));
    $table->set_header(4, get_lang('EndTimeWindow'));
    $table->set_header(5, get_lang('Teacher'));
    $table->set_header(6, get_lang('Student'));
    $table->set_header(7, get_lang('Guest'));
    
    $table->set_header(8, get_lang('Language'));
    $table->set_header(9, get_lang('Modify'), false, 'width="50px"');
    $form_actions = array();
    $form_actions['delete_selected'] = get_lang('Delete');
    $table->set_form_actions($form_actions);
    $table->display();
}
/* FOOTER */
Display :: display_footer();
