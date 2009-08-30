<?php
// $Id: backup.php 12219 2007-05-01 18:46:59Z yannoo $
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
/**
 * ==============================================================================
 * Create a backup.
 * 
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 * ==============================================================================
 */

// name of the language file that needs to be included 
$language_file = array('exercice', 'coursebackup', 'admin');

// including the global file
include ('../inc/global.inc.php');

// Check access rights (only teachers allowed)
if (!api_is_allowed_to_edit())
{
	api_not_allowed(true);
}
// section for the tabs
$this_section=SECTION_COURSES;

// breadcrumbs
$interbreadcrumb[] = array ("url" => "../course_info/maintenance.php", "name" => get_lang('Maintenance'));

// Displaying the header
$nameTools = get_lang('Backup');
Display::display_header($nameTools);

// Display the tool title
api_display_tool_title($nameTools);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 
?>

  <ul>
    <li><a href="create_backup.php"><?php echo get_lang('CreateBackup')  ?></a><br/>
    <?php echo get_lang('CreateBackupInfo') ?>
    </li>
    <li><a href="import_backup.php"><?php echo get_lang('ImportBackup')  ?></a><br/>
    <?php echo get_lang('ImportBackupInfo') ?>
    </li>
  </ul>
<?php
// Display the footer
Display::display_footer();
?>