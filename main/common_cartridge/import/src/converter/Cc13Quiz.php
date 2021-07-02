<?php
/* For licensing terms, see /license.txt */

class Cc13Quiz extends Cc13Entities 
{    
    
    public function generate_data() {
        
        $data = [];
        $instances = $this->generate_instances();
        if (!empty($instances)) {
            foreach ($instances as $instance) {
                if ($instance['is_question_bank'] == 0) {                    
                    $data[] = $this->get_quiz_data($instance);                    
                }
            }
        }
        return $data;
    }
    
    public function store_quiz($quiz) {
        
        $courseInfo = api_get_course_info();
        $exercise = new Exercise($courseInfo['real_id']);
        $title = Exercise::format_title_variable($quiz['title']);
        $exercise->updateTitle($title);
        $exercise->updateDescription('');
        $exercise->updateAttempts($quiz['max_attempts']);
        $exercise->updateFeedbackType(0);

        // Match shuffle question with chamilo
        if (isset($moduleValues['shufflequestions']) &&
            (int) $moduleValues['shufflequestions'] === 1
        ) {
            $exercise->setRandom(-1);
        } else {
            $exercise->setRandom(0);
        }
        $exercise->updateRandomAnswers(!empty($moduleValues['shuffleanswers']));
        // @todo divide to minutes
        $exercise->updateExpiredTime((int) $moduleValues['timelimit']);

        if ($moduleValues['questionsperpage'] == 1) {
            $exercise->updateType(2);
        } else {
            $exercise->updateType(1);
        }

        // Create the new Quiz
        $exercise->save();
        
        if (!empty($quiz['questions'])) {
            foreach ($quiz['questions'] as $question) {
                $qType = $question['type'];
                
                $types = ['unique_answer' => 1, 'multiple_answer' => 2];
                $questionType = $types[$qType];

                $questionInstance = Question::getInstance($questionType);
                if (empty($questionInstance)) {
                    continue;
                }

                $questionInstance->updateTitle($question['title']);
                $questionText = '';

                // Replace the path from @@PLUGINFILE@@ to a correct chamilo path
                $questionText = str_replace(
                    '@@PLUGINFILE@@',
                    '/courses/'.$courseInfo['path'].'/document/moodle',
                    $questionText
                );

                $questionInstance->updateDescription($questionText);
                $questionInstance->updateLevel(1);
                $questionInstance->updateCategory(0);

                //Save normal question if NOT media
                if ($questionInstance->type != MEDIA_QUESTION) {
                    $questionInstance->save($exercise);
                    // modify the exercise
                    $exercise->addToList($questionInstance->id);
                    $exercise->update_question_positions();
                }

                
                if ($qtype == 'unique_answer') {
                    $objAnswer = new Answer($questionInstance->id);
                    $questionWeighting = 0;
                    foreach ($question['answers'] as $slot => $answerValues) {
                        
                        $correct = (int) $answerValues['score'] ? (int) $answerValues['score'] : 0;
                        $answer = $answerValues['title'];
                        $comment = $answerValues['feedback'];
                        $weighting = $answerValues['score'];
                        $weighting = abs($weighting);
                        if ($weighting > 0) {
                            $questionWeighting += $weighting;
                        }
                        $goodAnswer = $correct ? true : false;

                        $objAnswer->createAnswer(
                            $answer,
                            $goodAnswer,
                            $comment,
                            $weighting,
                            $slot + 1,
                            null,
                            null,
                            ''
                        );
                    }
                    // saves the answers into the data base
                    $objAnswer->save();
                    // sets the total weighting of the question
                    $questionInstance->updateWeighting($questionWeighting);
                    $questionInstance->save($exercise);
                    
                }
                else {
                    
                    $objAnswer = new Answer($questionInstance->id);
                    $questionWeighting = 0;
                    foreach ($question['answers'] as $slot => $answerValues) {
                        
                            
                        $answer = $answerValues['title'];
                        $comment = $answerValues['feedback'];
                        $weighting = $answerValues['score'];
                        if ($weighting > 0) {
                            $questionWeighting += $weighting;
                        }
                        $goodAnswer = $weighting > 0;

                        $objAnswer->createAnswer(
                            $answer,
                            $goodAnswer,
                            $comment,
                            $weighting,
                            $slot + 1,
                            null,
                            null,
                            ''
                        );
                    }

                    // saves the answers into the data base
                    $objAnswer->save();
                    // sets the total weighting of the question
                    $questionInstance->updateWeighting($questionWeighting);
                    $questionInstance->save($exercise);
                    
                }

            }
        }
        
    }
    
