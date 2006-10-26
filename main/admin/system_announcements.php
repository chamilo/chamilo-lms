<?php
// $Id: system_announcements.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) 2005 Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This page allows the administrator to manage the system announcements.
*	@package dokeos.admin
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = array ('admin', 'agenda');
$cidReset = true;
include ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
api_protect_admin_script();
if(empty($_GET['lang']))
{
    $_GET['lang']=$_SESSION['user_language_choice'];
}
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
$tool_name = get_lang('SystemAnnouncements');
//$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('PlatformAdmin'));

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if($_GET['action'] != 'add' && $_GET['action'] != 'edit'){

	$form = new FormValidator('languagechange','GET');
	$language_list = api_get_languages();
	$language_list_with_keys = array();
	for($i=0; $i<count($language_list['name']) ; $i++) {
		$language_list_with_keys[$language_list['folder'][$i]] = $language_list['name'][$i];
	}
	$form->addElement('select', 'lang',get_lang('Language'),$language_list_with_keys);
	$form->addElement('submit', 'submit', get_lang('Ok'));
	$form->display();
	echo '<a href="?action=add">'.get_lang('AddAnnouncement').'</a>';
}
$form_action = "";
$show_announcement_list = true;
if (isset ($_GET['action']) && $_GET['action'] == 'make_visible')
{
	switch ($_GET['person'])
	{
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
if (isset ($_GET['action']) && $_GET['action'] == 'make_invisible')
{
	switch ($_GET['person'])
	{
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
if (isset ($_POST['action']))
{
	$action_todo = true;
}

// Delete an announcement
if (isset ($_GET['action']) && $_GET['action'] == 'delete')
{
	SystemAnnouncementManager :: delete_announcement($_GET['id']);
	Display :: display_normal_message(get_lang('AnnouncementDeleted'));
}
// Delete selected announcements
if (isset ($_POST['action']) && $_POST['action'] == 'delete_selected')
{
	foreach($_POST['id'] as $index => $id)
	{
		SystemAnnouncementManager :: delete_announcement($id);	
	}
	Display :: display_normal_message(get_lang('AnnouncementDeleted'));
	$action_todo = false;
}
// Add an announcement
if (isset ($_GET['action']) && $_GET['action'] == 'add')
{
	$values['action'] = 'add';
	// Set default time window: NOW -> NEXT WEEK
	$values['start'] = date('Y-m-d H:i:s');
	$values['end'] = date('Y-m-d H:i:s',time() + (7 * 24 * 60 * 60));
	$action_todo = true;
}
// Edit an announcement
if (isset ($_GET['action']) && $_GET['action'] == 'edit')
{
	$announcement = SystemAnnouncementManager :: get_announcement($_GET['id']);
	$values['id'] = $announcement->id;
	$values['title'] = $announcement->title;
	$values['content'] = $announcement->content;
	$values['start'] = $announcement->date_start;
	$values['end'] = $announcement->date_end;
	$values['visible_teacher'] = $announcement->visible_teacher;
	$values['visible_student'] = $announcement->visible_student ;
	$values['visible_guest'] = $announcement->visible_guest ;
	$values['lang'] = $announcement->lang;
	$values['action'] = 'edit';
	$action_todo = true;
}

if ($action_todo)
{
	$form = new FormValidator('system_announcement');
	$form->add_textfield('title', get_lang('Title'));
	$language_list = api_get_languages();
	$language_list_with_keys = array();
	for($i=0; $i<count($language_list['name']) ; $i++) {
		$language_list_with_keys[$language_list['folder'][$i]] = $language_list['name'][$i];
	}
	$form->addElement('select', 'lang',get_lang('Language'),$language_list_with_keys);
	$form->add_html_editor('content', get_lang('Content'));
	$form->add_timewindow('start','end',get_lang('StartTimeWindow'),get_lang('EndTimeWindow'));
	$form->addElement('checkbox', 'visible_teacher', get_lang('Visible'), get_lang('Teacher'));
	$form->addElement('checkbox', 'visible_student', null, get_lang('Student'));
	$form->addElement('checkbox', 'visible_guest', null, get_lang('Guest'));
	$form->addElement('hidden', 'action');
	$form->addElement('hidden', 'id');
	$form->addElement('submit', 'submit', get_lang('Ok'));
	$form->setDefaults($values);
	if($form->validate())
	{
		$values = $form->exportValues();
		if( !isset($values['visible_teacher']))
		{
			$values['visible_teacher'] = false;	
		}
		if( !isset($values['visible_student']))
		{
			$values['visible_student'] = false;	
		}
		if( !isset($values['visible_guest']))
		{
			$values['visible_guest'] = false;	
		}
		switch($values['action'])
		{
			case 'add':
				if(SystemAnnouncementManager::add_announcement($values['title'],$values['content'],$values['start'],$values['end'],$values['visible_teacher'],$values['visible_student'],$values['visible_guest'], $values['lang']))
					Display :: display_normal_message(get_lang('AnnouncementAdded'));
				else {
					$show_announcement_list = false;
					$form->display();
				}
				break;
			case 'edit':
				if (SystemAnnouncementManager::update_announcement($values['id'],$values['title'],$values['content'],$values['start'],$values['end'],$values['visible_teacher'],$values['visible_student'],$values['visible_guest'], $values['lang']))
					Display :: display_normal_message(get_lang('AnnouncementUpdated'));
				else {
					$show_announcement_list = false;
					$form->display();
				}
				break;
			default:	
				break;
		}
		$show_announcement_list = true;
	}
	else
	{
		$form->display();
		$show_announcement_list = false;
	}
}
if ($show_announcement_list)
{
	$announcements = SystemAnnouncementManager :: get_all_announcements($_GET['lang']);
	$announcement_data = array ();
	foreach ($announcements as $index => $announcement)
	{
		$row = array ();
		$row[] = $announcement->id;
		$row[] = "<img src=\"../img/". ($announcement->visible ? 'visible.gif' : 'invisible.gif')."\"/>";
		$row[] = $announcement->date_start;
		$row[] = $announcement->date_end;
		$row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_TEACHER."&amp;action=". ($announcement->visible_teacher ? 'make_invisible' : 'make_visible')."\"><img src=\"../img/". ($announcement->visible_teacher  ? 'visible.gif' : 'invisible.gif')."\" border=\"0\"></a>";
		$row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_STUDENT."&amp;action=". ($announcement->visible_student  ? 'make_invisible' : 'make_visible')."\"><img src=\"../img/". ($announcement->visible_student ? 'visible.gif' : 'invisible.gif')."\" border=\"0\"></a>";
		$row[] = "<a href=\"?id=".$announcement->id."&amp;person=".VISIBLE_GUEST."&amp;action=". ($announcement->visible_guest ? 'make_invisible' : 'make_visible')."\"><img src=\"../img/". ($announcement->visible_guest  ? 'visible.gif' : 'invisible.gif')."\" border=\"0\"></a>";
		$row[] = $announcement->title;
		$row[] = "<a href=\"?action=edit&id=".$announcement->id."\"><img src=\"../img/edit.gif\" border=\"0\"/></a> <a href=\"?action=delete&id=".$announcement->id."\"  onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."')) return false;\"><img src=\"../img/delete.gif\" border=\"0\"/></a>";
		$announcement_data[] = $row;
	}
	$table = new SortableTableFromArray($announcement_data);
	$table->set_header(0,'',false);
	$table->set_header(1,'', false);
	$table->set_header(2,get_lang('StartTimeWindow'));
	$table->set_header(3,get_lang('EndTimeWindow'));
	$table->set_header(4,get_lang('Teacher'));
	$table->set_header(5,get_lang('Student'));
	$table->set_header(6,get_lang('Guest'));
	$table->set_header(7,get_lang('Title'));
	$table->set_header(8,get_lang('Modify'), false);
	$form_actions = array();
	$form_actions['delete_selected'] = get_lang('Delete');
	$table->set_form_actions($form_actions);
	$table->display();
}
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>