<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;
use FillBlanks;

/**
 * Class QuizExport.
 *
 * Handles the export of quizzes within a course.
 */
class QuizExport extends ActivityExport
{
    /**
     * Export a quiz to the specified directory.
     *
     * @param int    $activityId The ID of the quiz.
     * @param string $exportDir  The directory where the quiz will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the quiz export will be saved
        $quizDir = $this->prepareActivityDirectory($exportDir, 'quiz', $moduleId);

        // Retrieve quiz data
        $quizData = $this->getData($activityId, $sectionId);

        // Generate XML files
        $this->createQuizXml($quizData, $quizDir);
        $this->createModuleXml($quizData, $quizDir);
        $this->createGradesXml($quizData, $quizDir);
        $this->createCompletionXml($quizData, $quizDir);
        $this->createCommentsXml($quizData, $quizDir);
        $this->createCompetenciesXml($quizData, $quizDir);
        $this->createFiltersXml($quizData, $quizDir);
        $this->createGradeHistoryXml($quizData, $quizDir);
        $this->createInforefXml($quizData, $quizDir);
        $this->createRolesXml($quizData, $quizDir);
        $this->createCalendarXml($quizData, $quizDir);
    }

    /**
     * Retrieves the quiz data.
     */
    public function getData(int $quizId, int $sectionId): array
    {
        $quizResources = $this->course->resources[RESOURCE_QUIZ];

        foreach ($quizResources as $quiz) {
            if ($quiz->obj->iid == -1) {
                continue;
            }

            if ($quiz->obj->iid == $quizId) {
                $contextid = $quiz->obj->c_id;

                return [
                    'id' => $quiz->obj->iid,
                    'name' => $quiz->obj->title,
                    'intro' => $quiz->obj->description,
                    'timeopen' => $quiz->obj->start_time ?? 0,
                    'timeclose' => $quiz->obj->end_time ?? 0,
                    'timelimit' => $quiz->obj->timelimit ?? 0,
                    'grademethod' => $quiz->obj->grademethod ?? 1,
                    'decimalpoints' => $quiz->obj->decimalpoints ?? 2,
                    'sumgrades' => $quiz->obj->sumgrades ?? 0,
                    'grade' => $quiz->obj->grade ?? 0,
                    'questionsperpage' => $quiz->obj->questionsperpage ?? 1,
                    'preferredbehaviour' => $quiz->obj->preferredbehaviour ?? 'deferredfeedback',
                    'shuffleanswers' => $quiz->obj->shuffleanswers ?? 1,
                    'questions' => $this->getQuestionsForQuiz($quizId),
                    'feedbacks' => $this->getFeedbacksForQuiz($quizId),
                    'sectionid' => $sectionId,
                    'moduleid' => $quiz->obj->iid ?? 0,
                    'modulename' => 'quiz',
                    'contextid' => $contextid,
                    'overduehandling' => $quiz->obj->overduehandling ?? 'autosubmit',
                    'graceperiod' => $quiz->obj->graceperiod ?? 0,
                    'canredoquestions' => $quiz->obj->canredoquestions ?? 0,
                    'attempts_number' => $quiz->obj->attempts_number ?? 0,
                    'attemptonlast' => $quiz->obj->attemptonlast ?? 0,
                    'questiondecimalpoints' => $quiz->obj->questiondecimalpoints ?? 2,
                    'reviewattempt' => $quiz->obj->reviewattempt ?? 0,
                    'reviewcorrectness' => $quiz->obj->reviewcorrectness ?? 0,
                    'reviewmarks' => $quiz->obj->reviewmarks ?? 0,
                    'reviewspecificfeedback' => $quiz->obj->reviewspecificfeedback ?? 0,
                    'reviewgeneralfeedback' => $quiz->obj->reviewgeneralfeedback ?? 0,
                    'reviewrightanswer' => $quiz->obj->reviewrightanswer ?? 0,
                    'reviewoverallfeedback' => $quiz->obj->reviewoverallfeedback ?? 0,
                    'timecreated' => $quiz->obj->insert_date ?? time(),
                    'timemodified' => $quiz->obj->lastedit_date ?? time(),
                    'password' => $quiz->obj->password ?? '',
                    'subnet' => $quiz->obj->subnet ?? '',
                    'browsersecurity' => $quiz->obj->browsersecurity ?? '-',
                    'delay1' => $quiz->obj->delay1 ?? 0,
                    'delay2' => $quiz->obj->delay2 ?? 0,
                    'showuserpicture' => $quiz->obj->showuserpicture ?? 0,
                    'showblocks' => $quiz->obj->showblocks ?? 0,
                    'completionattemptsexhausted' => $quiz->obj->completionattemptsexhausted ?? 0,
                    'completionpass' => $quiz->obj->completionpass ?? 0,
                    'completionminattempts' => $quiz->obj->completionminattempts ?? 0,
                    'allowofflineattempts' => $quiz->obj->allowofflineattempts ?? 0,
                    'users' => [],
                    'files' => [],
                ];
            }
        }

        return [];
    }

