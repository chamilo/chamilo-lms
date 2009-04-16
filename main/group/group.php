<?php // $Id: group.php 19800 2009-04-16 08:08:55Z pcool $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
==============================================================================
*	Main page for the group module.
*	This script displays the general group settings,
*	and a list of groups with buttons to view, edit...
*
*	@author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
*	@author Bert Vanderkimpen, improved self-unsubscribe for cvs
*	@author Patrick Cool, show group comment under the group name
*	@author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
*	@author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
*	@package dokeos.group
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'group';
include ('../inc/global.inc.php');
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true); 
 
$nameTools = get_lang('GroupManagement');

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');

//Create default category if it doesn't exist when group categories aren't allowed
if( api_get_setting('allow_group_categories') == 'false')
{
	$cat_table = Database::get_course_table(TABLE_GROUP_CATEGORY);
	$sql = "SELECT * FROM $cat_table WHERE id = '".DEFAULT_GROUP_CATEGORY."'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$num = Database::num_rows($res);
	if($num == 0)
	{
		api_sql_query("INSERT INTO ".$cat_table." ( id , title , description , forum_state , wiki_state, max_student , self_reg_allowed , self_unreg_allowed , groups_per_user , display_order ) VALUES ('2', '".lang2db($DefaultGroupCategory)."', '', '1', '1', '8', '0', '0', '0', '0');");
	}
}

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath')
{ //so we are not in learnpath tool
	event_access_tool(TOOL_GROUP);
	if (! $is_allowed_in_course) api_not_allowed(true);
}
Display::display_header(get_lang('Groups'));

// Tool introduction
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';
Display::display_introduction_section(TOOL_GROUP,'left');
$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

/*
 * Self-registration and unregistration
 */
if (isset ($_GET['action']))
{
	switch ($_GET['action'])
	{
		case 'self_reg' :
			if (GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $_GET['group_id']))
			{
				GroupManager :: subscribe_users($_SESSION['_user']['user_id'], $_GET['group_id']);
				Display :: display_confirmation_message(get_lang('GroupNowMember'));
			}
			break;
		case 'self_unreg' :
			if (GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $_GET['group_id']))
			{
				GroupManager :: unsubscribe_users($_SESSION['_user']['user_id'], $_GET['group_id']);
				Display :: display_confirmation_message(get_lang('StudentDeletesHimself'));
			}
			break;
		case 'show_msg' :
			Display :: display_confirmation_message($_GET['msg']);
			break;
	}
}
/*
 * Group-admin functions
 */
if (api_is_allowed_to_edit(false,true))
{

	// Post-actions
	if (isset ($_POST['action']))
	{
		switch ($_POST['action'])
		{
			case 'delete_selected' :
				if( is_array($_POST['group']))
				{
					GroupManager :: delete_groups($_POST['group']);
					Display :: display_confirmation_message(get_lang('SelectedGroupsDeleted'));
				}
				break;
			case 'empty_selected' :
				if( is_array($_POST['group']))
				{
                    GroupManager :: unsubscribe_all_users($_POST['group']);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsEmptied'));
				}
				break;
			case 'fill_selected' :
				if( is_array($_POST['group']))
				{
                    GroupManager :: fill_groups($_POST['group']);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsFilled'));
				}
				break;
		}
	}
	// Get-actions
	if (isset ($_GET['action']))
	{
		switch ($_GET['action'])
		{
			case 'swap_cat_order' :
				GroupManager :: swap_category_order($_GET['id1'], $_GET['id2']);
				Display :: display_confirmation_message(get_lang('CategoryOrderChanged'));
				break;
			case 'delete_one' :
				GroupManager :: delete_groups($_GET['id']);
				Display :: display_confirmation_message(get_lang('GroupDel'));
				break;
			case 'empty_one' :
				GroupManager :: unsubscribe_all_users($_GET['id']);
				Display :: display_confirmation_message(get_lang('GroupEmptied'));
				break;
			case 'fill_one' :
				GroupManager :: fill_groups($_GET['id']);
				Display :: display_confirmation_message(get_lang('GroupFilledGroups'));
				break;
			case 'delete_category' :
				GroupManager :: delete_category($_GET['id']);
				Display :: display_confirmation_message(get_lang('CategoryDeleted'));
				break;
		}
	}
}
	
