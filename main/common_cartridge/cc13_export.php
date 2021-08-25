<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'common_cartridge/export/src/lib/ccdependencyparser.php';
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

api_check_archive_dir();
api_set_more_memory_and_time_limits();

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php',
    'name' => get_lang('Maintenance'),
];

// Displaying the header
$nameTools = get_lang('ExportCcVersion13');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);

$action = isset($_POST['action']) ? $_POST['action'] : '';
$backupOption = 'select_items';

if (Security::check_token('post') && ($action === 'course_select_form')) {
    // Clear token
    Security::clear_token();
    $resources = Security::remove_XSS($_POST['resource']);
    if (!empty($resources)) {
        $cb = new CourseBuilder('partial');
        $course = $cb->build(0, null, false, array_keys($resources), $resources);
        $course = CourseSelectForm::get_posted_course(null, 0, '', $course);
        $imsccFile = Cc13ExportConvert::export($course);
        if ($imsccFile !== false) {
            echo Display::return_message(get_lang('IMSCCCreated'), 'confirm');
            echo '<br />';
            echo Display::toolbarButton(
                get_lang('Download'),
                api_get_path(WEB_CODE_PATH).'course_info/download.php?archive='.$imsccFile.'&'.api_get_cidreq(),
                'file-zip-o',
                'primary'
            );
            /*echo Display::url(
                get_lang('Download'),
                api_get_path(WEB_CODE_PATH).'course_info/download.php?archive='.$imsccFile.'&'.api_get_cidreq(),
                ['class' => 'btn btn-primary btn-large']
            );*/
        }
    }
} else {
    // Clear token
    Security::clear_token();
    $cb = new CourseBuilder('partial');
    $toolsToBuild = [
        RESOURCE_DOCUMENT,
        RESOURCE_FORUMCATEGORY,
        RESOURCE_FORUM,
        RESOURCE_FORUMTOPIC,
        RESOURCE_QUIZ,
        RESOURCE_TEST_CATEGORY,
        RESOURCE_LINK,
        RESOURCE_WIKI,
    ];
    $course = $cb->build(0, null, false, $toolsToBuild);
    if ($course->has_resources()) {
        // Add token to Course select form
        $hiddenFields['sec_token'] = Security::get_token();
        CourseSelectForm::display_form($course, $hiddenFields, false, true, true);
    } else {
        echo Display::return_message(get_lang('NoResourcesToBackup'), 'warning');
    }
}

Display::display_footer();