    /**
     * Exports a question in XML format.
     */
    public function exportQuestion(array $question): string
    {
        $xmlContent = '      <question id="'.($question['id'] ?? '0').'">'.PHP_EOL;
        $xmlContent .= '        <parent>0</parent>'.PHP_EOL;
        $xmlContent .= '        <name>'.htmlspecialchars($question['questiontext'] ?? 'No question text').'</name>'.PHP_EOL;
        $xmlContent .= '        <questiontext>'.htmlspecialchars($question['questiontext'] ?? 'No question text').'</questiontext>'.PHP_EOL;
        $xmlContent .= '        <questiontextformat>1</questiontextformat>'.PHP_EOL;
        $xmlContent .= '        <generalfeedback></generalfeedback>'.PHP_EOL;
        $xmlContent .= '        <generalfeedbackformat>1</generalfeedbackformat>'.PHP_EOL;
        $xmlContent .= '        <defaultmark>'.($question['maxmark'] ?? '0').'</defaultmark>'.PHP_EOL;
        $xmlContent .= '        <penalty>0.3333333</penalty>'.PHP_EOL;
        $xmlContent .= '        <qtype>'.htmlspecialchars(str_replace('_nosingle', '', $question['qtype']) ?? 'unknown').'</qtype>'.PHP_EOL;
        $xmlContent .= '        <length>1</length>'.PHP_EOL;
        $xmlContent .= '        <stamp>moodle+'.time().'+QUESTIONSTAMP</stamp>'.PHP_EOL;
        $xmlContent .= '        <version>moodle+'.time().'+VERSIONSTAMP</version>'.PHP_EOL;
        $xmlContent .= '        <hidden>0</hidden>'.PHP_EOL;
        $xmlContent .= '        <timecreated>'.time().'</timecreated>'.PHP_EOL;
        $xmlContent .= '        <timemodified>'.time().'</timemodified>'.PHP_EOL;
        $xmlContent .= '        <createdby>2</createdby>'.PHP_EOL;
        $xmlContent .= '        <modifiedby>2</modifiedby>'.PHP_EOL;

        // Add question type-specific content
        switch ($question['qtype']) {
            case 'multichoice':
                $xmlContent .= $this->exportMultichoiceQuestion($question);
                break;
            case 'multichoice_nosingle':
                $xmlContent .= $this->exportMultichoiceNosingleQuestion($question);
                break;
            case 'truefalse':
                $xmlContent .= $this->exportTrueFalseQuestion($question);
                break;
            case 'shortanswer':
                $xmlContent .= $this->exportShortAnswerQuestion($question);
                break;
            case 'match':
                $xmlContent .= $this->exportMatchQuestion($question);
                break;
        }

        $xmlContent .= '      </question>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Retrieves the questions for a specific quiz.
     */
    private function getQuestionsForQuiz(int $quizId): array
    {
        $questions = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionId => $questionData) {
            if (in_array($questionId, $this->course->resources[RESOURCE_QUIZ][$quizId]->obj->question_ids)) {
                $categoryId = $questionData->question_category ?? 0;
                $categoryId = $categoryId > 0 ? $categoryId : $this->getDefaultCategoryId();
                $questions[] = [
                    'id' => $questionData->source_id,
                    'questiontext' => $questionData->question,
                    'qtype' => $this->mapQuestionType($questionData->quiz_type),
                    'questioncategoryid' =>  $categoryId,
                    'answers' => $this->getAnswersForQuestion($questionData->source_id),
                    'maxmark' => $questionData->ponderation ?? 1,
                ];
            }
        }

        return $questions;
    }

