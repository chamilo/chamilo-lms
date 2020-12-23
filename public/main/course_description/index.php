<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_DESCRIPTION;
$this_section = SECTION_COURSES;

$action = !empty($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'listing';

$logInfo = [
    'tool' => TOOL_COURSE_DESCRIPTION,
    'action' => $action,
];
Event::registerLog($logInfo);

// protect a course script
api_protect_course_script(true);

$description_type = '';
if (isset($_GET['description_type'])) {
    $description_type = (int) $_GET['description_type'];
}

$id = null;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

if (isset($_GET['isStudentView']) && 'true' === $_GET['isStudentView']) {
    $action = 'listing';
}

// interbreadcrumb
$interbreadcrumb[] = ['url' => 'index.php?'.api_get_cidreq(), 'name' => get_lang('Description')];
if (1 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Description')];
}
if (2 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Objectives')];
}
if (3 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Topics')];
}
if (4 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Methodology')];
}
if (5 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Course material')];
}
if (6 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Resources')];
}
if (7 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Assessment')];
}
if (8 == $description_type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Thematic advance')];
}
if ($description_type >= 9) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Others')];
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
        $descriptionController->delete($id);
        break;
    case 'listing':
    default:
        $descriptionController->listing();
}
