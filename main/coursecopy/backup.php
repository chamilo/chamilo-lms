<?php
// $Id: backup.php 9246 2006-09-25 13:24:53Z bmol $
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
/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
$langFile = 'coursebackup';
include ('../inc/global.inc.php');
$nameTools = get_lang('Backup');
Display::display_header($nameTools);
api_display_tool_title($nameTools);
if (!api_is_allowed_to_edit())
{
	api_not_allowed();
}
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
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display::display_footer();
?>