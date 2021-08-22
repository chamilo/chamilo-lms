<?php
/* For licensing terms, see /license.txt */

class Cc13Quiz extends Cc13Entities
{
    public function generateData()
    {
        $data = [];
        $instances = $this->generateInstances();
        if (!empty($instances)) {
            foreach ($instances as $instance) {
                if ($instance['is_question_bank'] == 0) {
                    $data[] = $this->getQuizData($instance);
                }
            }
        }

        return $data;
    }

    public function storeQuiz($quiz)
    {
        $courseInfo = api_get_course_info();
        $exercise = new Exercise($courseInfo['real_id']);
        $title = Exercise::format_title_variable($quiz['title']);
        $exercise->updateTitle($title);
        $exercise->updateDescription('');
        $exercise->updateAttempts($quiz['max_attempts']);
        $exercise->updateFeedbackType(0);

        $exercise->setRandom(0);
        $exercise->updateRandomAnswers(!empty($moduleValues['shuffleanswers']));
        $exercise->updateExpiredTime((int) $quiz['timelimit']);
        $exercise->updateType(1);

        // Create the new Quiz
        $exercise->save();

        if (!empty($quiz['questions'])) {
            foreach ($quiz['questions'] as $question) {
                $qtype = $question['type'];

                $types = ['unique_answer' => 1, 'multiple_answer' => 2];
                $questionType = $types[$qtype];

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
                    $exercise->addToList($questionInstance->iid);
                    $exercise->update_question_positions();
                }

                if ($qtype == 'unique_answer') {
                    $objAnswer = new Answer($questionInstance->iid);
                    $questionWeighting = 0;
                    foreach ($question['answers'] as $slot => $answerValues) {
                        $correct = $answerValues['score'] ? (int) $answerValues['score'] : 0;
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
                } else {
                    $objAnswer = new Answer($questionInstance->iid);
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

    public function storeQuizzes($quizzes)
    {
        if (!empty($quizzes)) {
            foreach ($quizzes as $quiz) {
                $this->storeQuiz($quiz);
            }
        }
    }

    public function getQuizData($instance)
    {
        $values = [];
        if (!empty($instance)) {
            $questions = [];
            if (!empty($instance['questions'])) {
                foreach ($instance['questions'] as $question) {
                    $questions[$question['id']] = [
                        'title' => $question['title'],
                        'type' => $question['qtype'],
                        'ponderation' => $question['defaultgrade'],
                        'answers' => $question['answers'],
                    ];
                }
            }
            $values = [
                'id' => $instance['id'],
                'title' => $instance['title'],
                'timelimit' => $instance['options']['timelimit'],
                'max_attempts' => $instance['options']['max_attempts'],
                'questions' => $questions,
            ];
        }

        return $values;
    }

    private function generateInstances()
    {
        $lastInstanceId = 0;
        $lastQuestionId = 0;
        $lastAnswerId = 0;

        $instances = [];

        $types = [TOOL_TYPE_QUIZ];

        foreach ($types as $type) {
            if (!empty(Cc1p3Convert::$instances['instances'][$type])) {
                foreach (Cc1p3Convert::$instances['instances'][$type] as $instance) {
                    if ($type == TOOL_TYPE_QUIZ) {
                        $is_question_bank = 0;
                    } else {
                        $is_question_bank = 1;
                    }

                    $assessmentFile = $this->getExternalXml($instance['resource_indentifier']);

                    if (!empty($assessmentFile)) {
                        $assessment = $this->loadXmlResource(Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$assessmentFile);

                        if (!empty($assessment)) {
                            $replaceValues = ['unlimited' => 0];

                            $questions = $this->getQuestions($assessment, $lastQuestionId, $lastAnswerId, dirname($assessmentFile), $is_question_bank);
                            $questionCount = count($questions);

                            if (!empty($questionCount)) {
                                $lastInstanceId++;

                                $instances[$instance['resource_indentifier']]['questions'] = $questions;
                                $instances[$instance['resource_indentifier']]['id'] = $lastInstanceId;
                                $instances[$instance['resource_indentifier']]['title'] = $instance['title'];
                                $instances[$instance['resource_indentifier']]['is_question_bank'] = $is_question_bank;
                                $instances[$instance['resource_indentifier']]['options']['timelimit'] = $this->getGlobalConfig($assessment, 'qmd_timelimit', 0);
                                $instances[$instance['resource_indentifier']]['options']['max_attempts'] = $this->getGlobalConfig($assessment, 'cc_maxattempts', 0, $replaceValues);
                            }
                        }
                    }
                }
            }
        }

        return $instances;
    }

    private function getGlobalConfig($assessment, $option, $defaultValue, $replaceValues = '')
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());
        $metadata = $xpath->query('/xmlns:questestinterop/xmlns:assessment/xmlns:qtimetadata/xmlns:qtimetadatafield');

        foreach ($metadata as $field) {
            $fieldLabel = $xpath->query('xmlns:fieldlabel', $field);
            $fieldLabel = !empty($fieldLabel->item(0)->nodeValue) ? $fieldLabel->item(0)->nodeValue : '';

            if (strtolower($fieldLabel) == strtolower($option)) {
                $fieldEntry = $xpath->query('xmlns:fieldentry', $field);
                $response = !empty($fieldEntry->item(0)->nodeValue) ? $fieldEntry->item(0)->nodeValue : '';
            }
        }

        $response = !empty($response) ? trim($response) : '';

        if (!empty($replaceValues)) {
            foreach ($replaceValues as $key => $value) {
                $response = ($key == $response) ? $value : $response;
            }
        }

        $response = empty($response) ? $defaultValue : $response;

        return $response;
    }

    private function getQuestions($assessment, &$lastQuestionId, &$last_answer_id, $rootPath, $is_question_bank)
    {
        $questions = [];

        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        if (!$is_question_bank) {
            $questionsItems = $xpath->query('/xmlns:questestinterop/xmlns:assessment/xmlns:section/xmlns:item');
        } else {
            $questionsItems = $xpath->query('/xmlns:questestinterop/xmlns:objectbank/xmlns:item');
        }

        foreach ($questionsItems as $question_item) {
            $countQuestions = $xpath->evaluate('count(xmlns:presentation/xmlns:flow/xmlns:material/xmlns:mattext)', $question_item);

            if ($countQuestions == 0) {
                $questionTitle = $xpath->query('xmlns:presentation/xmlns:material/xmlns:mattext', $question_item);
            } else {
                $questionTitle = $xpath->query('xmlns:presentation/xmlns:flow/xmlns:material/xmlns:mattext', $question_item);
            }

            $questionTitle = !empty($questionTitle->item(0)->nodeValue) ? $questionTitle->item(0)->nodeValue : '';

            $questionIdentifier = $xpath->query('@ident', $question_item);
            $questionIdentifier = !empty($questionIdentifier->item(0)->nodeValue) ? $questionIdentifier->item(0)->nodeValue : '';

            if (!empty($questionIdentifier)) {
                $questionType = $this->getQuestionType($questionIdentifier, $assessment);

                if (!empty($questionType['qtype'])) {
                    $lastQuestionId++;

                    $questions[$questionIdentifier]['id'] = $lastQuestionId;

                    $questionTitle = $this->updateSources($questionTitle, $rootPath);
                    $questionTitle = !empty($questionTitle) ? str_replace("%24", "\$", $this->includeTitles($questionTitle)) : '';

                    $questionname = $xpath->query('@title', $question_item);
                    $questionname = !empty($questionname->item(0)->nodeValue) ? $questionname->item(0)->nodeValue : '';

                    $questions[$questionIdentifier]['title'] = $questionTitle;
                    $questions[$questionIdentifier]['name'] = $questionname;
                    $questions[$questionIdentifier]['identifier'] = $questionIdentifier;
                    $questions[$questionIdentifier]['qtype'] = $questionType['qtype'];
                    $questions[$questionIdentifier]['cc_type'] = $questionType['cc'];
                    $questions[$questionIdentifier]['feedback'] = $this->getGeneralFeedback($assessment, $questionIdentifier);
                    $questions[$questionIdentifier]['defaultgrade'] = $this->getDefaultgrade($assessment, $questionIdentifier);
                    $questions[$questionIdentifier]['answers'] = $this->getAnswers($questionIdentifier, $assessment, $lastAnswerId);
                }
            }
        }

        $questions = !empty($questions) ? $questions : '';

        return $questions;
    }

    private function getDefaultgrade($assessment, $questionIdentifier)
    {
        $result = 1;
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());
        $query = '//xmlns:item[@ident="'.$questionIdentifier.'"]';
        $query .= '//xmlns:qtimetadatafield[xmlns:fieldlabel="cc_weighting"]/xmlns:fieldentry';
        $defgrade = $xpath->query($query);
        if (!empty($defgrade) && ($defgrade->length > 0)) {
            $resp = (int) $defgrade->item(0)->nodeValue;
            if ($resp >= 0 && $resp <= 99) {
                $result = $resp;
            }
        }

        return $result;
    }

    private function getGeneralFeedback($assessment, $questionIdentifier)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $respconditions = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($respconditions)) {
            foreach ($respconditions as $respcondition) {
                $continue = $respcondition->getAttributeNode('continue');
                $continue = !empty($continue->nodeValue) ? strtolower($continue->nodeValue) : '';

                if ($continue == 'yes') {
                    $displayFeedback = $xpath->query('xmlns:displayfeedback', $respcondition);

                    if (!empty($displayFeedback)) {
                        foreach ($displayFeedback as $feedback) {
                            $feedbackIdentifier = $feedback->getAttributeNode('linkrefid');
                            $feedbackIdentifier = !empty($feedbackIdentifier->nodeValue) ? $feedbackIdentifier->nodeValue : '';

                            if (!empty($feedbackIdentifier)) {
                                $feedbackIdentifiers[] = $feedbackIdentifier;
                            }
                        }
                    }
                }
            }
        }

