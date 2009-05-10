<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
 * Manage specific fields
 *
 * @package dokeos.admin
 */
$language_file[] = 'admin';
// including some necessary dokeos files
require('../inc/global.inc.php');

// user permissions
api_protect_admin_script();

// breadcrumb
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'specific_fields.php', "name" => get_lang('SpecificSearchFields'));

$libpath = api_get_path(LIBRARY_PATH);
include_once ($libpath.'specific_fields_manager.lib.php');
require_once ($libpath.'formvalidator/FormValidator.class.php');

// Create an add-field box
$form = new FormValidator('add_field','post','','',null,false);
$renderer =& $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->addElement('static','search_advanced_link',null,'<a href="specific_fields_add.php">'.Display::return_icon('fieldadd.gif').get_lang('AddSpecificSearchField').'</a>');



// Create a sortable table with specific fields data
$column_show = array(1,1,1,1);
$column_order = array(3,2,1,4);
$extra_fields = get_specific_field_list();
$number_of_extra_fields = count($extra_fields);

$table = new SortableTableFromArrayConfig($extra_fields,2,50,'',$column_show,$column_order);
$table->set_header(0, '', false,null,'width="2%"', 'style="display:none"');
$table->set_header(1, get_lang('Code'), TRUE, 'width="10%"');
$table->set_header(2, get_lang('Name'));
$table->set_header(3, get_lang('Modify'),true,'width="10%"');
$table->set_column_filter(3, 'edit_filter');

function edit_filter($id,$url_params,$row)
{
	global $charset;
	$return = '<a href="specific_fields_add.php?action=edit&field_id='.$row[0].'">'.Display::return_icon('edit.gif',get_lang('Edit')).'</a>';
	$return .= ' <a href="'.api_get_self().'?action=delete&field_id='.$row[0].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a>';
	return $return;
}

if ($_REQUEST['action'] == 'delete') {
	delete_specific_field($_REQUEST['field_id']);
	header('Location: specific_fields.php?message='.get_lang('FieldRemoved'));
}

// Start output

// Displaying the header
Display::display_header($nameTools);
echo '<div class="admin-tool-intro">'.get_lang('SpecificSearchFieldsIntro').'</div>';

if(!empty($_GET['message']))
{
  Display::display_confirmation_message($_GET['message']);
}

echo '<div class="actions">';
$form->display();
echo '</div>';

$table->display();

// Displaying the footer
Display::display_footer();
