<?php
/* For licensing terms, see /license.txt */
/**
 * @author Created on 18 October 2006 by Elixir Interactive http://www.elixir-interactive.com
 *
 * @package chamilo.course_info
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Maintenance');
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
    <?php Display::display_icon('save_import.gif', get_lang('Backup')); ?>&nbsp;&nbsp;
    <?php echo get_lang('Backup'); ?>
</div>
<div class="sectioncomment">
    <ul>
        <li>
            <a href="../coursecopy/create_backup.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('CreateBackup'); ?>
            </a><br/>
            <?php echo get_lang('CreateBackupInfo'); ?>
        </li>
        <li>
            <a href="../coursecopy/import_backup.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('ImportBackup'); ?>
            </a><br/>
            <?php echo get_lang('ImportBackupInfo'); ?>
        </li>
        <li>
            <a href="../coursecopy/import_moodle.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('ImportFromMoodle'); ?>
            </a><br/>
            <?php echo get_lang('ImportFromMoodleInfo'); ?>
        </li>
        <li>
            <a href="../coursecopy/export_moodle.php?<?php echo api_get_cidreq(); ?>">
                <?php echo get_lang('ExportToMoodle'); ?>
            </a><br/>
            <?php echo get_lang('ExportToMoodleInfo'); ?>
        </li>
    </ul>
</div>

<div class="sectiontitle">
    <?php Display::display_icon('copy.gif', get_lang('CopyCourse')); ?>&nbsp;&nbsp;
    <a href="../coursecopy/copy_course.php?<?php echo api_get_cidreq(); ?>">
        <?php echo get_lang('CopyCourse'); ?></a>
</div>
<div class="sectioncomment"><?php echo get_lang('DescriptionCopyCourse'); ?>
</div>

<br>
<div class="sectiontitle">
    <?php Display::display_icon('copy.gif', get_lang('IMSCC13')); ?>&nbsp;&nbsp;
    <?php echo get_lang('CommonCartridge13'); ?>
</div>
<div class="sectioncomment">
    <ul>
        <li>
            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>common_cartridge/cc13_export.php?<?php echo api_get_cidreq(); ?>">
            <?php echo get_lang('ExportCcVersion13'); ?></a>
            </a><br/>
            <?php echo get_lang('ExportCcVersion13Info'); ?>
        </li>
        <li>
            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>common_cartridge/cc13_import.php?<?php echo api_get_cidreq(); ?>">
            <?php echo get_lang('ImportCcVersion13'); ?></a>
            </a><br/>
            <?php echo get_lang('ImportCcVersion13Info'); ?>
        </li>
    </ul>
</div>

<div class="sectiontitle">
    <?php Display::display_icon('tool_delete.gif', get_lang('recycle_course')); ?>&nbsp;&nbsp;
    <a href="../coursecopy/recycle_course.php?<?php echo api_get_cidreq(); ?>">
        <?php echo get_lang('recycle_course'); ?>
    </a>
</div>
<div class="sectioncomment"><?php echo get_lang('DescriptionRecycleCourse'); ?></div>

<div class="sectiontitle">
    <?php Display::display_icon('delete.gif', get_lang('DelCourse')); ?>&nbsp;&nbsp;
    <a href="../course_info/delete_course.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('DelCourse'); ?>
    </a>
</div>
<div class="sectioncomment"><?php echo get_lang('DescriptionDeleteCourse'); ?></div>

<?php

Display::display_footer();