        $feedback = '';
        $feedbackIdentifiers = empty($feedbackIdentifiers) ? '' : $feedbackIdentifiers;

        if (!empty($feedbackIdentifiers)) {
            foreach ($feedbackIdentifiers as $feedbackIdentifier) {
                $feedbacks = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:itemfeedback[@ident="'.$feedbackIdentifier.'"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue.' ' : '';
            }
        }

        return $feedback;
    }

    private function getFeedback($assessment, $identifier, $itemIdentifier, $questionType)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $resourceProcessing = $xpath->query('//xmlns:item[@ident="'.$itemIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($resourceProcessing)) {
            foreach ($resourceProcessing as $response) {
                $varequal = $xpath->query('xmlns:conditionvar/xmlns:varequal', $response);
                $varequal = !empty($varequal->item(0)->nodeValue) ? $varequal->item(0)->nodeValue : '';

                if (strtolower($varequal) == strtolower($identifier) || ($questionType == CC_QUIZ_ESSAY)) {
                    $displayFeedback = $xpath->query('xmlns:displayfeedback', $response);

                    if (!empty($displayFeedback)) {
                        foreach ($displayFeedback as $feedback) {
                            $feedbackIdentifier = $feedback->getAttributeNode('linkrefid');
                            $feedbackIdentifier = !empty($feedbackIdentifier->nodeValue) ? $feedbackIdentifier->nodeValue : '';

                            if (!empty($feedbackIdentifier)) {
                                $feedbackIdentifiers[] = $feedbackIdentifier;
                            }
                        }
                    }
                }
            }
        }

