<?php
/**
 * This script contains a data filling procedure for an exercise
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 */
/**
 * Initialisation section
 */
/**
 * Loads the data and injects it into the Chamilo database, using the Chamilo
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted
 */
function fill_exe() {
    $qc = 5000; //how many questions to create per exercise
    $eol = PHP_EOL;
    $courses = array(); //declare only to avoid parsing notice
    $qst = array();
    require_once 'data_exe.php'; //fill the $users array
    $output = array();
    $output[] = array('title'=>'Exe Filling Report: ');
    $i = 1;
    $lib = api_get_path(SYS_CODE_PATH);
    require_once $lib.'exercice/exercise.class.php';
    require_once $lib.'exercice/question.class.php';
    require_once $lib.'exercice/answer.class.php';
    require_once $lib.'exercice/exercise.lib.php';
    foreach ($courses as $i => $course) {
        $res = 0;
        //first check that the first item doesn't exist already
    	$output[$i]['line-init'] = $course['title'];
        $res = CourseManager::create_course($course);
    	$output[$i]['line-info'] = ($res ? $res : get_lang('NotInserted'));
    	$i++;
        if (is_array($res)) {
            //now insert an exercise
            foreach ($course['exes'] as $exe) {
                $objExercise = new Exercise();
                $objExercise->id = 0;
                $objExercise->course_id = $res['real_id'];
                $objExercise->exercise = $exe['title'];
                $objExercise->type = 1;
                $objExercise->attempts = $exe['attempts'];
                $objExercise->random = $exe['random'];
                $objExercise->active = 1;
                $objExercise->propagate_neg = 0;
                $objExercise->pass_percentage = $exe['pass_percentage'];
                $objExercise->session_id = 0;
                $objExercise->results_disabled = 0;
                $objExercise->expired_time = $exe['time'];
                $objExercise->review_answers = $exe['review_answers'];
                $objExercise->save();
                $id = $objExercise->id;
    		if (!empty($id)) {
                    $qi = 0;
                    while ($qi < $qc) {
                        foreach ($qst as $q) {
                            error_log('Created '.$qi.' questions');
                            $question = Question::getInstance($q['type']); 
                            $question->id = 0;
                            $question->question = $q['title'].' '.$qi;
                            $question->description = $q['desc'];
                            $question->type = $q['type'];
                            $question->course = $res;
                            $r = $question->save($id);
                            if ($r === false) { continue; }
                            $qid = $question->id;
                            $aid = 1;
                            foreach ($q['answers'] as $asw) {
                                $answer = new UniqueAnswer($qid);
                                $answer->create_answer($aid,$qid,$asw['title'],'',$asw['score'],$asw['correct'], $res['real_id']);
                                $aid++;
                            }
                            $qi++;
                        }
                    }
                } 
            }
        }
    }
    return $output;
}
