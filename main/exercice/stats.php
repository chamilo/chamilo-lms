<?php

require_once 'exercise.class.php';
require_once 'exercise.lib.php';
require_once 'question.class.php';
require_once 'answer.class.php';

$language_file = 'exercice';


require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$exercise_id = (isset($_GET['exerciseId']) && !empty($_GET['exerciseId'])) ? intval($_GET['exerciseId']) : 0;

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);

if (!$result) {
	api_not_allowed(true);	
}  

$students = CourseManager :: get_student_list_from_course_code(api_get_course_id(), false);

$question_list = $objExercise->get_validated_question_list();

$data = array();
//Question title 	# of students who tool it 	Lowest score 	Average 	Highest score 	Maximum score
$headers = array(
	get_lang('Question'), 
	get_lang('NumberOfStudentWhoTryTheExercise'),
	get_lang('LowestScore'), 
	get_lang('Average'),
	get_lang('HighestScore'),
	get_lang('MaximumScore')	
);


if (!empty($question_list)) {
	foreach($question_list as $question_id) {
		$question_obj = Question::read($question_id);
		$exercise_stats = get_student_stats_by_question($question_id, $exercise_id, api_get_course_id(), api_get_session_id());
		
		$data[$question_id]['name'] 						= cut($question_obj->question, 100);
		$data[$question_id]['students_who_try_exercise'] 	= $exercise_stats['users'];
		$data[$question_id]['lowest_score'] 				= $exercise_stats['min'];
		$data[$question_id]['average_score'] 				= $exercise_stats['average'];
		$data[$question_id]['highest_score'] 				= $exercise_stats['max'];
		$data[$question_id]['max_score'] 					= $question_obj->weighting;
	}
}

//Format A table
$table = new HTML_Table(array('class' => 'data_table'));
$row = 0;
$column = 0;
foreach ($headers as $header) {
	$table->setHeaderContents($row, $column, $header);
	$column++;
}
$row++;
foreach ($data as $row_table) {	
	$column = 0;
	foreach ($row_table as $cell) {		
		$table->setCellContents($row, $column, $cell);
		$table->updateCellAttributes($row, $column, 'align="center"');
		$column++;
	}
	$table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
	$row++;
}
$content = $table->toHtml();

//Format B

$headers = array(
	get_lang('Question'),
	get_lang('Answer'),
	get_lang('Correct'),
	get_lang('NumberStudentWhoSelect'),
	get_lang('HighestScore'),
	get_lang('MaximumScore')
);



$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook", "name" => get_lang('Exercices'));
$interbreadcrumb[] = array ("url" => "admin.php?exerciseId=$exercise_id","name" => $objExercise->name);

$tpl = new Template(get_lang('Stats'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();