        $feedback = '';
        $feedbackIdentifiers = empty($feedbackIdentifiers) ? '' : $feedbackIdentifiers;

        if (!empty($feedbackIdentifiers)) {
            foreach ($feedbackIdentifiers as $feedbackIdentifier) {
                $feedbacks = $xpath->query('//xmlns:item[@ident="'.$itemIdentifier.'"]/xmlns:itemfeedback[@ident="'.$feedbackIdentifier.'"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue.' ' : '';
            }
        }

        return $feedback;
    }

    private function getAnswersFib($questionIdentifier, $identifier, $assessment, &$lastAnswerId)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $correctanswersfib = [];
        $incorrectanswersfib = [];

        $responseItems = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition');

        $correctrespcond = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition/xmlns:setvar[text()="100"]/..');
        $correctanswers = $xpath->query('xmlns:conditionvar/xmlns:varequal', $correctrespcond->item(0));

        // Correct answers.
        foreach ($correctanswers as $correctans) {
            $answertitle = !empty($correctans->nodeValue) ? $correctans->nodeValue : '';
            if (empty($answertitle)) {
                continue;
            }

            $lastAnswerId++;

            $correctanswersfib[$answertitle] = [
                'id' => $lastAnswerId,
                'title' => $answertitle,
                'score' => 1,
                'feedback' => '',
                'case' => 0, ];
        }

