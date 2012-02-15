<?php
/* For licensing terms, see /license.txt */

/**
*	Main page for the group module.
*	This script displays the general group settings,
*	and a list of groups with buttons to view, edit...
*
*	@author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
*	@author Bert Vanderkimpen, improved self-unsubscribe for cvs
*	@author Patrick Cool, show group comment under the group name
*	@author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
*	@author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
*	@author Isaac Flores, code cleaning and improvements
*	@package chamilo.group
*/
/*		INIT SECTION	*/
// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);

$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {
	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
			$(".actions:eq("+i+")").hide();
		}
	}
 } );
 </script>';
$nameTools = get_lang('GroupManagement');

/*	Libraries */
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$course_id = api_get_course_int_id();

// Create default category if it doesn't exist when group categories aren't allowed
if (api_get_setting('allow_group_categories') == 'false') {
	$cat_table = Database::get_course_table(TABLE_GROUP_CATEGORY);
	$sql = "SELECT * FROM $cat_table WHERE c_id = $course_id AND id = '".DEFAULT_GROUP_CATEGORY."'";
	$res = Database::query($sql);
	$num = Database::num_rows($res);
	if ($num == 0) {
		$sql = "INSERT INTO ".$cat_table." ( c_id, id , title , description , forum_state, wiki_state, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user, display_order) 
		VALUES ($course_id, '2', '".lang2db($DefaultGroupCategory)."', '', '1', '1', '8', '0', '0', '0', '0');";
		Database::query ($sql);
	}
}

/*	Header */

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
	// So we are not in learnpath tool
	event_access_tool(TOOL_GROUP);
	if (!$is_allowed_in_course) {
		api_not_allowed(true);
	}
}
Display::display_header(get_lang('Groups'));

// Tool introduction
Display::display_introduction_section(TOOL_GROUP);

/*
 * Self-registration and unregistration
 */
 $my_group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
 $my_msg	  = isset($_GET['msg']) ? Security::remove_XSS($_GET['msg']) : null;
 $my_group    = isset($_GET['group']) ? Security::remove_XSS($_POST['group']) : null;
 $my_get_id1  = isset($_GET['id1']) ? Security::remove_XSS($_GET['id1']) : null;
 $my_get_id2  = isset($_GET['id2']) ? Security::remove_XSS($_GET['id2']) : null;
 $my_get_id   = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'self_reg' :
			if (GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $my_group_id)) {
				GroupManager :: subscribe_users($_SESSION['_user']['user_id'], $my_group_id);
				Display :: display_confirmation_message(get_lang('GroupNowMember'));
			}
			break;
		case 'self_unreg' :
			if (GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $my_group_id)) {
				GroupManager :: unsubscribe_users($_SESSION['_user']['user_id'], $my_group_id);
				Display :: display_confirmation_message(get_lang('StudentDeletesHimself'));
			}
			break;
		case 'show_msg' :
			Display :: display_confirmation_message($my_msg);
			break;
	}
}

/*
 * Group-admin functions
 */
if (api_is_allowed_to_edit(false, true)) {

	// Post-actions
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'delete_selected' :
				if (is_array($_POST['group'])) {
					GroupManager :: delete_groups($my_group);
					Display :: display_confirmation_message(get_lang('SelectedGroupsDeleted'));
				}
				break;
			case 'empty_selected' :
				if (is_array($_POST['group'])) {
                    GroupManager :: unsubscribe_all_users($my_group);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsEmptied'));
				}
				break;
			case 'fill_selected' :
				if (is_array($_POST['group'])) {
                    GroupManager :: fill_groups($my_group);
                    Display :: display_confirmation_message(get_lang('SelectedGroupsFilled'));
				}
				break;
		}
	}

	// Get-actions
	if (isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'swap_cat_order':
				GroupManager :: swap_category_order($my_get_id1, $my_get_id2);
				Display :: display_confirmation_message(get_lang('CategoryOrderChanged'));
				break;
			case 'delete_one':
				GroupManager :: delete_groups($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupDel'));
				break;
			case 'empty_one':
				GroupManager :: unsubscribe_all_users($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupEmptied'));
				break;
			case 'fill_one':
				GroupManager :: fill_groups($my_get_id);
				Display :: display_confirmation_message(get_lang('GroupFilledGroups'));
				break;
			case 'delete_category':
				GroupManager :: delete_category($my_get_id);
				Display :: display_confirmation_message(get_lang('CategoryDeleted'));
				break;
		}
	}
}

