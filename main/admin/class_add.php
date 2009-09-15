<?php
// $Id: class_add.php 10215 2006-11-27 13:57:17Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Bart Mollet, Hogeschool Gent

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
require_once('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// setting the name of the tool
$tool_name = get_lang("AddClasses");

$form = new FormValidator('add_class');
$form->add_textfield('name',get_lang('ClassName'));
$form->addElement('submit','submit',get_lang('Ok'));
if($form->validate())
{
	$values = $form->exportValues();
	ClassManager :: create_class($values['name']);
	header('Location: class_list.php');
}

// Displaying the header
Display :: display_header($tool_name);

// Displaying the form
$form->display();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>