        // Handle incorrect answers and feedback for all items.
        foreach ($responseItems as $response_item) {
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
            $answerTitle = !empty($varequal->item(0)->nodeValue) ? $varequal->item(0)->nodeValue : '';

            $displayFeedback = $xpath->query('xmlns:displayfeedback', $response_item);

            unset($feedbackIdentifiers);

            if (!empty($displayFeedback)) {
                foreach ($displayFeedback as $feedback) {
                    $feedbackIdentifier = $feedback->getAttributeNode('linkrefid');
                    $feedbackIdentifier = !empty($feedbackIdentifier->nodeValue) ? $feedbackIdentifier->nodeValue : '';

                    if (!empty($feedbackIdentifier)) {
                        $feedbackIdentifiers[] = $feedbackIdentifier;
                    }
                }
            }

            $feedback = '';
            $feedbackIdentifiers = empty($feedbackIdentifiers) ? '' : $feedbackIdentifiers;

            if (!empty($feedbackIdentifiers)) {
                foreach ($feedbackIdentifiers as $feedbackIdentifier) {
                    $feedbacks = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:itemfeedback[@ident="'.$feedbackIdentifier.'"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                    $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue.' ' : '';
                }
            }

            if (array_key_exists($answerTitle, $correctanswersfib)) {
                // Already a correct answer, just need the feedback for the correct answer.
                $correctanswerfib[$answerTitle]['feedback'] = $feedback;
            } else {
                // Need to add an incorrect answer.
                $lastAnswerId++;
                $incorrectanswersfib[] = [
                    'id' => $lastAnswerId,
                    'title' => $answerTitle,
                    'score' => 0,
                    'feedback' => $feedback,
                    'case' => 0, ];
            }
        }

        $answersFib = array_merge($correctanswersfib, $incorrectanswersfib);
        $answersFib = empty($answersFib) ? '' : $answersFib;

        return $answersFib;
    }

    private function getAnswersPatternMatch($questionIdentifier, $identifier, $assessment, &$lastAnswerId)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $answersFib = [];

        $responseItems = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition');

