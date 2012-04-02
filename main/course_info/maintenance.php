<?php
/* For licensing terms, see /license.txt */
/**
 * @author Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 * @package chamilo.course_info
 */
/**
 * Code
 */

// Language files that need to be included
$language_file = array('admin','create_course', 'course_info', 'coursebackup');
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_COURSE_MAINTENANCE;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Maintenance');
api_protect_course_script(true);
api_block_anonymous_users();

Display :: display_header($nameTools);
echo Display::page_header($nameTools);

?>

<div class="sectiontitle"><?php Display::display_icon('save_import.gif', get_lang('backup')); ?>&nbsp;&nbsp;<a href="../coursecopy/backup.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('backup'); ?></a></div>
<div class="sectioncomment">
		<ul>
		    <li><a href="../coursecopy/create_backup.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('CreateBackup'); ?></a><br/>
		    <?php echo get_lang('CreateBackupInfo'); ?>
		    </li>
		    <li><a href="../coursecopy/import_backup.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('ImportBackup'); ?></a><br/>
		    <?php echo get_lang('ImportBackupInfo'); ?>
		    </li>
	    </ul>
</div>

<div class="sectiontitle"><?php Display::display_icon('copy.gif', get_lang('CopyCourse')); ?>&nbsp;&nbsp;<a href="../coursecopy/copy_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('CopyCourse'); ?></a></div>
<div class="sectioncomment"><?php echo get_lang('DescriptionCopyCourse'); ?></div>

<div class="sectiontitle"><?php Display::display_icon('tool_delete.gif', get_lang('recycle_course')); ?>&nbsp;&nbsp;<a href="../coursecopy/recycle_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('recycle_course'); ?></a></div>
<div class="sectioncomment"><?php echo get_lang('DescriptionRecycleCourse'); ?></div>

<div class="sectiontitle"><?php Display::display_icon('delete.gif', get_lang('DelCourse')); ?>&nbsp;&nbsp;<a href="../course_info/delete_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('DelCourse'); ?></a></div>
<div class="sectioncomment"><?php echo get_lang('DescriptionDeleteCourse');	?></div>

<?php
// Footer
Display::display_footer();
