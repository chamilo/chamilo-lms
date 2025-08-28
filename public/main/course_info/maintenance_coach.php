<?php
/* For licensing terms, see /license.txt */
/**
 * Backup and import for session coach.
 *
 * @author Julio Montoya <julio.montoya@beeznest.com>
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Backup and import');

api_protect_course_script(true);
api_block_anonymous_users();

$sessionsCopy = api_get_setting('allow_session_course_copy_for_teachers');
if ('true' !== $sessionsCopy) {
    api_not_allowed(true);
}

Display::display_header($nameTools);

echo Display::page_subheader(
    Display::getMdiIcon(
        ActionIcon::IMPORT_ARCHIVE,
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Backup and import')
    ).'&nbsp;&nbsp;'.get_lang('Backup and import')
);

$url = api_get_path(WEB_CODE_PATH).'course_copy/copy_course_session_selected.php?'.api_get_cidreq();

$link = Display::url(get_lang('Copy course'), $url);
?>
<div class="sectioncomment">
    <ul>
        <li>
            <?php echo $link; ?><br/>
            <?php echo get_lang('Duplicate the course or some learning objects in another course. You need 2 courses to use this feature: an original course and a target course.'); ?>
        </li>
    </ul>
</div>
<?php
Display::display_footer();
