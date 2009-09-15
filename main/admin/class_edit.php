<?php
// $Id: class_edit.php 10215 2006-11-27 13:57:17Z pcool $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';

// resetting the course id
$cidReset = true;

// including some necessary dokeos files
include ('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => "class_list.php", "name" => get_lang('AdminClasses'));

// setting the name of the tool
$tool_name = get_lang("AddClasses");


$tool_name = get_lang('ModifyClassInfo');
$class_id = intval($_GET['idclass']);
$class = ClassManager :: get_class_info($class_id);
$form = new FormValidator('edit_class','post','class_edit.php?idclass='.$class_id);
$form->add_textfield('name',get_lang('ClassName'));
$form->addElement('submit','submit',get_lang('Ok'));
$form->setDefaults(array('name'=>$class['name']));
if($form->validate())
{
	$values = $form->exportValues();
	ClassManager :: set_name($values['name'], $class_id);
	header('Location: class_list.php');
}


Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
$form->display();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>