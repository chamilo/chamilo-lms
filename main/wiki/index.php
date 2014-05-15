<?php
/* For licensing terms, see /license.txt */
/**
 *	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * 	@author Juan Carlos Raña <herodoto@telefonica.net>
 *
 * 	@package chamilo.wiki
 */
/**
 * Code
 */
use \ChamiloSession as Session;

// name of the language file that needs to be included
$language_file = 'wiki';

// including the global initialization file
require_once '../inc/global.inc.php';
require_once 'wiki.inc.php';

global $charset;

$wiki = new Wiki();
$wiki->charset = $charset;

// section (for the tabs)
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_WIKI;
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$course_id = api_get_course_int_id();
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id);
$course_id = api_get_course_int_id();
$groupId = api_get_group_id();

// additional style information
$htmlHeadXtra[] ='<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'wiki/css/default.css"/>';

// javascript for advanced parameters menu
$htmlHeadXtra[] = '<script>
function advanced_parameters() {
    if (document.getElementById(\'options\').style.display == \'none\') {
        document.getElementById(\'options\').style.display = \'block\';
        document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif', get_lang('Hide'), array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
    } else {
        document.getElementById(\'options\').style.display = \'none\';
        document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif', get_lang('Show'), array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
    }
}
function setFocus() {
    $("#search_title").focus();
}

$(document).ready(function() {
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

/* TRACKING */
event_access_tool(TOOL_WIKI);

if ($groupId) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = array("url" => api_get_path(WEB_CODE_PATH)."group/group.php", "name" => get_lang('Groups'));
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."group/group_space.php?gidReq=".$groupId,
        "name" => get_lang('GroupSpace').' '.$group_properties['name']
    );
    //ensure this tool in groups whe it's private or deactivated
    if ($group_properties['wiki_state'] == 0) {
        api_not_allowed();
    } elseif ($group_properties['wiki_state']==2) {
        if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group(api_get_user_id(), api_get_group_id())) {
            api_not_allowed();
        }
    }
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true);

// The page we are dealing with
$page = isset($_GET['title']) ? $_GET['title']: 'index';
$action = isset($_GET['action']) ? $_GET['action'] : 'showpage';
$view = isset($_GET['view']) ? $_GET['view'] : null;

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
echo $wiki->getMessages();
echo $content;

Display::display_footer();
