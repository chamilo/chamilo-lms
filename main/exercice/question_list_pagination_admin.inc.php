<?php
/* For licensing terms, see /license.txt */
/**
*	Code library for HotPotatoes integration.
*	@package chamilo.exercise
*/

/**
*	QUESTION LIST ADMINISTRATION
*
*	This script allows to manage the question list
*	It is included from the script admin.php
*
*/

// deletes a question from the exercise (not from the data base)
if ($deleteQuestion) {
	// if the question exists
	if ($objQuestionTmp = Question::read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if ($objExercise->removeFromList($deleteQuestion)) {
			$nbrQuestions--;
		}
	}
	// destruction of the Question object
	unset($objQuestionTmp);
}

$token = Security::get_token();

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_question_list&exerciseId='.$exerciseId;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(get_lang('Questions'), get_lang('Type'), get_lang('Category'), get_lang('Difficulty'), get_lang('Score'), get_lang('Actions'));

//Column config
$column_model = array(
    array('name' => 'question', 'index' => 'question', 'width' => '300', 'align' => 'left'),
    array(
        'name'     => 'type',
        'index'    => 'type',
        'width'    => '100',
        'align'    => 'left',
        'sortable' => 'false'
    ),
    array(
        'name'     => 'category',
        'index'    => 'category',
        'width'    => '100',
        'align'    => 'left',
        'sortable' => 'false'
    ),
    array(
        'name'     => 'level',
        'index'    => 'level',
        'width'    => '50',
        'align'    => 'left',
        'sortable' => 'false'
    ),
    array(
        'name'     => 'score',
        'index'    => 'score',
        'width'    => '50',
        'align'    => 'left',
        'sortable' => 'false'
    ),
    array(
        'name'      => 'actions',
        'index'     => 'actions',
        'width'     => '50',
        'align'     => 'left',
        'formatter' => 'action_formatter',
        'sortable'  => 'false'
    )
);


//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

$courseCode = api_get_course_id();

$delete_link = null;
if ($objExercise->edit_exercise_in_lp == true) {
    $delete_link = '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?cidReq='.$courseCode.'&sec_token='.$token.'&deleteQuestion=\'+options.rowId+\'">'.Display::return_icon( 'delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
}
//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    return \'<a href="?exerciseId='.$exerciseId.'&myid=1&cidReq='.$courseCode.'&editQuestion=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;" href="?cidReq='.$courseCode.'&sec_token='.$token.'&clone_question=\'+options.rowId+\'">'.Display::return_icon('cd.gif',get_lang('Copy'), '',ICON_SIZE_SMALL).'</a>'.
    $delete_link.'\';
}';
?>
    <script>
        $(function () {
            <?php
                // grid definition see the $career->display() function
                echo Display::grid_js('question_list', $url, $columns, $column_model, $extra_params, array(), $action_links, true);
            ?>
        });
    </script>

    <div id="dialog-confirm" title="<?php echo get_lang("ConfirmYourChoice"); ?>" style="display:none;">
        <p>
            <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0; display:none;">
            </span>
            <?php echo get_lang("AreYouSureToDelete"); ?>
        </p>
    </div>

<?php

Question :: display_type_menu($objExercise);
echo Question::getMediaLabels();

echo '<br/><div style="clear:both;"></div>';
echo Display::grid_html('question_list');
