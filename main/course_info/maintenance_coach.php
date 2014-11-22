<?php
/* For licensing terms, see /license.txt */
/**
 * Maintenance for session coach
 * @author Julio Montoya <julio.montoya@beeznest.com>
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.course_info
 */
/**
 * Code
 */
// Language files that need to be included
$language_file = array('admin', 'create_course', 'course_info', 'coursebackup');

require_once '../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Maintenance');

api_protect_course_script(true);
api_block_anonymous_users();

if (!isset($_configuration['allow_session_course_copy_for_teachers'])
     || !$_configuration['allow_session_course_copy_for_teachers']){
    api_not_allowed(true);
}

Display :: display_header($nameTools);

echo Display::page_subheader(
    Display::return_icon(
        'save_import.gif', get_lang('backup')
    ) . '&nbsp;&nbsp;' . get_lang('backup')
);

$url = api_get_path(
        WEB_CODE_PATH
    ) . 'coursecopy/copy_course_session_selected.php?' . api_get_cidreq();

$link = Display::url(get_lang('CopyCourse'), $url);
?>
<div class="sectioncomment">
    <ul>
        <li>
            <?php echo $link; ?><br/>
            <?php echo get_lang('DescriptionCopyCourse'); ?>
        </li>
    </ul>
</div>
<?php
Display::display_footer();
