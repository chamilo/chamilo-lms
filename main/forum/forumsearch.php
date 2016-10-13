<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                       moderation of posts (approval)
 *                       reply only forums (students cannot create new threads)
 *                       multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @copyright Ghent University
 *
 *  @package chamilo.forum
 */

require_once '../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Including additional library scripts.
include 'forumfunction.inc.php';
include 'forumconfig.inc.php';

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

// Name of the tool
$nameTools = get_lang('ToolForum');

// Breadcrumbs

if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$groupId = api_get_group_id();

if ($origin == 'group') {
    $group_properties  = GroupManager :: get_group_properties($groupId);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(), 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(), 'name' => get_lang('GroupSpace').' ('.$group_properties['name'].')');
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?origin='.$origin.'&forum='.intval($_GET['forum']).'&'.api_get_cidreq(), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/forumsearch.php?'.api_get_cidreq(),'name' => get_lang('ForumSearch'));
} else {
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq(), 'name' => $nameTools);
    $nameTools = get_lang('ForumSearch');
}

// Display the header.
if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display :: display_header($nameTools);
}

// Tool introduction
Display::display_introduction_section(TOOL_FORUM);

// Tracking
Event::event_access_tool(TOOL_FORUM);

// Forum search
forum_search();

// Footer
if ($origin != 'learnpath') {
    Display :: display_footer();
}
