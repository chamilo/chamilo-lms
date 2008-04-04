<?php // $Id: $
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
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('admin','registration');
$cidReset = true;

// including necessary libraries
require ('../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
include_once ($libpath.'usermanager.lib.php');
require_once ($libpath.'formvalidator/FormValidator.class.php');

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// Database table definitions
$table_admin	= Database :: get_main_table(TABLE_MAIN_ADMIN);
$table_user 	= Database :: get_main_table(TABLE_MAIN_USER);
$table_uf	 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
$table_uf_opt 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
$table_uf_val 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_fields.php', "name" => get_lang('UserFields'));

$tool_name = get_lang('AddUserFields');
// Create the form
$form = new FormValidator('user_fields_add');
// Field variable name
$form->addElement('text','fieldlabel',get_lang('FieldLabel'));
$form->applyFilter('fieldlabel','html_filter');
$form->applyFilter('fieldlabel','trim');
$form->addRule('fieldlabel', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('fieldlabel', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
$form->addRule('fieldlabel', '', 'maxlength',20);
$form->addRule('fieldlabel', get_lang('FieldTaken'), 'fieldlabel_available');
// Field type
$types = array();
$types[USER_FIELD_TYPE_TEXT]  = get_lang('FieldTypeText');
//$types[USER_FIELD_TYPE_TEXTAREA] = get_lang('FieldTypeTextarea');
//$types[USER_FIELD_TYPE_RADIO] = get_lang('FieldTypeRadio');
//$types[USER_FIELD_TYPE_SELECT] = get_lang('FieldTypeSelect');
//$types[USER_FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
$form->addElement('select','fieldtype',get_lang('FieldType'),$types);
$form->addRule('fieltype', get_lang('ThisFieldIsRequired'), 'required');
// Field display name
$form->addElement('text','fieldtitle',get_lang('FieldTitle'));
$form->applyFilter('fieldtitle','html_filter');
$form->applyFilter('fieldtitle','trim');
$form->addRule('fieldtitle', get_lang('ThisFieldIsRequired'), 'required');
// Field default value
$form->addElement('text','fielddefaultvalue',get_lang('FieldDefaultValue'));

// Set default values
$defaults = array();
$form->setDefaults($defaults);
// Submit button
$form->addElement('submit', 'submit', get_lang('Add'));
// Validate form
if( $form->validate())
{
	$check = Security::check_token('post');
	if($check)
	{
		$field = $form->exportValues();
		$fieldlabel = $field['fieldlabel'];
		$fieldtype = $field['fieldtype'];
		$fieldtitle = $field['fieldtitle'];
		$fielddefault = $field['fielddefaultvalue'];
	
		$field_id = UserManager::create_extra_field($fieldlabel,$fieldtype,$fieldtitle,$fielddefault);
		Security::clear_token();
		header('Location: user_fields.php?action=show_message&message='.urlencode(get_lang('FieldAdded')));
		exit ();
	}
}else{
	if(isset($_POST['submit'])){
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
}
// Display form
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
if(!empty($_GET['message'])){
	Display::display_normal_message($_GET['message']);
}
$form->display();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>