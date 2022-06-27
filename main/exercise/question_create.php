<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// the section (tabs)
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

$allow = api_is_allowed_to_edit();

if (!$allow) {
    api_not_allowed(true);
}

// breadcrumbs
$interbreadcrumb[] = ["url" => "exercise.php", "name" => get_lang('Exercises')];

// Tool name
$nameTools = get_lang('AddQuestionToExercise');

// The form
$form = new FormValidator('add_question', 'post', api_get_self().'?'.api_get_cidreq());
// form title
$form->addElement('header', '', get_lang('AddQuestionToExercise'));

$question_list = Question::getQuestionTypeList();
$question_list_options = [];
foreach ($question_list as $key => $value) {
    $question_list_options[$key] = addslashes(get_lang($value[1]));
}
$form->addElement(
    'select',
    'question_type_hidden',
    get_lang('QuestionType'),
    $question_list_options,
    ['id' => 'question_type_hidden']
);

//session id
$session_id = api_get_session_id();

// the exercises
$tbl_exercises = Database::get_course_table(TABLE_QUIZ_TEST);
$course_id = api_get_course_int_id();

$sql = "SELECT iid, title, type, description, results_disabled
        FROM $tbl_exercises
        WHERE c_id = $course_id AND active<>'-1' AND session_id = ".$session_id."
        ORDER BY title ASC";
$result = Database::query($sql);
$exercises['-'] = '-'.get_lang('SelectExercise').'-';
while ($row = Database::fetch_array($result)) {
    $exercises[$row['iid']] = cut($row['title'], EXERCISE_MAX_NAME_SIZE);
}
$form->addElement('select', 'exercise', get_lang('Exercise'), $exercises);

// generate default content
$form->addElement(
    'checkbox',
    'is_content',
    null,
    get_lang('GenerateDefaultContent'),
    ['checked' => true]
);

// the submit button
$form->addButtonCreate(get_lang('CreateQuestion'), 'SubmitCreateQuestion');

// setting the rules
$form->addRule('exercise', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('exercise', get_lang('YouHaveToSelectATest'), 'numeric');

$form->registerRule('validquestiontype', 'callback', 'check_question_type');
$form->addRule('question_type_hidden', get_lang('InvalidQuestionType'), 'validquestiontype');

if ($form->validate()) {
    $values = $form->exportValues();
    $answer_type = $values['question_type_hidden'];

    // check feedback_type from current exercise for type of question delineation
    $exercise_id = (int) $values['exercise'];
    $sql = "SELECT feedback_type FROM $tbl_exercises WHERE iid = $exercise_id";
    $rs_feedback_type = Database::query($sql);
    $row_feedback_type = Database::fetch_row($rs_feedback_type);
    $feedback_type = $row_feedback_type[0];

    // if question type does not belong to self-evaluation (immediate feedback) it'll send an error
    if (($answer_type == HOT_SPOT_DELINEATION && $feedback_type != 1) ||
        ($feedback_type == 1 && ($answer_type != HOT_SPOT_DELINEATION && $answer_type != UNIQUE_ANSWER))) {
        header('Location: question_create.php?'.api_get_cidreq().'&error=true');
        exit;
    }
    header('Location: admin.php?exerciseId='.$values['exercise'].'&newQuestion=yes&isContent='.$values['is_content'].'&answerType='.$answer_type);
    exit;
} else {
    // header
    Display::display_header($nameTools);

    echo '<div class="actions">';
    echo '<a href="exercise.php?show=test">'.Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';

    // displaying the form
    $form->display();

    // footer
    Display::display_footer();
}

function check_question_type($parameter)
{
    $question_list = Question::getQuestionTypeList();
    foreach ($question_list as $key => $value) {
        $valid_question_types[] = $key;
    }
    if (in_array($parameter, $valid_question_types)) {
        return true;
    } else {
        return false;
    }
}
