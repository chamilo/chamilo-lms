<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once 'wiki.inc.php';

global $charset;

$wiki = new WikiManager();
$wiki->charset = $charset;

$wiki->setBaseUrl(api_get_self().'?'.api_get_cidreq());

// Section / tool
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_WIKI;

// Context
$courseId  = api_get_course_int_id();
$sessionId = api_get_session_id();
$groupId   = api_get_group_id();

// Legacy CSS
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

// Small UI helpers
$htmlHeadXtra[] = '<script>
function setFocus(){ $("#search_title").focus(); }
$(function(){
  setFocus();
  $("#start_date_toggle").on("click", function(){ $("#start_date").toggle(); });
  $("#end_date_toggle").on("click", function(){ $("#end_date").toggle(); });
});
</script>';

// Access control
api_protect_course_script();
api_block_anonymous_users();
api_protect_course_group(GroupManager::GROUP_TOOL_WIKI);

// Tracking
Event::event_access_tool(TOOL_WIKI);

// Group breadcrumbs
if ($groupId) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.Security::remove_XSS($groupProperties['name']),
    ];
}

// Request params
$rawTitle = $_GET['title'] ?? null;
$reflink  = WikiManager::normalizeReflink($rawTitle);

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'showpage';
$view   = isset($_GET['view'])   ? Security::remove_XSS($_GET['view'])   : null;

// Set on instance
$wiki->page   = $reflink;
$wiki->action = $action;

// Preload historical view if any
if (!empty($view)) {
    $wiki->setWikiData($view);
}

// Concurrency lock
$wiki->blockConcurrentEditions(api_get_user_id(), $action);

// Header
$tool_name = get_lang('Wiki');
Display::display_header($tool_name, 'Wiki');

// “Not last version” hint
if (!empty($view)) {
    $wiki->checkLastVersion($view);
}

// Intro section
Display::display_introduction_section(TOOL_WIKI);

// Global action bar
echo '<div class="mb-4">';
$wiki->showActionBar();
echo '</div>';

// Main content: handleAction() PRINTS content (void)
echo '<div class="space-y-4">';
$wiki->handleAction($action);
echo '</div>';

// Footer
Display::display_footer();