echo '<div class="actions">';
if (api_is_allowed_to_edit(false, true)) {
	echo '<a href="group_creation.php?'.api_get_cidreq().'">'.Display::return_icon('new_group.png', get_lang('NewGroupCreate'),'',ICON_SIZE_MEDIUM).'</a>';
	if (CourseManager::count_rows_course_table(Database::get_course_table(TABLE_GROUP),api_get_session_id(), api_get_course_int_id()) > 0) {
		//echo '<a href="group_overview.php?'.api_get_cidreq().'">'.Display::return_icon('group_view.gif').'&nbsp;'.get_lang('GroupOverview').'</a>&nbsp;';
		echo '<a href="group_overview.php?'.api_get_cidreq().'">'.Display::return_icon('group_summary.png', get_lang('GroupOverview'),'',ICON_SIZE_MEDIUM).'</a>';
	}

	if (api_get_setting('allow_group_categories') == 'true') {
		echo '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.Display::return_icon('new_folder.png', get_lang('AddCategory'),'',ICON_SIZE_MEDIUM).'</a>';
	} else {
		//echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('edit_group.gif').'&nbsp;'.get_lang('PropModify').'</a>&nbsp;';
		echo '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.Display::return_icon('settings.png', get_lang('PropModify'),'',ICON_SIZE_MEDIUM).'</a>';
	}
	//echo Display::return_icon('csv.gif', get_lang('ExportAsCSV')).'<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=csv">'.get_lang('ExportAsCSV').'</a> ';
	echo  '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'',ICON_SIZE_MEDIUM).'</a>';
	//echo '<a href="group_creation.php?'.api_get_cidreq().'">'.Display::return_icon('group_add_big.gif').'&nbsp;'.get_lang('NewGroupCreate').'</a>&nbsp;';
	echo '<a href="../user/user.php?'.api_get_cidreq().'">'.Display::return_icon('user.png', get_lang('GoTo').' '.get_lang('Users'),'',ICON_SIZE_MEDIUM).'</a>';
	
}

$group_cats = GroupManager :: get_categories();
if (api_get_setting('allow_group_categories') == 'true' && count($group_cats) > 1) {	
	echo ' <a href="?'.api_get_cidreq().'&show_all=1">'.Display::return_icon('group.png',get_lang('ShowAll'),'',ICON_SIZE_MEDIUM).'</a>';
}
echo '</div>';

/*
 * List all categories
 */