echo '<div class="actions">';
if (api_is_allowed_to_edit(false,true))
{	
	echo Display::return_icon('groupadd.gif', get_lang('NewGroupCreate')) . '<a href="group_creation.php?'.api_get_cidreq().'">'.get_lang('NewGroupCreate').'</a>&nbsp;';	
	if( Database::count_rows(Database::get_course_table(TABLE_GROUP)) > 0) {
		//echo '<a href="group_overview.php?'.api_get_cidreq().'">'.Display::return_icon('group_view.gif').'&nbsp;'.get_lang('GroupOverview').'</a>&nbsp;';
		echo Display::return_icon('group.gif', get_lang('GroupOverview')) .'<a href="group_overview.php?'.api_get_cidreq().'">'.get_lang('GroupOverview').'</a>&nbsp;';
	}
	
	if (get_setting('allow_group_categories') == 'true') {
		echo Display::return_icon('folder_new.gif', get_lang('AddCategory')) . '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.get_lang('AddCategory').'</a>&nbsp;';
	} else {
		//echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('edit_group.gif').'&nbsp;'.get_lang('PropModify').'</a>&nbsp;';
		echo Display::return_icon('settings.gif', get_lang('PropModify')) . '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.get_lang('PropModify').'</a>&nbsp;';
	}
	echo Display::return_icon('csv.gif', get_lang('ExportAsCSV')).'<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=csv">'.get_lang('ExportAsCSV').'</a> ';
	echo Display::return_icon('excel.gif', get_lang('ExportAsXLS')).' <a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.get_lang('ExportAsXLS').'</a>';	
	//echo '<a href="group_creation.php?'.api_get_cidreq().'">'.Display::return_icon('group_add_big.gif').'&nbsp;'.get_lang('NewGroupCreate').'</a>&nbsp;';
}
$group_cats = GroupManager :: get_categories();
if (get_setting('allow_group_categories') == 'true' && count($group_cats) > 1)
{
	//echo '<p><a href="?'.api_get_cidreq().'&show_all=1">'.get_lang('ShowAll').'</a></p>';
	echo Display::return_icon('group.gif').' <a href="?'.api_get_cidreq().'&show_all=1">'.get_lang('ShowAll').'</a>';
}
echo '</div>';
/*
 * List all categories
 */
