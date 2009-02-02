<?php // $Id: infocours.php 14956 2008-04-20 14:21:32Z yannoo $

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Hugues Peeters
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)
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


/*
 * Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
// name of the language file that needs to be included 
$language_file = array ('admin','create_course', 'course_info', 'coursebackup');

require ('../inc/global.inc.php');

$this_section = SECTION_COURSES;

$nameTools= get_lang('Maintenance');

api_block_anonymous_users();

Display :: display_header($nameTools);

api_display_tool_title($nameTools);

?>


<div class="sectiontitle"><?php Display::display_icon('tool_delete.gif',get_lang("DelCourse")); ?>&nbsp;&nbsp;<a href="../course_info/delete_course.php?<?php echo api_get_cidreq();?>"><?php echo get_lang("DelCourse");?></a></div>
<div class="sectioncomment"><?php echo get_lang("DescriptionDeleteCourse");	?></div>	

<div class="sectiontitle"><?php Display::display_icon('save_import.gif', get_lang("backup")); ?>&nbsp;&nbsp;<a href="../coursecopy/backup.php?<?php echo api_get_cidreq();?>"><?php echo get_lang("backup");?></a></div>
<div class="sectioncomment">
			<ul>
			    <li><a href="../coursecopy/create_backup.php?<?php echo api_get_cidreq();?>"><?php echo get_lang('CreateBackup')  ?></a><br/>
			    <?php echo get_lang('CreateBackupInfo') ?>
			    </li>
			    <li><a href="../coursecopy/import_backup.php?<?php echo api_get_cidreq();?>"><?php echo get_lang('ImportBackup')  ?></a><br/>
			    <?php echo get_lang('ImportBackupInfo') ?>
			    </li>
		    </ul>
</div>
			
<div class="sectiontitle"><?php Display::display_icon('empty.gif', get_lang("recycle_course")); ?>&nbsp;&nbsp;<a href="../coursecopy/recycle_course.php?<?php echo api_get_cidreq();?>"><?php echo get_lang("recycle_course");?></a></div>
<div class="sectioncomment"><?php echo get_lang("DescriptionRecycleCourse");?></div>

<div class="sectiontitle"><?php Display::display_icon('copy.gif', get_lang("CopyCourse")); ?>&nbsp;&nbsp;<a href="../coursecopy/copy_course.php?<?php echo api_get_cidreq();?>"><?php echo get_lang("CopyCourse");?></a></div>
<div class="sectioncomment"><?php echo get_lang("DescriptionCopyCourse"); ?></div>

<?php
// footer
Display::display_footer();
?>
