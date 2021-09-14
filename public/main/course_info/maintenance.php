<?php

/* For licensing terms, see /license.txt */
/**
 * @author Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Backup and import and import');
api_protect_course_script(true);
api_block_anonymous_users();

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

Display::display_header($nameTools);
echo Display::page_header($nameTools);

?>

<div class="sectiontitle">
    <?php echo Display::return_icon('save_import.gif', get_lang('Backup and import and import')); ?>&nbsp;&nbsp;
    <?php echo get_lang('Backup and import and import'); ?>
</div>
<div class="sectioncomment">
    <ul>
        <li>
            <a href="../coursecopy/create_backup.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('Create a backup and import and import'); ?>
            </a><br/>
            <?php echo get_lang('Create a backup and import and importInfo'); ?>
        </li>
        <li>
            <a href="../coursecopy/import_backup.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('Import backup and import and import'); ?>
            </a><br/>
            <?php echo get_lang('Import backup and import and importInfo'); ?>
        </li>
        <li>
            <a href="../coursecopy/import_moodle.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('Import from Moodle'); ?>
            </a><br/>
            <?php echo get_lang('Import from MoodleInfo'); ?>
        </li>
    </ul>
</div>

<div class="sectiontitle">
    <?php echo Display::return_icon('copy.gif', get_lang('Copy course')); ?>&nbsp;&nbsp;
    <a href="../coursecopy/copy_course.php?<?php echo api_get_cidreq(); ?>">
        <?php echo get_lang('Copy course'); ?></a>
</div>
<div class="sectioncomment"><?php echo get_lang('DescriptionCopy course'); ?>
</div>

<div class="sectiontitle">
    <?php echo Display::return_icon('delete.png', get_lang('Empty this course')); ?>&nbsp;&nbsp;
    <a href="../coursecopy/recycle_course.php?<?php echo api_get_cidreq(); ?>">
        <?php echo get_lang('Empty this course'); ?>
    </a>
</div>
<div class="sectioncomment"><?php echo get_lang('This tool empties the course. It removes documents, forums, links. And allows you to select what parts you want to remove or decide to remove the whole.'); ?></div>

<div class="sectiontitle">
    <?php echo Display::return_icon('delete.png', get_lang('Completely delete this course')); ?>&nbsp;&nbsp;
    <a href="../course_info/delete_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('Completely delete this course'); ?>
    </a>
</div>
<div class="sectioncomment"><?php echo get_lang('Click on this link for a full removal of the course from the server.<br /><br />Be carefull, there\'s no way back!'); ?></div>

<?php

Display::display_footer();