        foreach ($responseItems as $response_item) {
            $setvar = $xpath->query('xmlns:setvar', $response_item);
            $setvar = is_object($setvar->item(0)) ? $setvar->item(0)->nodeValue : '';

            if ($setvar != '') {
                $lastAnswerId++;

                $answerTitle = $xpath->query('xmlns:conditionvar/xmlns:varequal[@respident="'.$identifier.'"]', $response_item);
                $answerTitle = !empty($answerTitle->item(0)->nodeValue) ? $answerTitle->item(0)->nodeValue : '';

                if (empty($answerTitle)) {
                    $answerTitle = $xpath->query('xmlns:conditionvar/xmlns:varsubstring[@respident="'.$identifier.'"]', $response_item);
                    $answerTitle = !empty($answerTitle->item(0)->nodeValue) ? '*'.$answerTitle->item(0)->nodeValue.'*' : '';
                }

                if (empty($answerTitle)) {
                    $answerTitle = '*';
                }

                $case = $xpath->query('xmlns:conditionvar/xmlns:varequal/@case', $response_item);
                $case = is_object($case->item(0)) ? $case->item(0)->nodeValue : 'no'
                                    ;
                $case = strtolower($case) == 'yes' ? 1 :
                            0;

                $displayFeedback = $xpath->query('xmlns:displayfeedback', $response_item);

                unset($feedbackIdentifiers);

                if (!empty($displayFeedback)) {
                    foreach ($displayFeedback as $feedback) {
                        $feedbackIdentifier = $feedback->getAttributeNode('linkrefid');
                        $feedbackIdentifier = !empty($feedbackIdentifier->nodeValue) ? $feedbackIdentifier->nodeValue : '';

                        if (!empty($feedbackIdentifier)) {
                            $feedbackIdentifiers[] = $feedbackIdentifier;
                        }
                    }
                }

                $feedback = '';
                $feedbackIdentifiers = empty($feedbackIdentifiers) ? '' : $feedbackIdentifiers;

                if (!empty($feedbackIdentifiers)) {
                    foreach ($feedbackIdentifiers as $feedbackIdentifier) {
                        $feedbacks = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:itemfeedback[@ident="'.$feedbackIdentifier.'"]/xmlns:flow_mat/xmlns:material/xmlns:mattext');
                        $feedback .= !empty($feedbacks->item(0)->nodeValue) ? $feedbacks->item(0)->nodeValue.' ' : '';
                    }
                }

                $answersFib[] = ['id' => $lastAnswerId,
                                       'title' => $answerTitle,
                                       'score' => $setvar,
                                       'feedback' => $feedback,
                                       'case' => $case, ];
            }
        }

        $answersFib = empty($answersFib) ? '' : $answersFib;

