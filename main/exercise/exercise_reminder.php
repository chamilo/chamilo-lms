<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise reminder overview
 * Then it shows the results on the screen.
 *
 * @package chamilo.exercise
 *
 * @author Julio Montoya switchable fill in blank option added
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);
$origin = api_get_origin();

if (empty($learnpath_id)) {
    if (!empty($_REQUEST['learnpath_id'])) {
        $learnpath_id = (int) $_REQUEST['learnpath_id'];
    } else {
        $learnpath_id = 0;
    }
}
if (empty($learnpath_item_id)) {
    if (!empty($_REQUEST['learnpath_item_id'])) {
        $learnpath_item_id = (int) $_REQUEST['learnpath_item_id'];
    } else {
        $learnpath_item_id = 0;
    }
}
if (empty($learnpath_item_view_id)) {
    if (!empty($_REQUEST['learnpath_item_view_id'])) {
        $learnpath_item_view_id = (int) $_REQUEST['learnpath_item_view_id'];
    } else {
        $learnpath_item_view_id = 0;
    }
}

if (empty($exerciseId)) {
    if (!empty($_REQUEST['exerciseId'])) {
        $exerciseId = (int) $_REQUEST['exerciseId'];
    } else {
        $exerciseId = 0;
    }
}

if (empty($objExercise)) {
    $exerciseInSession = Session::read('objExercise');
    if (!empty($exerciseInSession)) {
        $objExercise = $exerciseInSession;
    } else {
        $objExercise = null;
    }
}

if (!$objExercise) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq());
    exit;
}

$time_control = false;
$clock_expired_time = ExerciseLib::get_session_time_control_key(
    $objExercise->id,
    $learnpath_id,
    $learnpath_item_id
);

if ($objExercise->expired_time != 0 && !empty($clock_expired_time)) {
    $time_control = true;
}

if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time, 'UTC') - time();
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}

$htmlHeadXtra[] = api_get_css_asset('pretty-checkbox/dist/pretty-checkbox.min.css');

$exe_id = 0;
if (isset($_GET['exe_id'])) {
    $exe_id = (int) $_GET['exe_id'];
    Session::write('exe_id', $exe_id);
}

$exe_id = (int) Session::read('exe_id');

$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
if (!empty($exercise_stat_info['data_tracking'])) {
    $question_list = explode(',', $exercise_stat_info['data_tracking']);
}

if (empty($exercise_stat_info) || empty($question_list)) {
    api_not_allowed();
}

$nameTools = get_lang('Exercises');
$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];

$hideHeaderAndFooter = in_array($origin, ['learnpath', 'embeddable']);

if (!$hideHeaderAndFooter) {
    //so we are not in learnpath tool
    Display::display_header($nameTools, get_lang('Exercise'));
} else {
    Display::display_reduced_header();
}

/* DISPLAY AND MAIN PROCESS */

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && !$hideHeaderAndFooter) {
    echo '<div class="actions">';
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32).'</a>';
    echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('edit.png', get_lang('ModifyExercise'), [], 32).'</a>';
    echo '</div>';
}
echo Display::page_header(get_lang('QuestionsToReview'));

if ($time_control) {
    echo $objExercise->return_time_left_div();
}

