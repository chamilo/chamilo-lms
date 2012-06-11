<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                      moderation of posts (approval)
 *                      reply only forums (students cannot create new threads)
 *                      multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @package chamilo.forum
 */

// Language files that need to be included.
$language_file = array('forum', 'document');

// Including the global initialization file.
require_once '../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('ForumCategories');

$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
    $origin_string = '&amp;origin='.$origin;
}

/* Including necessary files */
require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

// javascript
$htmlHeadXtra[] = '<script>
        function advanced_parameters() {
            if(document.getElementById(\'id_qualify\').style.display == \'none\') {
                document.getElementById(\'id_qualify\').style.display = \'block\';
                document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';

            } else {
                document.getElementById(\'id_qualify\').style.display = \'none\';
                document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
            }
        }
</script>';

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum categorie information */
// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread	= get_thread_information($_GET['thread']); // Note: This has to be validated that it is an existing thread.
$current_forum	= get_forum_information($current_thread['forum_id']); // Note: This has to be validated that it is an existing forum.
$current_forum_category = get_forumcategory_information(Security::remove_XSS($current_forum['forum_category']));

/* Is the user allowed here? */
// The user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
//if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility'] == 0 OR $current_forum['visibility'] == 0) OR ($current_forum_category['locked'] <> 0 OR $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0))) {
if (!api_is_allowed_to_edit(false, true) AND (($current_forum_category && $current_forum_category['visibility'] == 0) OR $current_forum['visibility'] == 0)) {
    api_not_allowed();
}
if (!api_is_allowed_to_edit(false, true) AND (($current_forum_category && $current_forum_category['locked'] <> 0) OR $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0)) {
    api_not_allowed();
}
if (!$_user['user_id'] AND $current_forum['allow_anonymous'] == 0) {
    api_not_allowed();
}

if ($current_forum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(api_get_user_id(), $current_forum['forum_of_group'], GROUP_TOOL_FORUM);
    if (!$show_forum) {
        api_not_allowed();
    }
}


/* Breadcrumbs */

if (isset($_SESSION['gradebook'])){
    $gradebook = Security::remove_XSS($_SESSION['gradebook']);
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.Security::remove_XSS($_SESSION['gradebook_dest']),
            'name' => get_lang('ToolGradebook')
        );
}

if ($origin == 'group') {
    $_clean['toolgroup'] = (int)$_SESSION['toolgroup'];
    $group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => $current_forum['forum_title']);
    $interbreadcrumb[] = array('url' => 'viewthread.php?origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']), 'name' => $current_thread['thread_title']);
    $interbreadcrumb[] = array('url' => 'javascript: void(0);', 'name' => get_lang('Reply'));
} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook, 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'], 'name' => $current_forum_category['cat_title']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => $current_forum['forum_title']);
    $interbreadcrumb[] = array('url' => 'viewthread.php?origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']), 'name' => $current_thread['thread_title']);
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Reply'));
}

/* Resource Linker */

if (isset($_POST['add_resources']) AND $_POST['add_resources'] == get_lang('Resources')) {
    $_SESSION['formelements']  = $_POST;
    $_SESSION['origin']        = $_SERVER['REQUEST_URI'];
    $_SESSION['breadcrumbs']   = $interbreadcrumb;
    header('Location: ../resourcelinker/resourcelinker.php');
    exit;
}

/* Header */

if ($origin == 'learnpath') {    
    Display :: display_reduced_header('');
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give an empty string.
    Display :: display_header('');
}

/* Action links */

if ($origin != 'learnpath') {
    echo '<div class="actions">';
    echo '<span style="float:right;">'.search_link().'</span>';
    echo '<a href="viewthread.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;gradebook='.$gradebook.'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;origin='.$origin.'">'.Display::return_icon('back.png', get_lang('BackToThread'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';
} else {
    echo '<div style="height:15px">&nbsp;</div>';
}

// The form for the reply
$my_action   = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$my_post     = isset($_GET['post']) ?   Security::remove_XSS($_GET['post']) : '';
$my_elements = isset($_SESSION['formelements']) ? $_SESSION['formelements'] : '';

$values      = show_add_post_form($my_action, $my_post, $my_elements); // Note: This has to be cleaned first.

if (!empty($values) AND isset($_POST['SubmitPost'])) {
    $result = store_reply($values);
    //@todo split the show_add_post_form function
    
    $url = 'viewthread.php?forum='.$current_thread['forum_id'].'&gradebook='.$gradebook.'&thread='.intval($_GET['thread']).'&gidReq='.api_get_group_id().'&origin='.$origin.'&msg='.$result['msg'].'&type='.$result['type'];
    echo '
    <script type="text/javascript">
    window.location = "'.$url.'";
    </script>';    
    //header('Location: );
}

if ($origin != 'learnpath') {
    Display :: display_footer();
}