        return $answersFib;
    }

    private function getAnswers($identifier, $assessment, &$lastAnswerId)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $answers = [];

        $questionCcType = $this->getQuestionType($identifier, $assessment);
        $questionCcType = $questionCcType['cc'];
        $isMultiresponse = ($questionCcType == CC_QUIZ_MULTIPLE_RESPONSE);

        if ($questionCcType == CC_QUIZ_MULTIPLE_CHOICE || $isMultiresponse || $questionCcType == CC_QUIZ_TRUE_FALSE) {
            $queryAnswers = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:response_lid/xmlns:render_choice/xmlns:response_label';
            $queryAnswersWithFlow = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:flow/xmlns:response_lid/xmlns:render_choice/xmlns:response_label';

            $queryIndentifer = '@ident';
            $queryTitle = 'xmlns:material/xmlns:mattext';
        }

        if ($questionCcType == CC_QUIZ_ESSAY) {
            $queryAnswers = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:response_str';
            $queryAnswersWithFlow = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:flow/xmlns:response_str';

            $queryIndentifer = '@ident';
            $queryTitle = 'xmlns:render_fib';
        }

        if ($questionCcType == CC_QUIZ_FIB || $questionCcType == CC_QUIZ_PATTERN_MACHT) {
            $xpathQuery = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:response_str/@ident';
            $xpathQueryWithFlow = '//xmlns:item[@ident="'.$identifier.'"]/xmlns:presentation/xmlns:flow/xmlns:response_str/@ident';

            $countResponse = $xpath->evaluate('count('.$xpathQueryWithFlow.')');

            if ($countResponse == 0) {
                $answerIdentifier = $xpath->query($xpathQuery);
            } else {
                $answerIdentifier = $xpath->query($xpathQueryWithFlow);
            }

            $answerIdentifier = !empty($answerIdentifier->item(0)->nodeValue) ? $answerIdentifier->item(0)->nodeValue : '';

            if ($questionCcType == CC_QUIZ_FIB) {
                $answers = $this->getAnswersFib($identifier, $answerIdentifier, $assessment, $lastAnswerId);
            } else {
                $answers = $this->getAnswersPatternMatch($identifier, $answerIdentifier, $assessment, $lastAnswerId);
            }
        } else {
            $countResponse = $xpath->evaluate('count('.$queryAnswersWithFlow.')');

            if ($countResponse == 0) {
                $responseItems = $xpath->query($queryAnswers);
            } else {
                $responseItems = $xpath->query($queryAnswersWithFlow);
            }

            if (!empty($responseItems)) {
                if ($isMultiresponse) {
                    $correctAnswerScore = 0;
                    //get the correct answers count
                    $canswers_query = "//xmlns:item[@ident='{$identifier}']//xmlns:setvar[@varname='SCORE'][.=100]/../xmlns:conditionvar//xmlns:varequal[@case='Yes'][not(parent::xmlns:not)]";
                    $canswers = $xpath->query($canswers_query);
                    if ($canswers->length > 0) {
                        $correctAnswerScore = round(1.0 / (float) $canswers->length, 7); //weird
                        $correctAanswersIdent = [];
                        foreach ($canswers as $cnode) {
                            $correctAanswersIdent[$cnode->nodeValue] = true;
                        }
                    }
                }

                foreach ($responseItems as $response_item) {
                    $lastAnswerId++;

                    $answerIdentifier = $xpath->query($queryIndentifer, $response_item);
                    $answerIdentifier = !empty($answerIdentifier->item(0)->nodeValue) ? $answerIdentifier->item(0)->nodeValue : '';

                    $answerTitle = $xpath->query($queryTitle, $response_item);
                    $answerTitle = !empty($answerTitle->item(0)->nodeValue) ? $answerTitle->item(0)->nodeValue : '';

                    $answerFeedback = $this->getFeedback($assessment, $answerIdentifier, $identifier, $questionCcType);

                    $answer_score = $this->getScore($assessment, $answerIdentifier, $identifier);

                    if ($isMultiresponse && isset($correctAanswersIdent[$answerIdentifier])) {
                        $answer_score = $correctAnswerScore;
                    }

                    $answers[] = ['id' => $lastAnswerId,
                                       'title' => $answerTitle,
                                       'score' => $answer_score,
                                       'identifier' => $answerIdentifier,
                                       'feedback' => $answerFeedback, ];
                }
            }
        }

        $answers = empty($answers) ? '' : $answers;

        return $answers;
    }

    private function getScore($assessment, $identifier, $questionIdentifier)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $resourceProcessing = $xpath->query('//xmlns:item[@ident="'.$questionIdentifier.'"]/xmlns:resprocessing/xmlns:respcondition');

        if (!empty($resourceProcessing)) {
            foreach ($resourceProcessing as $response) {
                $questionCcType = $this->getQuestionType($questionIdentifier, $assessment);
                $questionCcType = $questionCcType['cc'];

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

    private function getQuestionType($identifier, $assessment)
    {
        $xpath = Cc1p3Convert::newxPath($assessment, Cc1p3Convert::getquizns());

        $metadata = $xpath->query('//xmlns:item[@ident="'.$identifier.'"]/xmlns:itemmetadata/xmlns:qtimetadata/xmlns:qtimetadatafield');

        foreach ($metadata as $field) {
            $fieldLabel = $xpath->query('xmlns:fieldlabel', $field);
            $fieldLabel = !empty($fieldLabel->item(0)->nodeValue) ? $fieldLabel->item(0)->nodeValue : '';

            if ($fieldLabel == 'cc_profile') {
                $fieldEntry = $xpath->query('xmlns:fieldentry', $field);
                $type = !empty($fieldEntry->item(0)->nodeValue) ? $fieldEntry->item(0)->nodeValue : '';
            }
        }

        $returnType = [];

        $returnType['qtype'] = '';
        $returnType['cc'] = $type;

        if ($type == CC_QUIZ_MULTIPLE_CHOICE) {
            $returnType['qtype'] = 'unique_answer';
        }
        if ($type == CC_QUIZ_MULTIPLE_RESPONSE) {
            $returnType['qtype'] = 'multiple_answer';
        }

        return $returnType;
    }
}
