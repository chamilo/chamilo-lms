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

// Language files that need to be included.
$language_file = array ('forum', 'group');

// Including the global initialiation file.
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

if (!empty ($_GET['gidReq'])) {
    $toolgroup = Database::escape_string($_GET['gidReq']);
    Session::write('toolgroup',$toolgroup);
}

if ($origin == 'group') {
    $_clean['toolgroup']=(int)$_SESSION['toolgroup'];
    $group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name' => get_lang('GroupSpace').' ('.$group_properties['name'].')');
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => 'forumsearch.php','name' => get_lang('ForumSearch'));
} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook.'', 'name' => $nameTools);
    //$interbreadcrumb[] = array('url' => 'forumsearch.php', 'name' => );
    $nameTools = get_lang('ForumSearch');
}

// Display the header.
if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display :: display_header($nameTools);
}

// Display the tool title.
// api_display_tool_title($nameTools);

// Tool introduction
Display::display_introduction_section(TOOL_FORUM);

// Tracking
event_access_tool(TOOL_FORUM);

// Forum search
forum_search();

// Footer
if ($origin != 'learnpath') {
    Display :: display_footer();
}