echo Display::div('', ['id' => 'message']);
echo '<script>
    var lp_data = $.param({"learnpath_id": '.$learnpath_id.', "learnpath_item_id" : '.$learnpath_item_id.', "learnpath_item_view_id": '.$learnpath_item_view_id.'});

    function final_submit() {
        //Normal inputs
        window.location = "'.api_get_path(WEB_CODE_PATH).'exercise/exercise_result.php?'.api_get_cidreq().'&exe_id='.$exe_id.'&" + lp_data;
    }
    
    function changeOptionStatus(status) 
    {    
        $("input[type=checkbox]").each(function () {                 
            $(this).prop("checked", status);                                                
        });    
        
        var action = ""; 
        var extraOption = "remove_all";       
        if (status == 1) {
            extraOption = "add_all";
        }     
        $.ajax({
            url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=add_question_to_reminder",
            data: "option="+extraOption+"&exe_id='.$exe_id.'&action="+action,
            success: function(returnValue) {
            }
        });  
    }

    function review_questions() {
        var isChecked = 1;
        $("input[type=checkbox]").each(function () {
            if ($(this).prop("checked")) {
                isChecked = 2;
                return false;
            }
        });

        if (isChecked == 1) {
            $("#message").addClass("warning-message");
            $("#message").html("'.addslashes(get_lang('SelectAQuestionToReview')).'");
        } else {
            window.location = "exercise_submit.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'&reminder=2&" + lp_data;
        }
    }

    function save_remind_item(obj, question_id) {
        var action = "";
        if ($(obj).prop("checked")) {
            action = "add";
        } else {
            action = "delete";
        }
        $.ajax({
            url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=add_question_to_reminder",
            data: "question_id="+question_id+"&exe_id='.$exe_id.'&action="+action,
            success: function(returnValue) {
            }
        });
    }
</script>';

$attempt_list = Event::getAllExerciseEventByExeId($exe_id);
$remind_list = $exercise_stat_info['questions_to_check'];
$remind_list = explode(',', $remind_list);
$exercise_result = [];

foreach ($attempt_list as $question_id => $options) {
    foreach ($options as $item) {
        $question_obj = Question::read($item['question_id']);
        switch ($question_obj->type) {
            case FILL_IN_BLANKS:
                $item['answer'] = $objExercise->fill_in_blank_answer_to_string($item['answer']);
                break;
            case HOT_SPOT:
                break;
        }

        if ($item['answer'] != '0' && !empty($item['answer'])) {
            $exercise_result[] = $question_id;
            break;
        }
    }
}
echo Display::label(get_lang('QuestionWithNoAnswer'), 'danger');
echo '<div class="clear"></div><br />';

$table = '';
$counter = 0;

// Loop over all question to show results for each of them, one by one
foreach ($question_list as $questionId) {
    // destruction of the Question object
    unset($objQuestionTmp);
    // creates a temporary Question object
    $objQuestionTmp = Question:: read($questionId);
    $quesId = $objQuestionTmp->selectId();
    $check_id = 'remind_list['.$questionId.']';

    $attributes = ['id' => $check_id, 'onclick' => "save_remind_item(this, '$questionId');"];
    if (in_array($questionId, $remind_list)) {
        $attributes['checked'] = 1;
    }

    $checkbox = Display::input('checkbox', 'remind_list['.$questionId.']', '', $attributes);

    $checkbox = '<div class="pretty p-default">
        '.$checkbox.'
        <div class="state p-primary ">
            <label>&nbsp;</label>
        </div>
    </div>';

    $url = 'exercise_submit.php?exerciseId='.$objExercise->id.'&num='.$counter.'&reminder=1&'.api_get_cidreq();

    $counter++;
    $questionTitle = $counter.'. '.strip_tags($objQuestionTmp->selectTitle());
    // Check if the question doesn't have an answer
    if (!in_array($questionId, $exercise_result)) {
        $questionTitle = Display::label($questionTitle, 'danger');
    }

    $label_attributes = [];
    $label_attributes['for'] = $check_id;
    $questionTitle = Display::tag('label', $checkbox.$questionTitle, $label_attributes);
    $table .= Display::div($questionTitle, ['class' => 'exercise_reminder_item ']);
} // end foreach() block that loops over all questions

echo Display::div($table, ['class' => 'question-check-test']);

$exerciseActions = Display::url(
    get_lang('ReviewQuestions'),
    'javascript://',
    ['onclick' => 'review_questions();', 'class' => 'btn btn-primary']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('SelectAll'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(1);', 'class' => 'btn btn-default']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('UnSelectAll'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(0);', 'class' => 'btn btn-default']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('EndTest'),
    'javascript://',
    ['onclick' => 'final_submit();', 'class' => 'btn btn-warning']
);

echo Display::div('', ['class' => 'clear']);
echo Display::div($exerciseActions, ['class' => 'form-actions']);

if (!$hideHeaderAndFooter) {
    // We are not in learnpath tool or embeddable quiz
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
