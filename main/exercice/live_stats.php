<?php
/* See license terms in /license.txt */

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
$language_file = array('exercice','tracking');
require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';
$this_section = SECTION_COURSES;
$exercise_id = (isset($_GET['exerciseId']) && !empty($_GET['exerciseId'])) ? intval($_GET['exerciseId']) : 0;

// Access control
api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed();
}

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);

if (!$result) {
	api_not_allowed(true);	
}  

$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook", "name" => get_lang('Exercices'));
$interbreadcrumb[] = array ("url" => "admin.php?exerciseId=$exercise_id","name" => $objExercise->name);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// The header.
Display::display_header(get_lang('StudentsWhoAreTakingTheExerciseRightNow'));

//jqgrid will use this URL to do the selects

$minutes = 60;
$url     = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=get_live_stats&exercise_id='.$objExercise->id.'&minutes='.$minutes;

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('FirstName'), get_lang('LastName'), get_lang('Time'), get_lang('QuestionsAlreadyAnswered'), get_lang('Score'));

//Column config
$column_model   = array(
                        array('name'=>'firstname',  'index'=>'firstname',       'width'=>'100', 'align'=>'left'),
                        array('name'=>'lastname',   'index'=>'lastname',        'width'=>'100', 'align'=>'left'),
                        array('name'=>'start_date', 'index'=>'start_date',      'width'=>'100', 'align'=>'left'),
                        array('name'=>'question',   'index'=>'count_questions', 'width'=>'60', 'align'=>'left', 'sortable'=>'false'),
                        array('name'=>'score',      'index'=>'score',           'width'=>'50', 'align'=>'left','sortable'=>'false'),
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 
/*
//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?action=copy&id=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\'; 
                 }';
  */                      
?>
<script>

function refreshGrid() {
    var grid = $("#live_stats");
    grid.trigger("reloadGrid");
    t = setTimeout("refreshGrid()", 10000);
}

$(function() {
    <?php 
        echo Display::grid_js('live_stats',  $url, $columns, $column_model, $extra_params, array(), $action_links,true);       
    ?>
    refreshGrid();
});
</script>
<?php

$actions = '<a href="exercise_report.php?exerciseId='.intval($_GET['exerciseId']).'">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList'),'',ICON_SIZE_MEDIUM).'</a>';
echo $actions = Display::div($actions, array('class'=> 'actions'));

//echo Display::page_header($objExercise->name);
//echo Display::page_header(get_lang('StudentsWhoAreTakingTheExerciseRightNow'));
echo Display::grid_html('live_stats');  

Display::display_footer();
