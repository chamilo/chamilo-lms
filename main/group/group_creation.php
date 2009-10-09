<?php
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Hugues Peeters
	Copyright (c) Thomas Depraetere
	Copyright (c) Christophe Gesche
	Copyright (c) Roan Embrechts
	Copyright (c) Bart Mollet

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
===============================================================================
*/
/**
==============================================================================
*	@package dokeos.group
==============================================================================
*/
/*
===============================================================================
       DOKEOS INIT SETTINGS
===============================================================================
*/
// name of the language file that needs to be included
$language_file = "group";
require_once ('../inc/global.inc.php');
$this_section = SECTION_COURSES;

require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
/*
--------------------------------------
        Create the groups
--------------------------------------
*/
if (isset ($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'create_groups' :
			$groups = array ();

			for ($i = 0; $i < $_POST['number_of_groups']; $i ++)
			{
				$group1['name'] = api_strlen($_POST['group_'.$i.'_name']) == 0 ? get_lang('Group').' '.$i : $_POST['group_'.$i.'_name'] ;
				$group1['category'] = isset($_POST['group_'.$i.'_category'])?$_POST['group_'.$i.'_category']:null;
				$group1['tutor'] = isset($_POST['group_'.$i.'_tutor'])?$_POST['group_'.$i.'_tutor']:null;
				$group1['places'] = isset($_POST['group_'.$i.'_places'])?$_POST['group_'.$i.'_places']:null;
				$groups[] = $group1;
			}

			foreach ($groups as $index => $group)
			{
				if (!empty($_POST['same_tutor']))
				{
					$group['tutor'] = $_POST['group_0_tutor'];
				}
				if (!empty($_POST['same_places']))
				{
					$group['places'] = $_POST['group_0_places'];
				}
				if (api_get_setting('allow_group_categories') == 'false')
				{
					$group['category'] = DEFAULT_GROUP_CATEGORY;
				}
				elseif ($_POST['same_category'])
				{
					$group['category'] = $_POST['group_0_category'];
				}
				GroupManager :: create_group(strip_tags($group['name']),$group['category'],$group['tutor'] , $group['places']);
			}
			$msg = urlencode(count($groups).' '.get_lang('GroupsAdded'));
			header('Location: group.php?action=show_msg&msg='.$msg);
			break;
		case 'create_virtual_groups' :
			$ids = GroupManager :: create_groups_from_virtual_courses();
			$msg = urlencode(count($ids).' '.get_lang('GroupsAdded'));
			header('Location: group.php?action=show_msg&msg='.$msg);
			break;
		case 'create_subgroups' :
			GroupManager :: create_subgroups($_POST['base_group'], $_POST['number_of_groups']);
			$msg = urlencode($_POST['number_of_groups'].' '.get_lang('GroupsAdded'));
			header('Location: group.php?action=show_msg&msg='.$msg);
			break;
		case 'create_class_groups' :
			$ids = GroupManager :: create_class_groups($_POST['group_category']);
			$msg = urlencode(count($ids).' '.get_lang('GroupsAdded'));
			header('Location: group.php?action=show_msg&msg='.$msg);
			break;
	}
}
$nameTools = get_lang('GroupCreation');
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang('Groups'));
Display :: display_header($nameTools, "Group");