    public function store_quizzes($quizzes) {                      
        if (!empty($quizzes)) {
            foreach ($quizzes as $quiz) {            
                $this->store_quiz($quiz);
            }                      
        }
    }
    
    public function get_quiz_data($instance) {
       
        $values = [];
        if (!empty($instance)) {                        
            if (!empty($instance['questions'])) {                
                $questions = [];
                foreach ($instance['questions'] as $question) {                                                          
                    $questions[$question['id']] = [
                        'title' => $question['title'],
                        'type' => $question['qtype'], 
                        'ponderation' => $question['defaultgrade'],
                        'answers' => $question['answers']
                    ];
                }
                
            }                        
            $values = [
                'id' => $instance['id'],
                'title' => $instance['title'],
                'timelimit' => $instance['options']['timelimit'],
                'max_attempts' => $instance['options']['max_attempts'],
                'questions' => $questions
            ];            
        }
        return $values;
    }
    

    private function generate_instances () {

        $last_instance_id = 0;
        $last_question_id = 0;
        $last_answer_id = 0;

        $instances = array();

        $types = array(TOOL_TYPE_QUIZ);

        foreach ($types as $type) {

            if (!empty(Cc1p3Convert::$instances['instances'][$type])) {

                foreach (Cc1p3Convert::$instances['instances'][$type] as $instance) {

                    if ($type == TOOL_TYPE_QUIZ) {
                        $is_question_bank = 0;
                    } else {
                        $is_question_bank = 1;
                    }

                    $assessment_file = $this->get_external_xml($instance['resource_indentifier']);

                    if (!empty($assessment_file)) {

                        $assessment = $this->load_xml_resource(Cc1p3Convert::$path_to_manifest_folder . DIRECTORY_SEPARATOR . $assessment_file);

                        if (!empty($assessment)) {

                            $replace_values = array('unlimited' => 0);

                            $questions = $this->get_questions($assessment, $last_question_id, $last_answer_id, dirname($assessment_file), $is_question_bank);
                            $question_count = count($questions);

                            if (!empty($question_count)) {

                                $last_instance_id++;

                                $instances[$instance['resource_indentifier']]['questions'] = $questions;
                                $instances[$instance['resource_indentifier']]['id'] = $last_instance_id;
                                $instances[$instance['resource_indentifier']]['title'] = $instance['title'];
                                $instances[$instance['resource_indentifier']]['is_question_bank'] = $is_question_bank;
                                $instances[$instance['resource_indentifier']]['options']['timelimit'] = $this->get_global_config($assessment, 'qmd_timelimit', 0);
                                $instances[$instance['resource_indentifier']]['options']['max_attempts'] = $this->get_global_config($assessment, 'cc_maxattempts', 0, $replace_values);
                            }
                        }
                    }
                }
            }
        }

        return $instances;
    }

    private function get_global_config ($assessment, $option, $default_value, $replace_values = '') {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());
        $metadata = $xpath->query('/xmlns:questestinterop/xmlns:assessment/xmlns:qtimetadata/xmlns:qtimetadatafield');

        foreach ($metadata as $field) {
            $field_label = $xpath->query('xmlns:fieldlabel', $field);
            $field_label = !empty($field_label->item(0)->nodeValue) ? $field_label->item(0)->nodeValue : '';

            if (strtolower($field_label) == strtolower($option)) {
                $field_entry = $xpath->query('xmlns:fieldentry', $field);
                $response = !empty($field_entry->item(0)->nodeValue) ? $field_entry->item(0)->nodeValue : '';
            }
        }

        $response = !empty($response) ? trim($response) : '';

        if (!empty($replace_values)) {
            foreach ($replace_values as $key => $value) {
                $response = ($key == $response) ? $value : $response;
            }
        }

        $response = empty($response) ? $default_value : $response;

