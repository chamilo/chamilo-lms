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

echo Display::page_subheader(Display::return_icon('save_import.gif', get_lang('backup')).'&nbsp;&nbsp;'.get_lang('backup'));
?>
<div class="sectioncomment">
		<ul>
		    <li>
                <a href="../coursecopy/create_backup.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('CreateBackup'); ?></a><br/>
                <?php echo get_lang('CreateBackupInfo'); ?>
		    </li>
		    <li>
                <a href="../coursecopy/import_backup.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('ImportBackup'); ?></a><br/>
                <?php echo get_lang('ImportBackupInfo'); ?>
		    </li>

            <li>
            <a href="../coursecopy/copy_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('CopyCourse'); ?></a><br/>
            <?php echo get_lang('DescriptionCopyCourse'); ?>
            </li>

            <li>
            <a href="../coursecopy/recycle_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('recycle_course'); ?></a><br/>
            <?php echo get_lang('DescriptionRecycleCourse'); ?>
            </li>

            <li>
            <a href="../course_info/delete_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('DelCourse'); ?></a><br/>
            <?php echo get_lang('DescriptionDeleteCourse');	?>
            </li>
	    </ul>
</div>
<?php
// Footer
Display::display_footer();