foreach ($group_cats as $index => $category)
{
	$group_list = array ();
	$in_category = false;
	if (get_setting('allow_group_categories') == 'true')
	{
		if (isset ($_GET['show_all']) || (isset ($_GET['category']) && $_GET['category'] == $category['id']))
		{
			echo '<img src="../img/shared_folder.gif" alt=""/>';
			echo ' <a href="group.php?'.api_get_cidreq().'&origin='.$_GET['origin'].'">'.$category['title'].'</a>';
			$in_category = true;
		}
		else
		{
			echo '<img src="../img/folder_document.gif" alt=""/>';
			echo ' <a href="group.php?'.api_get_cidreq().'&origin='.$_GET['origin'].'&amp;category='.$category['id'].'">'.$category['title'].'</a>';
		}
		$group_list = GroupManager :: get_group_list($category['id']);
		echo ' ('.count($group_list).' '.get_lang('ExistingGroups').')';
		if (api_is_allowed_to_edit(false,true))
		{
			echo '<a href="group_category.php?'.api_get_cidreq().'&id='.$category['id'].'"  title="'.get_lang('Edit').'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'"/></a> ';
			echo '<a href="group.php?'.api_get_cidreq().'&action=delete_category&amp;id='.$category['id'].'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;" title="'.get_lang('Delete').'"><img src="../img/delete.gif"  alt="'.get_lang('Delete').'"/></a> ';
			if ($index != 0)
			{
				echo ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index -1]['id'].'"><img src="../img/up.gif" alt=""/></a>';
			}
			if ($index != count($group_cats) - 1)
			{
				echo ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index +1]['id'].'"><img src="../img/down.gif" alt=""/></a>';
			}
		}
		echo '<p style="margin: 0px;margin-left: 50px;">'.$category['description'].'</p><p/>';
	}
	else
	{
		$group_list = GroupManager :: get_group_list();
		$in_category = true;
	}

	//if (count($group_list) > 0 && $in_category)
	if ($in_category)
	{
		$totalRegistered = 0;
		// Determine wether current user is tutor for this course
		$user_is_tutor = GroupManager :: is_tutor($_user['user_id']);
		$group_data = array ();
		foreach ($group_list as $index => $this_group)
		{
			// all the tutors of this group
			$tutorsids_of_group=GroupManager::get_subscribed_tutors($this_group['id'],true);

			// create a new table-row
			$row = array ();
			// checkbox
			if (api_is_allowed_to_edit(false,true) && count($group_list) > 1)
			{
				$row[] = $this_group['id'];
			}

			// group name
			if ((api_is_allowed_to_edit(false,true) || 
					in_array($_user['user_id'],$tutorsids_of_group) || 
					$this_group['is_member'] ||
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_FORUM) || 
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_DOCUMENTS) ||
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_CALENDAR) ||
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_ANNOUNCEMENT) ||
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_WORK) ||
					GroupManager::user_has_access($_user['user_id'],$this_group['id'],GROUP_TOOL_WIKI))
					&& !(api_is_course_coach() && intval($this_group['session_id'])!=intval($_SESSION['id_session'])))
			{				
				isset($origin)?$orig=$origin:$orig=null;
				$group_name = '<a href="group_space.php?'.api_get_cidreq().'&amp;origin='.$orig.'&amp;gidReq='.$this_group['id'].'">'.stripslashes($this_group['name']).'</a>';				
				if (!empty($_SESSION['_user']['user_id']) && !empty($this_group['id_tutor']) && $_SESSION['_user']['user_id'] == $this_group['id_tutor'])
				{
					$group_name .= ' ('.get_lang('OneMyGroups').')';
				}
				elseif ($this_group['is_member'])
				{
					$group_name .= ' ('.get_lang('MyGroup').')';
				}
				if(api_is_allowed_to_edit() && !empty($this_group['session_name']))
				{
					$group_name .= ' ('.$this_group['session_name'].')';
				}
				$row[] = $group_name.'<br/>'.stripslashes(trim($this_group['description']));
			}
			else
			{
				$row[] = $this_group['name'].'<br/>'.stripslashes(trim($this_group['description']));
			}
			// self-registration / unregistration
			if (!api_is_allowed_to_edit(false,true))
			{
				if (GroupManager :: is_self_registration_allowed($_user['user_id'], $this_group['id']))
				{
					$row[] = '<a href="group.php?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=self_reg&amp;group_id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;">'.get_lang('GroupSelfRegInf').'</a>';
				}
				elseif (GroupManager :: is_self_unregistration_allowed($_user['user_id'], $this_group['id']))
				{
					$row[] = '<a href="group.php?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=self_unreg&amp;group_id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;">'.get_lang('GroupSelfUnRegInf').'</a>';
				}
				else
				{
					$row[] = '-';
				}
			}
			// number of members in group
			$row[] = $this_group['number_of_members'];
			// max number of members in group
			$row[] = ($this_group['maximum_number_of_members'] == MEMBER_PER_GROUP_NO_LIMIT ? '-' : $this_group['maximum_number_of_members']);
			// tutor name
			$tutor_info = '';

			if(count($tutorsids_of_group)>0)
			{
				foreach($tutorsids_of_group as $tutor_id){
					$tutor = api_get_user_info($tutor_id);
					if (api_get_setting("show_email_addresses") == "true")
					{	
						$tutor_info .= Display::encrypted_mailto_link($tutor['mail'],$tutor['firstName'].' '.$tutor['lastName']).', ';								
					}
					else
					{	
						if (api_is_allowed_to_edit()=='true')
						{
							$tutor_info .= Display::encrypted_mailto_link($tutor['mail'],$tutor['firstName'].' '.$tutor['lastName']).', ';
						}
						else
						{											
							$tutor_info .= $tutor['firstName'].' '.$tutor['lastName'].', ';
						}
					}					
				}
			}
			$tutor_info = substr($tutor_info,0,strlen($tutor_info)-2);
			$row[] = $tutor_info;
			// edit-links
			if (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && intval($this_group['session_id'])!=intval($_SESSION['id_session'])))
			{
				$edit_actions = '<a href="group_edit.php?'.api_get_cidreq().'&gidReq='.$this_group['id'].'"  title="'.get_lang('Edit').'"><img src="../img/edit.gif" alt="'.get_lang('Edit').'"/></a>&nbsp;';
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=delete_one&amp;id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;" title="'.get_lang('Delete').'"><img src="../img/delete.gif" alt="'.get_lang('Delete').'"/></a>&nbsp;';
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=empty_one&amp;id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;" title="'.get_lang('EmptyGroup').'"><img src="../img/clean_group.gif" alt="'.get_lang('EmptyGroup').'"/></a>&nbsp;';
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=fill_one&amp;id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;" title="'.get_lang('FillGroup').'"><img src="../img/fill_group.gif" alt="'.get_lang('FillGroup').'"/></a>';
				$row[] = $edit_actions;
			}
			if (!empty($this_group['nbMember'])) {
				$totalRegistered = $totalRegistered + $this_group['nbMember'];	
			}
			
			$group_data[] = $row;
		} // while loop
		if (isset ($_GET['show_all']))
		{
			$paging_options = array ('per_page' => count($group_data));
		}
		else
		{
			$paging_options = array ();
		}
		$table = new SortableTableFromArrayConfig($group_data, 1);
		isset($_GET['category'])?$my_cat = $_GET['category']: $my_cat = null; 
		$table->set_additional_parameters(array('category'=>$my_cat));
		$column = 0;
		if (api_is_allowed_to_edit(false,true) and count($group_list) > 1)
		{
			$table->set_header($column++,'', false);
		}
		$table->set_header($column++,get_lang('ExistingGroups'));
		if (!api_is_allowed_to_edit(false,true)) // If self-registration allowed
		{
			$table->set_header($column++,get_lang('GroupSelfRegistration'));
		}
		$table->set_header($column++,get_lang('Registered'));
		$table->set_header($column++,get_lang('Max'));
		$table->set_header($column++,get_lang('GroupTutor'));
		if (api_is_allowed_to_edit(false,true)) // only for course administrator
		{
			$table->set_header($column++,get_lang('Modify'), false);
			$form_actions = array();
			$form_actions['delete_selected'] = get_lang('Delete');
			$form_actions['fill_selected'] = get_lang('FillGroup');
			$form_actions['empty_selected'] = get_lang('EmptyGroup');
			if (count($group_list) > 1)
			{
				$table->set_form_actions($form_actions,'group');
			}
		}
		$table->display();
	}
	/*
	elseif ($in_category)
	{
		echo get_lang('NoGroupsAvailable');
	}
	*/
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath')
{
	Display::display_footer();
}
?>
