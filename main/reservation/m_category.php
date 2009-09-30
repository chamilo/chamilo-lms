<?php
// $Id: m_category.php,v 1.2 2006/05/09 08:51:14 kvansteenkiste Exp $
/*
==============================================================================
    Dokeos - elearning and course management software

    Copyright (c) 2004-2008 Dokeos SPRL
    Copyright (c) Sebastien Jacobs (www.spiritual-coder.com)
    Copyright (c) Kristof Van Steenkiste
    Copyright (c) Julio Montoya Armas

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
    ---------------------------------------------------------------------
                Category-manager (add, edit & delete)
    ---------------------------------------------------------------------
 */
require_once('rsys.php');

$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

Rsys :: protect_script('m_category');
$tool_name = get_lang('BookingSystem');
$interbreadcrumb[] = array ("url" => "../admin/index.php", "name" => get_lang('PlatformAdmin'));

/**
    ---------------------------------------------------------------------
 */

/**
 *  Filter to display the modify-buttons
 *
 *  @param - int $id The ResourceType-id
 */
function modify_filter($id) {
	return '<a href="m_category.php?action=edit&amp;id='.$id.'" title="'.get_lang("EditResourceType").'"><img alt="" src="../img/edit.gif" /></a>'.' <a href="m_category.php?action=delete&amp;id='.$id.'" title="'.get_lang("DeleteResourceType").'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmDeleteResourceType")))."'".')) return false;"><img alt="" src="../img/delete.gif" /></a>';
}

/**
    ---------------------------------------------------------------------
 */

switch ($_GET['action']) {
	case 'add' :
		$interbreadcrumb[] = array ("url" => "m_category.php", "name" => $tool_name);
		Display :: display_header(get_lang('AddNewResourceType'));
		api_display_tool_title(get_lang('AddNewResourceType'));
		$form = new FormValidator('category', 'post', 'm_category.php?action=add');
		$form->add_textfield('name', get_lang('ResourceTypeName'), true, array ('maxlength' => '128'));
		$form->addElement('style_submit_button', 'submit', get_lang('CreateResourceType'),'class="add"');
		if ($form->validate()) {
			$values = $form->exportValues();
			if (Rsys :: add_category($values['name']))
				Display :: display_normal_message(Rsys :: get_return_msg(get_lang('ResourceTypeAdded'), "m_category.php", $tool_name),false);
			else
				Display :: display_normal_message(Rsys :: get_return_msg(get_lang('ResourceTypeExist'), "m_category.php?action=add", get_lang('AddNewResourceType')),false);
		} else
			$form->display();
		break;
	case 'edit' :
		$interbreadcrumb[] = array ("url" => "m_category.php", "name" => $tool_name);
		Display :: display_header(get_lang('EditResourceType'));
		api_display_tool_title(get_lang('EditResourceType'));
		$form = new FormValidator('category', 'post', 'm_category.php?action=edit');
		$form->add_textfield('name', get_lang('ResourceTypeName'), true, array ('maxlength' => '128'));
		$form->addElement('hidden', 'id', $_GET['id']);
		$form->addElement('style_submit_button', 'submit', get_lang('ModifyResourceType'),'class="save"');
		$form->setDefaults(Rsys :: get_category($_GET['id']));
		if ($form->validate()) {
			$values = $form->exportValues();
			if (Rsys :: edit_category($values['id'], $values['name']))
				Display :: display_normal_message(Rsys :: get_return_msg(get_lang('ResourceTypeEdited'), "m_category.php", $tool_name),false);
			else
				Display :: display_normal_message(Rsys :: get_return_msg(get_lang('ResourceTypeExist'), "m_category.php?action=edit&id=".$values['id'], get_lang('EditRight')),false);
		} else
			$form->display();
		break;
	case 'delete' :
		$result = Rsys :: delete_category($_GET['id']);
		ob_start();
		if ($result == 0)
			Display :: display_normal_message(get_lang('ResourceTypeDeleted'),false);
		else
			Display :: display_normal_message(str_replace('#NUM#', $result, get_lang('ResourceTypeHasItems')),false);
		$msg = ob_get_contents();
		ob_end_clean();
	default :
		$NoSearchResults = get_lang('NoCategories');
		Display :: display_header($tool_name);
		api_display_tool_title($tool_name);

		echo $msg;
		echo '<div class="actions">';
		echo '<a href="m_category.php?action=add"><img src="../img/view_more_stats.gif" border="0" alt="" title="'.get_lang('AddNewBookingPeriod').'"/>'.get_lang('AddNewResourceType').'</a><br />';
		echo '</div>';
		if (isset ($_POST['action'])) {
			switch ($_POST['action']) {
				case 'delete_categories' :
					$ids = $_POST['categories'];
					if (count($ids) > 0) {
						foreach ($ids as $index => $id) {
							$result = Rsys :: delete_category($id);
							if ($result != 0)
								$warning = true;
						}
					}
					if ($warning) {
						ob_start();
						Display :: display_normal_message(get_lang('ResourceTypeNotDeleted'),false);
						$msg2 = ob_get_contents();
						ob_end_clean();
					}
					break;
			}
		}
		echo $msg2;
		$table = new SortableTable('category', array ('Rsys', 'get_num_categories'), array ('Rsys', 'get_table_categories'), 1);
		$table->set_header(0, '', false, array ('style' => 'width:10px'));
		$table->set_header(1, '', false);
		$table->set_header(2, '', false, array ('style' => 'width:50px;'));
		$table->set_column_filter(2, 'modify_filter');
		$table->set_form_actions(array ('delete_categories' => get_lang('DeleteSelectedCategories')), 'categories');
		$table->display();
}

/**
    ---------------------------------------------------------------------
 */

Display :: display_footer();
?>
