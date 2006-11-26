<?php


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


/**
 * MAIN PART
 */
 
?>


<table width="100%" cellpadding="0" cellspacing="20">
	<tr>
		<td width="50%" valign="top" style="border:1px #4171B5 solid; padding: 4px;">
			<div><img src="../img/tool_delete.gif">&nbsp;&nbsp;<a href="../course_info/delete_course.php"><?php echo get_lang("DelCourse");?></a></div><br>
			<?php
				echo get_lang("DescriptionDeleteCourse");
			?>
		</td>
		<td width="50%" valign="top" style="border:1px #4171B5 solid; padding: 4px;">
			<div><img src="../img/save_import.gif">&nbsp;&nbsp;<a href="../coursecopy/backup.php"><?php echo get_lang("backup");?></a></div>
			<ul>
			    <li><a href="../coursecopy/create_backup.php"><?php echo get_lang('CreateBackup')  ?></a><br/>
			    <?php echo get_lang('CreateBackupInfo') ?>
			    </li>
			    <li><a href="../coursecopy/import_backup.php"><?php echo get_lang('ImportBackup')  ?></a><br/>
			    <?php echo get_lang('ImportBackupInfo') ?>
			    </li>
		    </ul>
		</td>
	</tr>
	<tr>
		<td width="50%" valign="top" style="border:1px #4171B5 solid; padding: 4px;">
			<div><img src="../img/empty.gif">&nbsp;&nbsp;<a href="../coursecopy/recycle_course.php"><?php echo get_lang("recycle_course");?></a></div><br>
			<?php echo get_lang("DescriptionRecycleCourse");?><br><br>
		</td>
			
		<td width="50%" valign="top" style="border:1px #4171B5 solid; padding: 4px;">
			<div><img src="../img/copy.gif">&nbsp;&nbsp;<a href="../coursecopy/copy_course.php"><?php echo get_lang("CopyCourse");?></a></div><br>
			<?php
				echo get_lang("DescriptionCopyCourse");
			?>
			
		</td>
			
	</tr>
</table>


<?php

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();


?>