if (!is_allowed_to_edit())
{
	api_not_allowed();
}
/*
===============================================================================
       MAIN TOOL CODE
===============================================================================
*/
/*
--------------------------------------
        Show group-settings-form
--------------------------------------
*/
elseif (isset ($_POST['number_of_groups']))
{
	if (!is_numeric($_POST['number_of_groups']) || intval($_POST['number_of_groups']) < 1)
	{
		Display :: display_error_message(get_lang('PleaseEnterValidNumber').'<br/><br/><a href="group_creation.php?'.api_get_cidreq().'">&laquo; '.get_lang('Back').'</a>',false);
	}
	else
	{
	$number_of_groups = intval($_POST['number_of_groups']);
	if ($number_of_groups > 1)
	{
		
?>
	<script type="text/javascript">
	<!--
	var number_of_groups = <?php echo $number_of_groups; ?>;
	function switch_state(key)
	{
		for( i=1; i<number_of_groups; i++)
		{
			element = document.getElementById(key+'_'+i);
			element.disabled = !element.disabled;
			disabled = element.disabled;
		}
		ref = document.getElementById(key+'_0');
		if( disabled )
		{
			ref.addEventListener("change", copy, false);
		}
		else
		{
			ref.removeEventListener("change", copy, false);
		}
		copy_value(key);
	}
	function copy(e)
	{
		key = e.currentTarget.id;
		var re = new RegExp ('_0', '') ;
		var key = key.replace(re, '') ;
		copy_value(key);
	}
	function copy_value(key)
	{
		ref = document.getElementById(key+'_0');
		for( i=1; i<number_of_groups; i++)
		{
			element = document.getElementById(key+'_'+i);
			element.value = ref.value;
		}
	}
	-->
	</script>
	<?php


	}
	$group_categories = GroupManager :: get_categories();
	$group_id = GroupManager :: get_number_of_groups() + 1;
	/*$tutors = GroupManager :: get_all_tutors();
	$tutor_options[0] = get_lang('GroupNoTutor');
	foreach ($tutors as $index => $tutor)
	{
		$tutor_options[$tutor['user_id']] = api_get_person_name($tutor['firstname'], $tutor['lastname']);
	}
	$cat_options = array ();
	*/
	foreach ($group_categories as $index => $category)
	{
		// Don't allow new groups in the virtual course category!
		if ($category['id'] != VIRTUAL_COURSE_CATEGORY)
		{
			$cat_options[$category['id']] = $category['title'];
		}
	}
	$form = new FormValidator('create_groups_step2');

	// Modify the default templates
	$renderer = & $form->defaultRenderer();
	$form_template = "<form {attributes}>\n<div>\n<table>\n{content}\n</table>\n</div>\n</form>";
	$renderer->setFormTemplate($form_template);
	$element_template = <<<EOT
	<tr>
		<td>
			<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
		</td>
		<td>
			<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	{element}
		</td>
	</tr>

EOT;
	$renderer->setElementTemplate($element_template);
	$form->addElement('header', '', $nameTools);

	$form->addElement('hidden', 'action');
	$form->addElement('hidden', 'number_of_groups');
	$defaults = array ();
	// Table heading
	$group_el = array ();
	$group_el[] = & $form->createElement('static', null, null, '<b>'.get_lang('GroupName').'</b>');
	if (api_get_setting('allow_group_categories') == 'true')
	{
		$group_el[] = & $form->createElement('static', null, null, '<b>'.get_lang('GroupCategory').'</b>');
	}
	//$group_el[] = & $form->createElement('static', null, null, '<b>'.get_lang('GroupTutor').'</b>');
	$group_el[] = & $form->createElement('static', null, null, '<b>'.get_lang('GroupPlacesThis').'</b>');
	$form->addGroup($group_el, 'groups', null, "\n</td>\n<td>\n", false);
	// Checkboxes
	if ($_POST['number_of_groups'] > 1)
	{
		$group_el = array ();
		$group_el[] = & $form->createElement('static', null, null, ' ');
		if (api_get_setting('allow_group_categories') == 'true')
		{
			$group_el[] = & $form->createElement('checkbox', 'same_category', null, get_lang('SameForAll'), array ('onclick' => "javascript:switch_state('category')"));
		}
		//$group_el[] = & $form->createElement('checkbox', 'same_tutor', null, get_lang('SameForAll'), array ('onclick' => "javascript:switch_state('tutor')"));
		$group_el[] = & $form->createElement('checkbox', 'same_places', null, get_lang('SameForAll'), array ('onclick' => "javascript:switch_state('places')"));
		$form->addGroup($group_el, 'groups', null, '</td><td>', false);
	}
	// Properties for all groups
	for ($group_number = 0; $group_number < $_POST['number_of_groups']; $group_number ++)
	{
		$group_el = array ();
		$group_el[] = & $form->createElement('text', 'group_'.$group_number.'_name');
		if (api_get_setting('allow_group_categories') == 'true')
		{
			$group_el[] = & $form->createElement('select', 'group_'.$group_number.'_category', null, $cat_options, array ('id' => 'category_'.$group_number));
		}
		//$group_el[] = & $form->createElement('select', 'group_'.$group_number.'_tutor', null, $tutor_options, array ('id' => 'tutor_'.$group_number));
		$group_el[] = & $form->createElement('text', 'group_'.$group_number.'_places', null, array ('size' => 3, 'id' => 'places_'.$group_number));
		

		if($_POST['number_of_groups']<10000)
		{
			if ($group_id<10)
			{
				$prev='000';
			}
			elseif ($group_id<100)
			{
				$prev='00';
			}
			elseif ($group_id<1000)
			{
				$prev='0';
			}
			else
			{
				$prev='';
			}
		}		
				
		$defaults['group_'.$group_number.'_name'] = get_lang('GroupSingle').' '.$prev.$group_id ++;		
		
		$form->addGroup($group_el, 'group_'.$group_number, null, '</td><td>', false);
	}
	$defaults['action'] = 'create_groups';
	$defaults['number_of_groups'] = $_POST['number_of_groups'];
	$form->setDefaults($defaults);
	$form->addElement('style_submit_button', 'submit', get_lang('CreateGroup'), 'class="save"');
	$form->display();
	}
}
else
{
	/*
	 * Show form to generate new groups
	 */
	$categories = GroupManager :: get_categories();
	//echo '<blockquote>';
	if (count($categories) > 1 || isset ($categories[0]) && $categories[0]['id'] != VIRTUAL_COURSE_CATEGORY)
	{
		$create_groups_form = new FormValidator('create_groups');
		$create_groups_form->addElement('header', '', $nameTools);
		$group_el = array ();
		$group_el[] = & $create_groups_form->createElement('static', null, null, get_lang('Create'));
		$group_el[] = & $create_groups_form->createElement('text', 'number_of_groups', null, array ('size' => 3));
		$group_el[] = & $create_groups_form->createElement('static', null, null, get_lang('NewGroups'));
		$group_el[] = & $create_groups_form->createElement('style_submit_button', 'submit', get_lang('ProceedToCreateGroup'), 'class="save"');
		$create_groups_form->addGroup($group_el, 'create_groups', null, ' ', false);
		$defaults = array ();
		$defaults['number_of_groups'] = 1;
		$create_groups_form->setDefaults($defaults);
		$create_groups_form->display();
	}
	else
	{
		echo get_lang('NoCategoriesDefined');
	}
	//echo '</blockquote>';
	/*
	 * Show form to generate groups from virtual courses
	 */
	$virtual_courses = CourseManager :: get_virtual_courses_linked_to_real_course($_course['sysCode']);
	if (count($virtual_courses) > 0)
	{
		echo '<b>'.get_lang('CreateGroupsFromVirtualCourses').'</b>';
		echo '<blockquote>';
		echo get_lang('CreateGroupsFromVirtualCoursesInfo');
		$create_virtual_groups_form = new FormValidator('create_virtual_groups');
		$create_virtual_groups_form->addElement('hidden', 'action');
		$create_virtual_groups_form->addElement('submit', 'submit', get_lang('Ok'));
		$create_virtual_groups_form->setDefaults(array ('action' => 'create_virtual_groups'));
		$create_virtual_groups_form->display();
		echo '</blockquote>';
	}
	/*
	 * Show form to generate subgroups
	 */
	if (api_get_setting('allow_group_categories') == 'true' && count(GroupManager :: get_group_list()) > 0)
	{
		$base_group_options = array ();
		$groups = GroupManager :: get_group_list();
		foreach ($groups as $index => $group)
		{
			$number_of_students = GroupManager :: number_of_students($group['id']);
			if ($number_of_students > 0)
			{
				$base_group_options[$group['id']] = $group['name'].' ('.$number_of_students.' '.get_lang('Users').')';
			}
		}
		if (count($base_group_options) > 0)
		{
			echo '<b>'.get_lang('CreateSubgroups').'</b>';
			echo '<blockquote>';
			echo '<p>'.get_lang('CreateSubgroupsInfo').'</p>';
			$create_subgroups_form = new FormValidator('create_subgroups');
			$create_subgroups_form->addElement('hidden', 'action');
			$group_el = array ();
			$group_el[] = & $create_subgroups_form->createElement('static', null, null, get_lang('CreateNumberOfGroups'));
			$group_el[] = & $create_subgroups_form->createElement('text', 'number_of_groups', null, array ('size' => 3));
			$group_el[] = & $create_subgroups_form->createElement('static', null, null, get_lang('WithUsersFrom'));
			$group_el[] = & $create_subgroups_form->createElement('select', 'base_group', null, $base_group_options);
			$group_el[] = & $create_subgroups_form->createElement('submit', 'submit', get_lang('Ok'));
			$create_subgroups_form->addGroup($group_el, 'create_groups', null, ' ', false);
			$defaults = array ();
			$defaults['action'] = 'create_subgroups';
			$create_subgroups_form->setDefaults($defaults);
			$create_subgroups_form->display();
			echo '</blockquote>';
		}
	}
	/*
	 * Show form to generate groups from classes subscribed to the course
	 */
	$classes = ClassManager :: get_classes_in_course($_course['sysCode']);
	if (count($classes) > 0)
	{
		echo '<b>'.get_lang('GroupsFromClasses').'</b>';
		echo '<blockquote>';
		echo '<p>'.get_lang('GroupsFromClassesInfo').'</p>';
		echo '<ul>';
		foreach ($classes as $index => $class)
		{
			$number_of_users = count(ClassManager :: get_users($class['id']));
			echo '<li>';
			echo $class['name'];
			echo ' ('.$number_of_users.' '.get_lang('Users').')';
			echo '</li>';
		}
		echo '</ul>';

		$create_class_groups_form = new FormValidator('create_class_groups_form');
		$create_class_groups_form->addElement('hidden', 'action');
		if (api_get_setting('allow_group_categories') == 'true')
		{
			$group_categories = GroupManager :: get_categories();
			$cat_options = array ();
			foreach ($group_categories as $index => $category)
			{
				// Don't allow new groups in the virtual course category!
				if ($category['id'] != VIRTUAL_COURSE_CATEGORY)
				{
					$cat_options[$category['id']] = $category['title'];
				}
			}
			$create_class_groups_form->addElement('select', 'group_category', null, $cat_options);
		}
		else
		{
			$create_class_groups_form->addElement('hidden', 'group_category');
		}
		$create_class_groups_form->addElement('submit', 'submit', get_lang('Ok'));
		$defaults['group_category'] = DEFAULT_GROUP_CATEGORY;
		$defaults['action'] = 'create_class_groups';
		$create_class_groups_form->setDefaults($defaults);
		$create_class_groups_form->display();
		echo '</blockquote>';
	}
}
/*
===============================================================================
       DOKEOS FOOTER
===============================================================================
*/
Display :: display_footer();
?>
