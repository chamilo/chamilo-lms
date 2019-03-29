<?php
/* For licensing terms, see /license.txt */

/**
 * Template (front controller in MVC pattern) used for distpaching
 * to the controllers depend on the current action.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.course_description
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_DESCRIPTION;

// defining constants
define('ADD_BLOCK', 8);

// current section
$this_section = SECTION_COURSES;

$action = !empty($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'listing';

$logInfo = [
    'tool' => TOOL_COURSE_DESCRIPTION,
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $action,
    'info' => '',
];
Event::registerLog($logInfo);

// protect a course script
api_protect_course_script(true);

$description_type = '';
if (isset($_GET['description_type'])) {
    $description_type = intval($_GET['description_type']);
}

$id = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') {
    $action = 'listing';
}

// interbreadcrumb
$interbreadcrumb[] = ["url" => "index.php?".api_get_cidreq(), "name" => get_lang('CourseProgram')];
if ($description_type == 1) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('GeneralDescription')];
}
if ($description_type == 2) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('Objectives')];
}
if ($description_type == 3) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('Topics')];
}
if ($description_type == 4) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('Methodology')];
}
if ($description_type == 5) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('CourseMaterial')];
}
if ($description_type == 6) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('HumanAndTechnicalResources')];
}
if ($description_type == 7) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('Assessment')];
}
if ($description_type == 8) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('ThematicAdvance')];
}
if ($description_type >= 9) {
    $interbreadcrumb[] = ["url" => "#", "name" => get_lang('Others')];
}

// course description controller object
$descriptionController = new CourseDescriptionController();

// block access
if (in_array($action, ['add', 'edit', 'delete']) &&
    !api_is_allowed_to_edit(null, true)
) {
    api_not_allowed(true);
}

// Actions to controller
switch ($action) {
    case 'history':
        $descriptionController->listing(true);
        break;
    case 'add':
        $descriptionController->add();
        break;
    case 'edit':
        $descriptionController->edit($id, $description_type);
        break;
    case 'delete':
        $descriptionController->destroy($id);
        break;
    case 'listing':
    default:
        $descriptionController->listing();
}
