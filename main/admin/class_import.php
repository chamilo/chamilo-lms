<?php
// $Id: class_import.php 10215 2006-11-27 13:57:17Z pcool $
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
*   This     tool allows platform admins to add classes by uploading a CSV file
* @todo Add some langvars to DLTT
*	@package dokeos.admin
==============================================================================
*/
/**
 * Validate the imported data
 */
function validate_data($classes)
{
	$errors = array();
	foreach($classes as $index => $class)
	{
		//1. Check if ClassName is available
		if(!isset($class['ClassName']) || strlen(trim($class['ClassName'])) == 0)
		{
			$class['line'] = $index+2;
			$class['error'] = get_lang('MissingClassName');
			$errors[] = $class;
		}	
		//2. Check if class doesn't exist yet
		else
		{
			if(ClassManager::class_name_exists($class['ClassName']))
			{
				$class['line'] = $index+2;
				$class['error'] = get_lang('ClassNameExists');
				$errors[] = $class;					
			}	
		}
	}
	return $errors;
}
/**
 * Save imported class data to database
 */
function save_data($classes)
{
	$number_of_added_classes = 0;
	foreach($classes as $index => $class)
	{
		if(ClassManager::create_class($class['ClassName']))
		{
			$number_of_added_classes++;	
		}	
	}
	return $number_of_added_classes;
}



// name of the language file that needs to be included 
$language_file = array ('admin', 'registration');

// resetting the course id
$cidReset = true;

// including some necessary dokeos files
include ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'import.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

// Database Table Definitions

// setting the name of the tool
$tool_name = get_lang('ImportClassListCSV');

// Displaying the header
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
$form = new FormValidator('import_classes');
$form->addElement('file','import_file',get_lang('ImportCSVFileLocation'));
$form->addElement('submit','submit',get_lang('Ok'));
if( $form->validate())
{
	$classes = Import::csv_to_array($_FILES['import_file']['tmp_name']);
	$errors = validate_data($classes);
	if (count($errors) == 0)
	{
		$number_of_added_classes = save_data($classes);
		Display::display_normal_message($number_of_added_classes.' '.get_lang('ClassesCreated'));
	}		
	else
	{
		$error_message = get_lang('ErrorsWhenImportingFile');
		$error_message .= '<ul>';
		foreach ($errors as $index => $error_class)
		{
			$error_message .= '<li>'.$error_class['error'].' ('.get_lang('Line').' '.$error_class['line'].')';
			$error_message .= '</li>';
		}
		$error_message .= '</ul>';
		$error_message .= get_lang('NoClassesHaveBeenCreated');
		Display :: display_error_message($error_message);
	}
}
$form->display();
?>
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>
<blockquote>
 <pre>
  <b>ClassName</b>
  <b>1A</b>
  <b>1B</b>
  <b>2A group 1</b>
  <b>2A group 2</b>
 </pre>
</blockquote>
<?php
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>