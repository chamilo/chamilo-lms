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
 * - quoting a message.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @copyright Ghent University
 */
require_once __DIR__.'/../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Including additional library scripts.
include 'forumfunction.inc.php';

// Are we in a lp ?
$origin = api_get_origin();

// Name of the tool
$nameTools = get_lang('ToolForum');

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$groupId = api_get_group_id();

if ($origin == 'group') {
    $group_properties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' ('.$group_properties['name'].')',
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?origin='.$origin.'&forum='.intval($_GET['forum']).'&'.api_get_cidreq(),
        'name' => prepare4display($current_forum['forum_title']),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/forumsearch.php?'.api_get_cidreq(),
        'name' => get_lang('ForumSearch'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq(),
        'name' => $nameTools,
    ];
    $nameTools = get_lang('ForumSearch');
}

// Display the header.
if ('learnpath' == $origin) {
    Display::display_reduced_header();
} else {
    Display::display_header($nameTools);
}

// Tool introduction
Display::display_introduction_section(TOOL_FORUM);

// Tracking
Event::event_access_tool(TOOL_FORUM);

// Forum search
forum_search();

// Footer
if ('learnpath' != $origin) {
    Display::display_footer();
}
