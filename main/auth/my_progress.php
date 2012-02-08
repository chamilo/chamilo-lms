<?php
/* For licensing terms, see /license.txt */
/**
 * Reporting page on the user's own progress
 * @package chamilo.tracking
 */
/**
 * Code
 */

// name of the language file that needs to be included
$language_file = array('registration', 'tracking', 'exercice', 'admin', 'learnpath');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
      
$this_section = SECTION_TRACKING;

$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_jquery_ui_js();
$htmlHeadXtra[] = '
<script language="javascript">
$(function() {
    $(".dialog").dialog("destroy");        
    $(".dialog").dialog({
            autoOpen: false,
            show: "blind",                
            resizable: false,
            height:300,
            width:550,
            modal: true
     });     

    $(".opener").click(function() {
        var my_id = $(this).attr(\'id\'); 
        var big_image = \'#main_graph_\' + my_id;
        $( big_image ).dialog("open");
        return false;
    });
});
</script>';

$content = Tracking::show_user_progress(api_get_user_id());
if (!empty($content)) {
    $content .= '<br /><br />';
}
$content .= Tracking::show_course_detail(api_get_user_id(), $_GET['course'], $_GET['session_id']);

if (empty($content)) {
    $message = Display::return_message(get_lang('NoDataAvailable'), 'warning');
}
    
$tpl = new Template($tool_name);

//$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