    /**
     * Maps the quiz type code to a descriptive string.
     */
    private function mapQuestionType(string $quizType): string
    {
        switch ($quizType) {
            case UNIQUE_ANSWER: return 'multichoice';
            case MULTIPLE_ANSWER: return 'multichoice_nosingle';
            case FILL_IN_BLANKS: return 'match';
            case FREE_ANSWER: return 'shortanswer';
            case CALCULATED_ANSWER: return 'calculated';
            case UPLOAD_ANSWER: return 'fileupload';
            default: return 'unknown';
        }
    }

    /**
     * Retrieves the answers for a specific question ID.
     */
    private function getAnswersForQuestion(int $questionId): array
    {
        static $globalCounter = 0;
        $answers = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionData) {
            if ($questionData->source_id == $questionId) {
                foreach ($questionData->answers as $answer) {
                    $globalCounter++;
                    $answers[] = [
                        'id' => $questionId * 1000 + $globalCounter,
                        'text' => $answer['answer'],
                        'fraction' => $answer['correct'] == '1' ? 100 : 0,
                        'feedback' => $answer['comment'],
                    ];
                }
            }
        }

        return $answers;
    }

    /**
     * Retrieves feedbacks for a specific quiz.
     */
    private function getFeedbacksForQuiz(int $quizId): array
    {
        $feedbacks = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZ] ?? [];

        foreach ($quizResources as $quiz) {
            if ($quiz->obj->iid == $quizId) {
                $feedbacks[] = [
                    'feedbacktext' => $quiz->obj->description ?? '',
                    'mingrade' => 0.00000,
                    'maxgrade' => $quiz->obj->grade ?? 10.00000,
                ];
            }
        }

        return $feedbacks;
    }

    private function getDefaultCategoryId(): int
    {
        return 1;
    }

    /**
     * Creates the quiz.xml file.
     */
    private function createQuizXml(array $quizData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$quizData['id'].'" moduleid="'.$quizData['moduleid'].'" modulename="quiz" contextid="'.$quizData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <quiz id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($quizData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars($quizData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <timeopen>'.($quizData['timeopen'] ?? 0).'</timeopen>'.PHP_EOL;
        $xmlContent .= '    <timeclose>'.($quizData['timeclose'] ?? 0).'</timeclose>'.PHP_EOL;
        $xmlContent .= '    <timelimit>'.($quizData['timelimit'] ?? 0).'</timelimit>'.PHP_EOL;
        $xmlContent .= '    <overduehandling>'.($quizData['overduehandling'] ?? 'autosubmit').'</overduehandling>'.PHP_EOL;
        $xmlContent .= '    <graceperiod>'.($quizData['graceperiod'] ?? 0).'</graceperiod>'.PHP_EOL;
        $xmlContent .= '    <preferredbehaviour>'.htmlspecialchars($quizData['preferredbehaviour']).'</preferredbehaviour>'.PHP_EOL;
        $xmlContent .= '    <canredoquestions>'.($quizData['canredoquestions'] ?? 0).'</canredoquestions>'.PHP_EOL;
        $xmlContent .= '    <attempts_number>'.($quizData['attempts_number'] ?? 0).'</attempts_number>'.PHP_EOL;
        $xmlContent .= '    <attemptonlast>'.($quizData['attemptonlast'] ?? 0).'</attemptonlast>'.PHP_EOL;
        $xmlContent .= '    <grademethod>'.$quizData['grademethod'].'</grademethod>'.PHP_EOL;
        $xmlContent .= '    <decimalpoints>'.$quizData['decimalpoints'].'</decimalpoints>'.PHP_EOL;
        $xmlContent .= '    <questiondecimalpoints>'.($quizData['questiondecimalpoints'] ?? -1).'</questiondecimalpoints>'.PHP_EOL;

        // Review options
        $xmlContent .= '    <reviewattempt>'.($quizData['reviewattempt'] ?? 69888).'</reviewattempt>'.PHP_EOL;
        $xmlContent .= '    <reviewcorrectness>'.($quizData['reviewcorrectness'] ?? 4352).'</reviewcorrectness>'.PHP_EOL;
        $xmlContent .= '    <reviewmarks>'.($quizData['reviewmarks'] ?? 4352).'</reviewmarks>'.PHP_EOL;
        $xmlContent .= '    <reviewspecificfeedback>'.($quizData['reviewspecificfeedback'] ?? 4352).'</reviewspecificfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewgeneralfeedback>'.($quizData['reviewgeneralfeedback'] ?? 4352).'</reviewgeneralfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewrightanswer>'.($quizData['reviewrightanswer'] ?? 4352).'</reviewrightanswer>'.PHP_EOL;
        $xmlContent .= '    <reviewoverallfeedback>'.($quizData['reviewoverallfeedback'] ?? 4352).'</reviewoverallfeedback>'.PHP_EOL;

        // Navigation and presentation settings
        $xmlContent .= '    <questionsperpage>'.$quizData['questionsperpage'].'</questionsperpage>'.PHP_EOL;
        $xmlContent .= '    <navmethod>'.htmlspecialchars($quizData['navmethod']).'</navmethod>'.PHP_EOL;
        $xmlContent .= '    <shuffleanswers>'.$quizData['shuffleanswers'].'</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '    <sumgrades>'.$quizData['sumgrades'].'</sumgrades>'.PHP_EOL;
        $xmlContent .= '    <grade>'.$quizData['grade'].'</grade>'.PHP_EOL;

        // Timing and security
        $xmlContent .= '    <timecreated>'.($quizData['timecreated'] ?? time()).'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.($quizData['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <password>'.(isset($quizData['password']) ? htmlspecialchars($quizData['password']) : '').'</password>'.PHP_EOL;
        $xmlContent .= '    <subnet>'.(isset($quizData['subnet']) ? htmlspecialchars($quizData['subnet']) : '').'</subnet>'.PHP_EOL;
        $xmlContent .= '    <browsersecurity>'.(isset($quizData['browsersecurity']) ? htmlspecialchars($quizData['browsersecurity']) : '-').'</browsersecurity>'.PHP_EOL;
        $xmlContent .= '    <delay1>'.($quizData['delay1'] ?? 0).'</delay1>'.PHP_EOL;
        $xmlContent .= '    <delay2>'.($quizData['delay2'] ?? 0).'</delay2>'.PHP_EOL;

        // Additional options
        $xmlContent .= '    <showuserpicture>'.($quizData['showuserpicture'] ?? 0).'</showuserpicture>'.PHP_EOL;
        $xmlContent .= '    <showblocks>'.($quizData['showblocks'] ?? 0).'</showblocks>'.PHP_EOL;
        $xmlContent .= '    <completionattemptsexhausted>'.($quizData['completionattemptsexhausted'] ?? 0).'</completionattemptsexhausted>'.PHP_EOL;
        $xmlContent .= '    <completionpass>'.($quizData['completionpass'] ?? 0).'</completionpass>'.PHP_EOL;
        $xmlContent .= '    <completionminattempts>'.($quizData['completionminattempts'] ?? 0).'</completionminattempts>'.PHP_EOL;
        $xmlContent .= '    <allowofflineattempts>'.($quizData['allowofflineattempts'] ?? 0).'</allowofflineattempts>'.PHP_EOL;

        // Subplugin, if applicable
        $xmlContent .= '    <subplugin_quizaccess_seb_quiz>'.PHP_EOL;
        $xmlContent .= '    </subplugin_quizaccess_seb_quiz>'.PHP_EOL;

        // Add question instances
        $xmlContent .= '    <question_instances>'.PHP_EOL;
        $slotIndex = 1;
        foreach ($quizData['questions'] as $question) {
            $xmlContent .= '      <question_instance id="'.$question['id'].'">'.PHP_EOL;
            $xmlContent .= '        <slot>'.$slotIndex.'</slot>'.PHP_EOL;
            $xmlContent .= '        <page>1</page>'.PHP_EOL;
            $xmlContent .= '        <requireprevious>0</requireprevious>'.PHP_EOL;
            $xmlContent .= '        <questionid>'.$question['id'].'</questionid>'.PHP_EOL;
            $xmlContent .= '        <questioncategoryid>'.$question['questioncategoryid'].'</questioncategoryid>'.PHP_EOL;
            $xmlContent .= '        <includingsubcategories>$@NULL@$</includingsubcategories>'.PHP_EOL;
            $xmlContent .= '        <maxmark>'.$question['maxmark'].'</maxmark>'.PHP_EOL;
            $xmlContent .= '      </question_instance>'.PHP_EOL;
            $slotIndex++;
        }
        $xmlContent .= '    </question_instances>'.PHP_EOL;

        // Quiz sections
        $xmlContent .= '    <sections>'.PHP_EOL;
        $xmlContent .= '      <section id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '        <firstslot>1</firstslot>'.PHP_EOL;
        $xmlContent .= '        <shufflequestions>0</shufflequestions>'.PHP_EOL;
        $xmlContent .= '      </section>'.PHP_EOL;
        $xmlContent .= '    </sections>'.PHP_EOL;

        // Add feedbacks
        $xmlContent .= '    <feedbacks>'.PHP_EOL;
        foreach ($quizData['feedbacks'] as $feedback) {
            $xmlContent .= '      <feedback id="'.$quizData['id'].'">'.PHP_EOL;
            $xmlContent .= '        <feedbacktext>'.htmlspecialchars($feedback['feedbacktext']).'</feedbacktext>'.PHP_EOL;
            $xmlContent .= '        <feedbacktextformat>1</feedbacktextformat>'.PHP_EOL;
            $xmlContent .= '        <mingrade>'.$feedback['mingrade'].'</mingrade>'.PHP_EOL;
            $xmlContent .= '        <maxgrade>'.$feedback['maxgrade'].'</maxgrade>'.PHP_EOL;
            $xmlContent .= '      </feedback>'.PHP_EOL;
        }
        $xmlContent .= '    </feedbacks>'.PHP_EOL;

        // Complete with placeholders for attempts and grades
        $xmlContent .= '    <overrides>'.PHP_EOL.'    </overrides>'.PHP_EOL;
        $xmlContent .= '    <grades>'.PHP_EOL.'    </grades>'.PHP_EOL;
        $xmlContent .= '    <attempts>'.PHP_EOL.'    </attempts>'.PHP_EOL;

        // Close the activity tag
        $xmlContent .= '  </quiz>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

        // Save the XML file
        $xmlFile = $destinationDir.'/quiz.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingQuizXml'));
        }
    }

    /**
     * Exports a multiple-choice question in XML format.
     */
    private function exportMultichoiceQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_multichoice_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;
        foreach ($question['answers'] as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;
        $xmlContent .= '          <multichoice id="'.($question['id'] ?? '0').'">'.PHP_EOL;
        $xmlContent .= '            <layout>0</layout>'.PHP_EOL;
        $xmlContent .= '            <single>1</single>'.PHP_EOL;
        $xmlContent .= '            <shuffleanswers>1</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '            <correctfeedback>Your answer is correct.</correctfeedback>'.PHP_EOL;
        $xmlContent .= '            <correctfeedbackformat>1</correctfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedback>Your answer is partially correct.</partiallycorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedbackformat>1</partiallycorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedback>Your answer is incorrect.</incorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedbackformat>1</incorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <answernumbering>abc</answernumbering>'.PHP_EOL;
        $xmlContent .= '            <shownumcorrect>1</shownumcorrect>'.PHP_EOL;
        $xmlContent .= '          </multichoice>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_multichoice_question>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Exports a multiple-choice question with single=0 in XML format.
     */
    private function exportMultichoiceNosingleQuestion(array $question): string
    {
        // Similar structure to exportMultichoiceQuestion, but with single=0
        $xmlContent = str_replace('<single>1</single>', '<single>0</single>', $this->exportMultichoiceQuestion($question));

        return $xmlContent;
    }

    /**
     * Exports a true/false question in XML format.
     */
    private function exportTrueFalseQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_truefalse_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;
        foreach ($question['answers'] as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;
        $xmlContent .= '          <truefalse id="'.($question['id'] ?? '0').'">'.PHP_EOL;
        $trueId = $question['answers'][0]['id'] ?? 0;
        $falseId = $question['answers'][1]['id'] ?? 0;
        $xmlContent .= '            <trueanswer>'.$trueId.'</trueanswer>'.PHP_EOL;
        $xmlContent .= '            <falseanswer>'.$falseId.'</falseanswer>'.PHP_EOL;
        $xmlContent .= '          </truefalse>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_truefalse_question>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Exports a short answer question in XML format.
     */
    private function exportShortAnswerQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_shortanswer_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;
        foreach ($question['answers'] as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;
        $xmlContent .= '          <shortanswer id="'.($question['id'] ?? '0').'">'.PHP_EOL;
        $xmlContent .= '            <usecase>0</usecase>'.PHP_EOL;
        $xmlContent .= '          </shortanswer>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_shortanswer_question>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Exports a matching question in XML format.
     */
    private function exportMatchQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_match_question>'.PHP_EOL;
        $xmlContent .= '          <matchoptions id="'.htmlspecialchars($question['id'] ?? '0').'">'.PHP_EOL;
        $xmlContent .= '            <shuffleanswers>1</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '            <correctfeedback>'.htmlspecialchars($question['correctfeedback'] ?? '').'</correctfeedback>'.PHP_EOL;
        $xmlContent .= '            <correctfeedbackformat>0</correctfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedback>'.htmlspecialchars($question['partiallycorrectfeedback'] ?? '').'</partiallycorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedbackformat>0</partiallycorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedback>'.htmlspecialchars($question['incorrectfeedback'] ?? '').'</incorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedbackformat>0</incorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <shownumcorrect>0</shownumcorrect>'.PHP_EOL;
        $xmlContent .= '          </matchoptions>'.PHP_EOL;
        $xmlContent .= '          <matches>'.PHP_EOL;

        $res = FillBlanks::getAnswerInfo($question['answers'][0]['text']);
        $words = $res['words'];
        $common_words = $res['common_words'];

        for ($i = 0; $i < count($common_words); $i++) {
            $answer = htmlspecialchars(trim(strip_tags($common_words[$i])));
            if (!empty(trim($answer))) {
                $xmlContent .= '            <match id="'.($i + 1).'">'.PHP_EOL;
                $xmlContent .= '              <questiontext>'.$answer.'</questiontext>'.PHP_EOL;
                $xmlContent .= '              <questiontextformat>0</questiontextformat>'.PHP_EOL;
                $xmlContent .= '              <answertext>'.htmlspecialchars(explode('|', $words[$i])[0]).'</answertext>'.PHP_EOL;
                $xmlContent .= '            </match>'.PHP_EOL;
            }
        }

        $xmlContent .= '          </matches>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_match_question>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Exports an answer in XML format.
     */
    private function exportAnswer(array $answer): string
    {
        return '            <answer id="'.($answer['id'] ?? '0').'">'.PHP_EOL.
            '              <answertext>'.htmlspecialchars($answer['text'] ?? 'No answer text').'</answertext>'.PHP_EOL.
            '              <answerformat>1</answerformat>'.PHP_EOL.
            '              <fraction>'.($answer['fraction'] ?? '0').'</fraction>'.PHP_EOL.
            '              <feedback>'.htmlspecialchars($answer['feedback'] ?? '').'</feedback>'.PHP_EOL.
            '              <feedbackformat>1</feedbackformat>'.PHP_EOL.
            '            </answer>'.PHP_EOL;
    }
}