        return $response;
    }

    private function get_questions ($assessment, &$last_question_id, &$last_answer_id, $root_path, $is_question_bank) {

        $questions = array();

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        if (!$is_question_bank) {
            $questions_items = $xpath->query('/xmlns:questestinterop/xmlns:assessment/xmlns:section/xmlns:item');
        } else {
            $questions_items = $xpath->query('/xmlns:questestinterop/xmlns:objectbank/xmlns:item');
        }

        foreach ($questions_items as $question_item) {

            $count_questions = $xpath->evaluate('count(xmlns:presentation/xmlns:flow/xmlns:material/xmlns:mattext)', $question_item);

            if ($count_questions == 0) {
                $question_title = $xpath->query('xmlns:presentation/xmlns:material/xmlns:mattext', $question_item);
            } else {
                $question_title = $xpath->query('xmlns:presentation/xmlns:flow/xmlns:material/xmlns:mattext', $question_item);
            }

            $question_title = !empty($question_title->item(0)->nodeValue) ? $question_title->item(0)->nodeValue : '';

            $question_identifier = $xpath->query('@ident', $question_item);
            $question_identifier = !empty($question_identifier->item(0)->nodeValue) ? $question_identifier->item(0)->nodeValue : '';

            if (!empty($question_identifier)) {

                $question_type = $this->get_question_type($question_identifier, $assessment);

                if (!empty($question_type['qtype'])) {

                    $last_question_id++;

                    $questions[$question_identifier]['id'] = $last_question_id;

                    $question_title = $this->update_sources($question_title, $root_path);
                    $question_title = !empty($question_title) ? str_replace("%24", "\$", $this->include_titles($question_title)) : '';

                    $questionname = $xpath->query('@title', $question_item);
                    $questionname = !empty($questionname->item(0)->nodeValue) ? $questionname->item(0)->nodeValue : '';

                    $questions[$question_identifier]['title'] = $question_title;
                    $questions[$question_identifier]['name'] = $questionname;
                    $questions[$question_identifier]['identifier'] = $question_identifier;
                    $questions[$question_identifier]['qtype'] = $question_type['qtype'];
                    $questions[$question_identifier]['cc_type'] = $question_type['cc'];
                    $questions[$question_identifier]['feedback'] = $this->get_general_feedback($assessment, $question_identifier);
                    $questions[$question_identifier]['defaultgrade'] = $this->get_defaultgrade($assessment, $question_identifier);
                    $questions[$question_identifier]['answers'] = $this->get_answers($question_identifier, $assessment, $last_answer_id);

                }
            }
        }

        $questions = !empty($questions) ? $questions : '';

        return $questions;
    }

    private function get_defaultgrade($assessment, $question_identifier) {
        $result = 1;
        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());
        $query = '//xmlns:item[@ident="' . $question_identifier . '"]';
        $query .= '//xmlns:qtimetadatafield[xmlns:fieldlabel="cc_weighting"]/xmlns:fieldentry';
        $defgrade = $xpath->query($query);
        if (!empty($defgrade) && ($defgrade->length > 0)) {
            $resp = (int)$defgrade->item(0)->nodeValue;
            if ($resp >= 0 && $resp <= 99) {
                $result = $resp;
            }
        }
        return $result;
    }

    private function get_general_feedback ($assessment, $question_identifier) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $respconditions = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($respconditions)) {

            foreach ($respconditions as $respcondition) {

                $continue = $respcondition->getAttributeNode('continue');
                $continue = !empty($continue->nodeValue) ? strtolower($continue->nodeValue) : '';

                if ($continue == 'yes') {

                    $display_feedback = $xpath->query('xmlns:displayfeedback', $respcondition);

                    if (!empty($display_feedback)) {
                        foreach ($display_feedback as $feedback) {

                            $feedback_identifier = $feedback->getAttributeNode('linkrefid');
                            $feedback_identifier = !empty($feedback_identifier->nodeValue) ? $feedback_identifier->nodeValue : '';

                            if (!empty($feedback_identifier)) {
                                $feedbacks_identifiers[] = $feedback_identifier;
                            }
                        }
                    }
                }
            }
        }

        $feedback = '';
        $feedbacks_identifiers = empty($feedbacks_identifiers) ? '' : $feedbacks_identifiers;

        if (!empty($feedbacks_identifiers)) {
            foreach ($feedbacks_identifiers as $feedback_identifier) {
                $feedbacks = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:itemfeedback[@ident="' . $feedback_identifier . '"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue . ' ' : '';
            }
        }

        return $feedback;
    }

    private function get_feedback ($assessment, $identifier, $item_identifier, $question_type) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $resource_processing = $xpath->query('//xmlns:item[@ident="' . $item_identifier . '"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($resource_processing)) {

            foreach ($resource_processing as $response) {

                $varequal = $xpath->query('xmlns:conditionvar/xmlns:varequal', $response);
                $varequal = !empty($varequal->item(0)->nodeValue) ? $varequal->item(0)->nodeValue : '';

                if (strtolower($varequal) == strtolower($identifier) || ($question_type == CC_QUIZ_ESSAY)) {

                    $display_feedback = $xpath->query('xmlns:displayfeedback', $response);

                    if (!empty($display_feedback)) {
                        foreach ($display_feedback as $feedback) {

                            $feedback_identifier = $feedback->getAttributeNode('linkrefid');
                            $feedback_identifier = !empty($feedback_identifier->nodeValue) ? $feedback_identifier->nodeValue : '';

                            if (!empty($feedback_identifier)) {
                                $feedbacks_identifiers[] = $feedback_identifier;
                            }
                        }
                    }
                }
            }
        }

        $feedback = '';
        $feedbacks_identifiers = empty($feedbacks_identifiers) ? '' : $feedbacks_identifiers;

        if (!empty($feedbacks_identifiers)) {
            foreach ($feedbacks_identifiers as $feedback_identifier) {
                $feedbacks = $xpath->query('//xmlns:item[@ident="' . $item_identifier . '"]/xmlns:itemfeedback[@ident="' . $feedback_identifier . '"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue . ' ' : '';
            }
        }

        return $feedback;
    }

    private function get_answers_fib ($question_identifier, $identifier, $assessment, &$last_answer_id) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $correctanswersfib = array();
        $incorrectanswersfib = array();

        $response_items = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:resprocessing/xmlns:respcondition');

        $correctrespcond = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:resprocessing/xmlns:respcondition/xmlns:setvar[text()="100"]/..');
        $correctanswers = $xpath->query('xmlns:conditionvar/xmlns:varequal', $correctrespcond->item(0));

        // Correct answers.
        foreach ($correctanswers as $correctans) {
            $answertitle = !empty($correctans->nodeValue) ? $correctans->nodeValue : '';
            if (empty($answertitle)) {
                continue;
            }

            $last_answer_id++;

            $correctanswersfib[$answertitle] = array(
                'id' => $last_answer_id,
                'title' => $answertitle,
                'score' => 1,
                'feedback' => '',
                'case' => 0);
        }

        // Handle incorrect answers and feedback for all items.
        foreach ($response_items as $response_item) {

            $setvar = $xpath->query('xmlns:setvar', $response_item);
            if (!empty($setvar->length) && $setvar->item(0)->nodeValue == '100') {
                // Skip the correct answer responsecondition.
                continue;
            }

            $varequal = $xpath->query('xmlns:conditionvar/xmlns:varequal', $response_item);
            if (empty($varequal->length)) {
                // Skip respcondition elements that don't have varequal containing an answer
                continue;
            }
            $answer_title = !empty($varequal->item(0)->nodeValue) ? $varequal->item(0)->nodeValue : '';

            $display_feedback = $xpath->query('xmlns:displayfeedback', $response_item);

            unset($feedbacks_identifiers);

            if (!empty($display_feedback)) {

                foreach ($display_feedback as $feedback) {

                    $feedback_identifier = $feedback->getAttributeNode('linkrefid');
                    $feedback_identifier = !empty($feedback_identifier->nodeValue) ? $feedback_identifier->nodeValue : '';

                    if (!empty($feedback_identifier)) {
                        $feedbacks_identifiers[] = $feedback_identifier;
                    }
                }
            }

            $feedback = '';
            $feedbacks_identifiers = empty($feedbacks_identifiers) ? '' : $feedbacks_identifiers;

            if (!empty($feedbacks_identifiers)) {
                foreach ($feedbacks_identifiers as $feedback_identifier) {
                    $feedbacks = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:itemfeedback[@ident="' . $feedback_identifier . '"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                    $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue . ' ' : '';
                }
            }

            if (array_key_exists($answer_title, $correctanswersfib)) {
                // Already a correct answer, just need the feedback for the correct answer.
                $correctanswerfib[$answer_title]['feedback'] = $feedback;
            } else {
                // Need to add an incorrect answer.
                $last_answer_id++;
                $incorrectanswersfib[] = array(
                    'id' => $last_answer_id,
                    'title' => $answer_title,
                    'score' => 0,
                    'feedback' => $feedback,
                    'case' => 0);
            }
        }

        $answers_fib = array_merge($correctanswersfib, $incorrectanswersfib);
        $answers_fib = empty($answers_fib) ? '' : $answers_fib;

        return $answers_fib;
    }

    private function get_answers_pattern_match ($question_identifier, $identifier, $assessment, &$last_answer_id) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $answers_fib = array();

        $response_items = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:resprocessing/xmlns:respcondition');

        foreach ($response_items as $response_item) {

            $setvar = $xpath->query('xmlns:setvar', $response_item);
            $setvar = is_object($setvar->item(0)) ? $setvar->item(0)->nodeValue : '';

            if ($setvar != '') {

                $last_answer_id++;

                $answer_title = $xpath->query('xmlns:conditionvar/xmlns:varequal[@respident="' . $identifier . '"]', $response_item);
                $answer_title = !empty($answer_title->item(0)->nodeValue) ? $answer_title->item(0)->nodeValue : '';

                if (empty($answer_title)) {
                    $answer_title = $xpath->query('xmlns:conditionvar/xmlns:varsubstring[@respident="' . $identifier . '"]', $response_item);
                    $answer_title = !empty($answer_title->item(0)->nodeValue) ? '*' . $answer_title->item(0)->nodeValue . '*' : '';
                }

                if (empty($answer_title)) {
                    $answer_title = '*';
                }

            $case = $xpath->query('xmlns:conditionvar/xmlns:varequal/@case', $response_item);
            $case = is_object($case->item(0)) ? $case->item(0)->nodeValue : 'no'
                                    ;
            $case = strtolower($case) == 'yes' ? 1 :
                            0;

                $display_feedback = $xpath->query('xmlns:displayfeedback', $response_item);

                unset($feedbacks_identifiers);

                if (!empty($display_feedback)) {

                    foreach ($display_feedback as $feedback) {

                        $feedback_identifier = $feedback->getAttributeNode('linkrefid');
                        $feedback_identifier = !empty($feedback_identifier->nodeValue) ? $feedback_identifier->nodeValue : '';

                        if (!empty($feedback_identifier)) {
                            $feedbacks_identifiers[] = $feedback_identifier;
                        }
                    }
                }

                $feedback = '';
                $feedbacks_identifiers = empty($feedbacks_identifiers) ? '' : $feedbacks_identifiers;

                if (!empty($feedbacks_identifiers)) {
                    foreach ($feedbacks_identifiers as $feedback_identifier) {
                        $feedbacks = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:itemfeedback[@ident="' . $feedback_identifier . '"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                        $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue . ' ' : '';
                    }
                }

                $answers_fib[] = array('id' => $last_answer_id,
                                       'title' => $answer_title,
                                       'score' => $setvar,
                                       'feedback' => $feedback,
                                       'case' => $case);
            }
        }

        $answers_fib = empty($answers_fib) ? '' : $answers_fib;

        return $answers_fib;
    }


    private function get_answers ($identifier, $assessment, &$last_answer_id) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $answers = array();

        $question_cc_type = $this->get_question_type($identifier, $assessment);
        $question_cc_type = $question_cc_type['cc'];
        $is_multiresponse = ($question_cc_type == CC_QUIZ_MULTIPLE_RESPONSE);

        if ($question_cc_type == CC_QUIZ_MULTIPLE_CHOICE || $is_multiresponse || $question_cc_type == CC_QUIZ_TRUE_FALSE) {

            $query_answers = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:response_lid/xmlns:render_choice/xmlns:response_label';
            $query_answers_with_flow = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:flow/xmlns:response_lid/xmlns:render_choice/xmlns:response_label';

            $query_indentifer = '@ident';
            $query_title = 'xmlns:material/xmlns:mattext';
        }

        if ($question_cc_type == CC_QUIZ_ESSAY) {

            $query_answers = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:response_str';
            $query_answers_with_flow = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:flow/xmlns:response_str';

            $query_indentifer = '@ident';
            $query_title = 'xmlns:render_fib';
        }

        if ($question_cc_type == CC_QUIZ_FIB || $question_cc_type == CC_QUIZ_PATTERN_MACHT) {

            $xpath_query = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:response_str/@ident';
            $xpath_query_with_flow = '//xmlns:item[@ident="' . $identifier . '"]/xmlns:presentation/xmlns:flow/xmlns:response_str/@ident';

            $count_response = $xpath->evaluate('count(' . $xpath_query_with_flow . ')');

            if ($count_response == 0) {
                $answer_identifier = $xpath->query($xpath_query);
            } else {
                $answer_identifier = $xpath->query($xpath_query_with_flow);
            }

            $answer_identifier = !empty($answer_identifier->item(0)->nodeValue) ? $answer_identifier->item(0)->nodeValue : '';

            if ($question_cc_type == CC_QUIZ_FIB) {
                $answers = $this->get_answers_fib ($identifier, $answer_identifier, $assessment, $last_answer_id);
            } else {
                $answers = $this->get_answers_pattern_match ($identifier, $answer_identifier, $assessment, $last_answer_id);
            }

        } else {

            $count_response = $xpath->evaluate('count(' . $query_answers_with_flow . ')');

            if ($count_response == 0) {
                $response_items = $xpath->query($query_answers);
            } else {
                $response_items = $xpath->query($query_answers_with_flow);
            }

            if (!empty($response_items)) {

                if ($is_multiresponse) {
                    $correct_answer_score = 0;
                    //get the correct answers count
                    $canswers_query = "//xmlns:item[@ident='{$identifier}']//xmlns:setvar[@varname='SCORE'][.=100]/../xmlns:conditionvar//xmlns:varequal[@case='Yes'][not(parent::xmlns:not)]";
                    $canswers = $xpath->query($canswers_query);
                    if ($canswers->length > 0) {
                        $correct_answer_score = round(1.0 / (float)$canswers->length, 7); //weird
                        $correct_answers_ident = array();
                        foreach ($canswers as $cnode) {
                            $correct_answers_ident[$cnode->nodeValue] = true;
                        }
                    }
                }

                foreach ($response_items as $response_item) {

                    $last_answer_id++;

                    $answer_identifier = $xpath->query($query_indentifer, $response_item);
                    $answer_identifier = !empty($answer_identifier->item(0)->nodeValue) ? $answer_identifier->item(0)->nodeValue : '';

                    $answer_title = $xpath->query($query_title, $response_item);
                    $answer_title = !empty($answer_title->item(0)->nodeValue) ? $answer_title->item(0)->nodeValue : '';

                    $answer_feedback = $this->get_feedback($assessment, $answer_identifier, $identifier, $question_cc_type);

                    $answer_score = $this->get_score($assessment, $answer_identifier, $identifier);

                    if ($is_multiresponse && isset($correct_answers_ident[$answer_identifier])) {
                        $answer_score = $correct_answer_score;
                    }

                    $answers[] = array('id' => $last_answer_id,
                                       'title' => $answer_title,
                                       'score' => $answer_score,
                                       'identifier' => $answer_identifier,
                                       'feedback' => $answer_feedback);
                }
            }
        }

        $answers = empty($answers) ? '' : $answers;

        return $answers;

    }

    private function get_score ($assessment, $identifier, $question_identifier) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $resource_processing = $xpath->query('//xmlns:item[@ident="' . $question_identifier . '"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($resource_processing)) {

            foreach ($resource_processing as $response) {

                $question_cc_type = $this->get_question_type($question_identifier, $assessment);
                $question_cc_type = $question_cc_type['cc'];

                $varequal = $xpath->query('xmlns:conditionvar/xmlns:varequal', $response);
                $varequal = !empty($varequal->item(0)->nodeValue) ? $varequal->item(0)->nodeValue : '';

                if (strtolower($varequal) == strtolower($identifier)) {
                    $score = $xpath->query('xmlns:setvar', $response);
                    $score = !empty($score->item(0)->nodeValue) ? $score->item(0)->nodeValue : '';
                }
            }
        }

        $score = empty($score) ? "0.0000000" : '1.0000000';

        return $score;
    }

    private function get_question_type ($identifier, $assessment) {

        $xpath = Cc1p3Convert::newx_path($assessment, Cc1p3Convert::getquizns());

        $metadata = $xpath->query('//xmlns:item[@ident="' . $identifier . '"]/xmlns:itemmetadata/xmlns:qtimetadata/xmlns:qtimetadatafield');

        foreach ($metadata as $field) {

            $field_label = $xpath->query('xmlns:fieldlabel', $field);
            $field_label = !empty($field_label->item(0)->nodeValue) ? $field_label->item(0)->nodeValue : '';

            if ($field_label == 'cc_profile') {
                $field_entry = $xpath->query('xmlns:fieldentry', $field);
                $type = !empty($field_entry->item(0)->nodeValue) ? $field_entry->item(0)->nodeValue : '';
            }
        }

        $return_type = array();

        $return_type['qtype'] = '';
        $return_type['cc'] = $type;

        if ($type == CC_QUIZ_MULTIPLE_CHOICE) {
            $return_type['qtype'] = 'unique_answer';
        }
        if ($type == CC_QUIZ_MULTIPLE_RESPONSE) {
            $return_type['qtype'] = 'multiple_answer';
        }

        return $return_type;

    }
}
