<?php
/* For licensing terms, see /license.txt */

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 *
 * @package chamilo.wiki
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once 'wiki.inc.php';

global $charset;

$wiki = new Wiki();
$wiki->charset = $charset;

// section (for the tabs)
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_WIKI;

$course_id = api_get_course_int_id();
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id);
$groupId = api_get_group_id();

// additional style information
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

// javascript for advanced parameters menu
$htmlHeadXtra[] = '<script>
function setFocus() {
    $("#search_title").focus();
}

$(function() {
    setFocus();
    $("#start_date_toggle").click(function() {
        $("#start_date").toggle();
    });

    $("#end_date_toggle").click(function() {
        $("#end_date").toggle();
    });
});

</script>';

/* Constants and variables */
$tool_name = get_lang('ToolWiki');

/* ACCESS */
api_protect_course_script();
api_block_anonymous_users();
api_protect_course_group(GroupManager::GROUP_TOOL_WIKI);

Event::event_access_tool(TOOL_WIKI);

if ($groupId) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.Security::remove_XSS($group_properties['name']),
    ];
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true);

// The page we are dealing with
$page = $_GET['title'] ?? 'index';
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'showpage';
$view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : null;

$wiki->page = $page;
$wiki->action = $action;

// Setting wiki data
if (!empty($view)) {
    $wiki->setWikiData($view);
}

$wiki->blockConcurrentEditions(api_get_user_id(), $action);

/* MAIN WIKI AREA */

ob_start();
$wiki->handleAction($action);
if ($action == 'export_to_pdf') {
    $wiki->handleAction('showpage');
}
$content = ob_get_contents();
ob_end_clean();

Display::display_header($tool_name, 'Wiki');

// check last version
if (!empty($view)) {
    $wiki->checkLastVersion($view);
}

// Tool introduction
Display::display_introduction_section(TOOL_WIKI);

$wiki->showActionBar();
echo $content;

Display::display_footer();