foreach ($group_cats as $index => $category) {
	$group_list = array ();
	$in_category = false;
	if (api_get_setting('allow_group_categories') == 'true') {
		
		if (isset ($_GET['show_all']) || (isset ($_GET['category']) && $_GET['category'] == $category['id'])) {
			echo '<img src="../img/folder_group_category.gif" alt=""/>';
			echo '<a href="group.php?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'">'.$category['title'].'</a>';
			$in_category = true;
		} else {
			echo '<img src="../img/folder_document.gif" alt=""/>';
			echo '<a href="group.php?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&amp;category='.$category['id'].'">'.$category['title'].'</a>';
		}
		$group_list = GroupManager :: get_group_list($category['id']);
		echo ' ('.count($group_list).' '.get_lang('ExistingGroups').')';
		if (api_is_allowed_to_edit(false, true)) {
			echo '<a href="group_category.php?'.api_get_cidreq().'&id='.$category['id'].'"  title="'.get_lang('Edit').'">'.Display::return_icon('settings.png', get_lang('EditGroup'),'',ICON_SIZE_SMALL).'</a>';
			echo '<a href="group.php?'.api_get_cidreq().'&action=delete_category&amp;id='.$category['id'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
			if ($index != 0) {
				echo ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index -1]['id'].'">'.Display::return_icon('up.png','&nbsp;','',ICON_SIZE_SMALL).'</a>';

			}
			if ($index != count($group_cats) - 1) {
				echo ' <a href="group.php?'.api_get_cidreq().'&action=swap_cat_order&amp;id1='.$category['id'].'&amp;id2='.$group_cats[$index +1]['id'].'">'.Display::return_icon('down.png','&nbsp;','',ICON_SIZE_SMALL).'</a>';
			}
		}
		echo '<p style="margin: 0px;margin-left: 50px;">'.$category['description'].'</p><p/>';
	} else {
		$group_list = GroupManager :: get_group_list();        
		$in_category = true;
	}

	//if (count($group_list) > 0 && $in_category)
	
	if ($in_category) {
		$totalRegistered = 0;
				
		$group_data = array();
		
		foreach ($group_list as $index => $this_group) {
		    

			// Validacion when belongs to a session
			$session_img = api_get_session_image($this_group['session_id'], $_user['status']);

			// All the tutors of this group
			$tutorsids_of_group = GroupManager::get_subscribed_tutors($this_group['id'], true);

			// Create a new table-row
			$row = array ();
			// Checkbox
			if (api_is_allowed_to_edit(false,true) && count($group_list) > 1) {
				$row[] = $this_group['id'];
			}

			// Group name
			if ((api_is_allowed_to_edit(false, true) ||
					in_array($_user['user_id'], $tutorsids_of_group) ||
					$this_group['is_member'] ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_FORUM) ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_DOCUMENTS) ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_CALENDAR) ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_ANNOUNCEMENT) ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_WORK) ||
					GroupManager::user_has_access($_user['user_id'], $this_group['id'], GROUP_TOOL_WIKI))
					&& !(api_is_course_coach() && intval($this_group['session_id']) != intval($_SESSION['id_session']))) {
				$orig = isset($origin) ? $origin : null;
				$group_name = '<a href="group_space.php?'.api_get_cidreq().'&amp;origin='.$orig.'&amp;gidReq='.$this_group['id'].'">'.stripslashes($this_group['name']).'</a>';
				if (!empty($_SESSION['_user']['user_id']) && !empty($this_group['id_tutor']) && $_SESSION['_user']['user_id'] == $this_group['id_tutor']) {
					$group_name .= ' ('.get_lang('OneMyGroups').')';
				} elseif ($this_group['is_member']) {
					$group_name .= ' ('.get_lang('MyGroup').')';
				}
				if (api_is_allowed_to_edit() && !empty($this_group['session_name'])) {
					$group_name .= ' ('.$this_group['session_name'].')';
				}
				$group_name .= $session_img;
				$row[] = $group_name.'<br />'.stripslashes(trim($this_group['description']));
			} else {
				$row[] = $this_group['name'].'<br />'.stripslashes(trim($this_group['description']));
			}                      
            
            // Tutor name
            $tutor_info = '';

            if (count($tutorsids_of_group) > 0) {
                foreach ($tutorsids_of_group as $tutor_id) {
                    $tutor = api_get_user_info($tutor_id);
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $tutor['username']), ENT_QUOTES);
                    if (api_get_setting('show_email_addresses') == 'true') {
                        $tutor_info .= Display::tag('span', Display::encrypted_mailto_link($tutor['mail'], api_get_person_name($tutor['firstName'], $tutor['lastName'])), array('title'=>$username)).', ';
                    } else {
                        if (api_is_allowed_to_edit()) {
                            $tutor_info .= Display::tag('span', Display::encrypted_mailto_link($tutor['mail'], api_get_person_name($tutor['firstName'], $tutor['lastName'])), array('title'=>$username)).', ';
                        } else {
                            $tutor_info .= Display::tag('span', api_get_person_name($tutor['firstName'], $tutor['lastName']), array('title'=>$username)).', ';
                        }
                    }
                }
            }
            $tutor_info = api_substr($tutor_info, 0, api_strlen($tutor_info) - 2);
            $row[] = $tutor_info;
            
		
            // Max number of members in group
            $max_members = ($this_group['maximum_number_of_members'] == MEMBER_PER_GROUP_NO_LIMIT ? ' ' : ' / '.$this_group['maximum_number_of_members']);
            
            
			// Number of members in group
			$row[] = $this_group['number_of_members'].$max_members;
			
            // Self-registration / unregistration
            if (!api_is_allowed_to_edit(false, true)) {
                if (GroupManager :: is_self_registration_allowed($_user['user_id'], $this_group['id'])) {
                    $row[] = '<a class = "a_button gray small" href="group.php?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=self_reg&amp;group_id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset))."'".')) return false;">'.get_lang('GroupSelfRegInf').'</a>';
                } elseif (GroupManager :: is_self_unregistration_allowed($_user['user_id'], $this_group['id'])) {
                    $row[] = '<a class = "a_button gray small" href="group.php?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=self_unreg&amp;group_id='.$this_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset))."'".')) return false;">'.get_lang('GroupSelfUnRegInf').'</a>';
                } else {
                    $row[] = '-';
                }
            }

			// Edit-links
			if (api_is_allowed_to_edit(false, true)  && !(api_is_course_coach() && intval($this_group['session_id']) != intval($_SESSION['id_session']))) {
				$edit_actions = '<a href="group_edit.php?'.api_get_cidreq().'&gidReq='.$this_group['id'].'"  title="'.get_lang('Edit').'">'.Display::return_icon('edit.png', get_lang('EditGroup'),'',ICON_SIZE_SMALL).'</a>&nbsp;';								
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=empty_one&amp;id='.$this_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('EmptyGroup').'">'.Display::return_icon('clean.png',get_lang('EmptyGroup'),'',ICON_SIZE_SMALL).'</a>&nbsp;';				
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=fill_one&amp;id='.$this_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('FillGroup').'">'.Display::return_icon('fill.png',get_lang('FillGroup'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
				$edit_actions .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&category='.$category['id'].'&amp;action=delete_one&amp;id='.$this_group['id'].'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;" title="'.get_lang('Delete').'">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
				$row[] = $edit_actions;
			}
			if (!empty($this_group['nbMember'])) {
				$totalRegistered = $totalRegistered + $this_group['nbMember'];
			}

			$group_data[] = $row;
		} // while loop
		if (isset ($_GET['show_all'])) {
			$paging_options = array('per_page' => count($group_data));
		} else {
			$paging_options = array ();
		}
		$table = new SortableTableFromArrayConfig($group_data, 1);
		$my_cat = isset($_GET['category']) ? Security::remove_XSS($_GET['category']) : null;
		$table->set_additional_parameters(array('category' => $my_cat));
		$column = 0;
		if (api_is_allowed_to_edit(false, true) and count($group_list) > 1) {
			$table->set_header($column++, '', false);
		}
		$table->set_header($column++, get_lang('Groups'));
        $table->set_header($column++, get_lang('GroupTutor'));
        		
		$table->set_header($column++, get_lang('Registered'), false);
		
		if (!api_is_allowed_to_edit(false, true)) { // If self-registration allowed
            $table->set_header($column++, get_lang('GroupSelfRegistration'), false);
        }
		//$table->set_header($column++, get_lang('MaximumOfParticipants'));
		
		if (api_is_allowed_to_edit(false, true)) { // Only for course administrator
			$table->set_header($column++, get_lang('Modify'), false);
			$form_actions = array();
			$form_actions['delete_selected'] = get_lang('Delete');
			$form_actions['fill_selected'] = get_lang('FillGroup');
			$form_actions['empty_selected'] = get_lang('EmptyGroup');
			if (count($group_list) > 1) {
				$table->set_form_actions($form_actions, 'group');
			}
		}
		$table->display();
	}
	/*
	elseif ($in_category) {
		echo get_lang('NoGroupsAvailable');
	}
	*/
}

/*	FOOTER */

if (!isset ($_GET['origin']) || $_GET['origin'] != 'learnpath') {
	Display::display_footer();
}
