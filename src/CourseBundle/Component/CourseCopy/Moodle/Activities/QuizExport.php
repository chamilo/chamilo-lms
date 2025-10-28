<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Exception;
use FillBlanks;

use const PHP_EOL;

/**
 * Handles the export of quizzes within a course.
 */
class QuizExport extends ActivityExport
{
    /**
     * Export a quiz to the specified directory.
     *
     * @param int    $activityId the ID of the quiz
     * @param string $exportDir  the directory where the quiz will be exported
     * @param int    $moduleId   the ID of the module
     * @param int    $sectionId  the ID of the section
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $quizDir = $this->prepareActivityDirectory($exportDir, 'quiz', (int) $moduleId);

        $quizData = $this->getData((int) $activityId, (int) $sectionId);

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
     *
     * @return array<string,mixed>
     */
    public function getData(int $quizId, int $sectionId): array
    {
        $quizResources = $this->course->resources[RESOURCE_QUIZ] ?? [];

        foreach ($quizResources as $quiz) {
            if (-1 == $quiz->obj->iid) {
                continue;
            }

            if ((int) $quiz->obj->iid === $quizId) {
                $contextid = (int) ($quiz->obj->c_id ?? $this->course->info['real_id'] ?? 0);

                return [
                    'id' => (int) $quiz->obj->iid,
                    'name' => (string) $quiz->obj->title,
                    'intro' => (string) ($quiz->obj->description ?? ''),
                    'timeopen' => (int) ($quiz->obj->start_time ?? 0),
                    'timeclose' => (int) ($quiz->obj->end_time ?? 0),
                    'timelimit' => (int) ($quiz->obj->timelimit ?? 0),
                    'grademethod' => (int) ($quiz->obj->grademethod ?? 1),
                    'decimalpoints' => (int) ($quiz->obj->decimalpoints ?? 2),
                    'sumgrades' => (float) ($quiz->obj->sumgrades ?? 0),
                    'grade' => (float) ($quiz->obj->grade ?? 0),
                    'questionsperpage' => (int) ($quiz->obj->questionsperpage ?? 1),
                    'preferredbehaviour' => (string) ($quiz->obj->preferredbehaviour ?? 'deferredfeedback'),
                    'navmethod' => (string) ($quiz->obj->navmethod ?? 'free'),
                    'shuffleanswers' => (int) ($quiz->obj->shuffleanswers ?? 1),
                    'questions' => $this->getQuestionsForQuiz($quizId),
                    'feedbacks' => $this->getFeedbacksForQuiz($quizId),
                    'sectionid' => $sectionId,
                    'moduleid' => (int) ($quiz->obj->iid ?? 0),
                    'modulename' => 'quiz',
                    'contextid' => $contextid,
                    'overduehandling' => (string) ($quiz->obj->overduehandling ?? 'autosubmit'),
                    'graceperiod' => (int) ($quiz->obj->graceperiod ?? 0),
                    'canredoquestions' => (int) ($quiz->obj->canredoquestions ?? 0),
                    'attempts_number' => (int) ($quiz->obj->attempts_number ?? 0),
                    'attemptonlast' => (int) ($quiz->obj->attemptonlast ?? 0),
                    'questiondecimalpoints' => (int) ($quiz->obj->questiondecimalpoints ?? 2),
                    'reviewattempt' => (int) ($quiz->obj->reviewattempt ?? 69888),
                    'reviewcorrectness' => (int) ($quiz->obj->reviewcorrectness ?? 4352),
                    'reviewmarks' => (int) ($quiz->obj->reviewmarks ?? 4352),
                    'reviewspecificfeedback' => (int) ($quiz->obj->reviewspecificfeedback ?? 4352),
                    'reviewgeneralfeedback' => (int) ($quiz->obj->reviewgeneralfeedback ?? 4352),
                    'reviewrightanswer' => (int) ($quiz->obj->reviewrightanswer ?? 4352),
                    'reviewoverallfeedback' => (int) ($quiz->obj->reviewoverallfeedback ?? 4352),
                    'timecreated' => (int) ($quiz->obj->insert_date ?? time()),
                    'timemodified' => (int) ($quiz->obj->lastedit_date ?? time()),
                    'password' => (string) ($quiz->obj->password ?? ''),
                    'subnet' => (string) ($quiz->obj->subnet ?? ''),
                    'browsersecurity' => (string) ($quiz->obj->browsersecurity ?? '-'),
                    'delay1' => (int) ($quiz->obj->delay1 ?? 0),
                    'delay2' => (int) ($quiz->obj->delay2 ?? 0),
                    'showuserpicture' => (int) ($quiz->obj->showuserpicture ?? 0),
                    'showblocks' => (int) ($quiz->obj->showblocks ?? 0),
                    'completionattemptsexhausted' => (int) ($quiz->obj->completionattemptsexhausted ?? 0),
                    'completionpass' => (int) ($quiz->obj->completionpass ?? 0),
                    'completionminattempts' => (int) ($quiz->obj->completionminattempts ?? 0),
                    'allowofflineattempts' => (int) ($quiz->obj->allowofflineattempts ?? 0),
                    'users' => [],
                    'files' => [],
                ];
            }
        }

        return [];
    }

    /**
     * Export one question (bank) entry in XML format.
     */
    public function exportQuestion(array $question): string
    {
        $qtype = (string) ($question['qtype'] ?? 'unknown');

        $xmlContent = '      <question id="'.(int) ($question['id'] ?? 0).'">'.PHP_EOL;
        $xmlContent .= '        <parent>0</parent>'.PHP_EOL;
        $xmlContent .= '        <name>'.htmlspecialchars((string) ($question['questiontext'] ?? 'No question text')).'</name>'.PHP_EOL;
        $xmlContent .= '        <questiontext>'.htmlspecialchars((string) ($question['questiontext'] ?? 'No question text')).'</questiontext>'.PHP_EOL;
        $xmlContent .= '        <questiontextformat>1</questiontextformat>'.PHP_EOL;
        $xmlContent .= '        <generalfeedback></generalfeedback>'.PHP_EOL;
        $xmlContent .= '        <generalfeedbackformat>1</generalfeedbackformat>'.PHP_EOL;
        $xmlContent .= '        <defaultmark>'.(float) ($question['maxmark'] ?? 0).'</defaultmark>'.PHP_EOL;
        $xmlContent .= '        <penalty>0.3333333</penalty>'.PHP_EOL;
        $xmlContent .= '        <qtype>'.htmlspecialchars(str_replace('_nosingle', '', $qtype) ?: 'unknown').'</qtype>'.PHP_EOL;
        $xmlContent .= '        <length>1</length>'.PHP_EOL;
        $xmlContent .= '        <stamp>moodle+'.time().'+QUESTIONSTAMP</stamp>'.PHP_EOL;
        $xmlContent .= '        <version>moodle+'.time().'+VERSIONSTAMP</version>'.PHP_EOL;
        $xmlContent .= '        <hidden>0</hidden>'.PHP_EOL;
        $xmlContent .= '        <timecreated>'.time().'</timecreated>'.PHP_EOL;
        $xmlContent .= '        <timemodified>'.time().'</timemodified>'.PHP_EOL;
        $xmlContent .= '        <createdby>2</createdby>'.PHP_EOL;
        $xmlContent .= '        <modifiedby>2</modifiedby>'.PHP_EOL;

        switch ($qtype) {
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
     * @return array<int,array<string,mixed>>
     */
    private function getQuestionsForQuiz(int $quizId): array
    {
        $questions = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionId => $questionData) {
            if (\in_array($questionId, $this->course->resources[RESOURCE_QUIZ][$quizId]->obj->question_ids ?? [], true)) {
                $categoryId = (int) ($questionData->question_category ?? 0);
                $categoryId = $categoryId > 0 ? $categoryId : $this->getDefaultCategoryId();

                $questions[] = [
                    'id' => (int) $questionData->source_id,
                    'questiontext' => (string) $questionData->question,
                    'qtype' => $this->mapQuestionType((string) $questionData->quiz_type),
                    'questioncategoryid' => $categoryId,
                    'answers' => $this->getAnswersForQuestion((int) $questionData->source_id),
                    'maxmark' => (float) ($questionData->ponderation ?? 1),
                ];
            }
        }

        return $questions;
    }

    private function mapQuestionType(string $quizType): string
    {
        switch ($quizType) {
            case UNIQUE_ANSWER:     return 'multichoice';

            case MULTIPLE_ANSWER:   return 'multichoice_nosingle';

            case FILL_IN_BLANKS:    return 'match';

            case FREE_ANSWER:       return 'shortanswer';

            case CALCULATED_ANSWER: return 'calculated';

            case UPLOAD_ANSWER:     return 'fileupload';

            default:                return 'unknown';
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getAnswersForQuestion(int $questionId): array
    {
        static $globalCounter = 0;
        $answers = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionData) {
            if ((int) $questionData->source_id === $questionId) {
                foreach ($questionData->answers as $answer) {
                    $globalCounter++;
                    $answers[] = [
                        'id' => $questionId * 1000 + $globalCounter,
                        'text' => (string) $answer['answer'],
                        'fraction' => (int) ('1' == $answer['correct'] ? 100 : 0),
                        'feedback' => (string) ($answer['comment'] ?? ''),
                    ];
                }
            }
        }

        return $answers;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getFeedbacksForQuiz(int $quizId): array
    {
        $feedbacks = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZ] ?? [];

        foreach ($quizResources as $quiz) {
            if ((int) $quiz->obj->iid === $quizId) {
                $feedbacks[] = [
                    'feedbacktext' => (string) ($quiz->obj->description ?? ''),
                    'mingrade' => 0.00000,
                    'maxgrade' => (float) ($quiz->obj->grade ?? 10.00000),
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
     *
     * @param array<string,mixed> $quizData
     */
    private function createQuizXml(array $quizData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$quizData['id'].'" moduleid="'.$quizData['moduleid'].'" modulename="quiz" contextid="'.$quizData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <quiz id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $quizData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) $quizData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <timeopen>'.(int) ($quizData['timeopen'] ?? 0).'</timeopen>'.PHP_EOL;
        $xmlContent .= '    <timeclose>'.(int) ($quizData['timeclose'] ?? 0).'</timeclose>'.PHP_EOL;
        $xmlContent .= '    <timelimit>'.(int) ($quizData['timelimit'] ?? 0).'</timelimit>'.PHP_EOL;
        $xmlContent .= '    <overduehandling>'.htmlspecialchars((string) ($quizData['overduehandling'] ?? 'autosubmit')).'</overduehandling>'.PHP_EOL;
        $xmlContent .= '    <graceperiod>'.(int) ($quizData['graceperiod'] ?? 0).'</graceperiod>'.PHP_EOL;
        $xmlContent .= '    <preferredbehaviour>'.htmlspecialchars((string) $quizData['preferredbehaviour']).'</preferredbehaviour>'.PHP_EOL;
        $xmlContent .= '    <canredoquestions>'.(int) ($quizData['canredoquestions'] ?? 0).'</canredoquestions>'.PHP_EOL;
        $xmlContent .= '    <attempts_number>'.(int) ($quizData['attempts_number'] ?? 0).'</attempts_number>'.PHP_EOL;
        $xmlContent .= '    <attemptonlast>'.(int) ($quizData['attemptonlast'] ?? 0).'</attemptonlast>'.PHP_EOL;
        $xmlContent .= '    <grademethod>'.(int) $quizData['grademethod'].'</grademethod>'.PHP_EOL;
        $xmlContent .= '    <decimalpoints>'.(int) $quizData['decimalpoints'].'</decimalpoints>'.PHP_EOL;
        $xmlContent .= '    <questiondecimalpoints>'.(int) ($quizData['questiondecimalpoints'] ?? -1).'</questiondecimalpoints>'.PHP_EOL;

        // Review options
        $xmlContent .= '    <reviewattempt>'.(int) ($quizData['reviewattempt'] ?? 69888).'</reviewattempt>'.PHP_EOL;
        $xmlContent .= '    <reviewcorrectness>'.(int) ($quizData['reviewcorrectness'] ?? 4352).'</reviewcorrectness>'.PHP_EOL;
        $xmlContent .= '    <reviewmarks>'.(int) ($quizData['reviewmarks'] ?? 4352).'</reviewmarks>'.PHP_EOL;
        $xmlContent .= '    <reviewspecificfeedback>'.(int) ($quizData['reviewspecificfeedback'] ?? 4352).'</reviewspecificfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewgeneralfeedback>'.(int) ($quizData['reviewgeneralfeedback'] ?? 4352).'</reviewgeneralfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewrightanswer>'.(int) ($quizData['reviewrightanswer'] ?? 4352).'</reviewrightanswer>'.PHP_EOL;
        $xmlContent .= '    <reviewoverallfeedback>'.(int) ($quizData['reviewoverallfeedback'] ?? 4352).'</reviewoverallfeedback>'.PHP_EOL;

        // Navigation and presentation
        $xmlContent .= '    <questionsperpage>'.(int) $quizData['questionsperpage'].'</questionsperpage>'.PHP_EOL;
        $xmlContent .= '    <navmethod>'.htmlspecialchars((string) $quizData['navmethod']).'</navmethod>'.PHP_EOL;
        $xmlContent .= '    <shuffleanswers>'.(int) $quizData['shuffleanswers'].'</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '    <sumgrades>'.(float) $quizData['sumgrades'].'</sumgrades>'.PHP_EOL;
        $xmlContent .= '    <grade>'.(float) $quizData['grade'].'</grade>'.PHP_EOL;

        // Timing and security
        $xmlContent .= '    <timecreated>'.(int) ($quizData['timecreated'] ?? time()).'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.(int) ($quizData['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <password>'.htmlspecialchars((string) ($quizData['password'] ?? '')).'</password>'.PHP_EOL;
        $xmlContent .= '    <subnet>'.htmlspecialchars((string) ($quizData['subnet'] ?? '')).'</subnet>'.PHP_EOL;
        $xmlContent .= '    <browsersecurity>'.htmlspecialchars((string) ($quizData['browsersecurity'] ?? '-')).'</browsersecurity>'.PHP_EOL;
        $xmlContent .= '    <delay1>'.(int) ($quizData['delay1'] ?? 0).'</delay1>'.PHP_EOL;
        $xmlContent .= '    <delay2>'.(int) ($quizData['delay2'] ?? 0).'</delay2>'.PHP_EOL;

        // Additional options
        $xmlContent .= '    <showuserpicture>'.(int) ($quizData['showuserpicture'] ?? 0).'</showuserpicture>'.PHP_EOL;
        $xmlContent .= '    <showblocks>'.(int) ($quizData['showblocks'] ?? 0).'</showblocks>'.PHP_EOL;
        $xmlContent .= '    <completionattemptsexhausted>'.(int) ($quizData['completionattemptsexhausted'] ?? 0).'</completionattemptsexhausted>'.PHP_EOL;
        $xmlContent .= '    <completionpass>'.(int) ($quizData['completionpass'] ?? 0).'</completionpass>'.PHP_EOL;
        $xmlContent .= '    <completionminattempts>'.(int) ($quizData['completionminattempts'] ?? 0).'</completionminattempts>'.PHP_EOL;
        $xmlContent .= '    <allowofflineattempts>'.(int) ($quizData['allowofflineattempts'] ?? 0).'</allowofflineattempts>'.PHP_EOL;

        // Subplugin placeholder
        $xmlContent .= '    <subplugin_quizaccess_seb_quiz>'.PHP_EOL;
        $xmlContent .= '    </subplugin_quizaccess_seb_quiz>'.PHP_EOL;

        // Question instances
        $xmlContent .= '    <question_instances>'.PHP_EOL;
        $slotIndex = 1;
        foreach (($quizData['questions'] ?? []) as $question) {
            $xmlContent .= '      <question_instance id="'.(int) ($question['id'] ?? 0).'">'.PHP_EOL;
            $xmlContent .= '        <slot>'.$slotIndex.'</slot>'.PHP_EOL;
            $xmlContent .= '        <page>1</page>'.PHP_EOL;
            $xmlContent .= '        <requireprevious>0</requireprevious>'.PHP_EOL;
            $xmlContent .= '        <questionid>'.(int) ($question['id'] ?? 0).'</questionid>'.PHP_EOL;
            $xmlContent .= '        <questioncategoryid>'.(int) ($question['questioncategoryid'] ?? 0).'</questioncategoryid>'.PHP_EOL;
            $xmlContent .= '        <includingsubcategories>$@NULL@$</includingsubcategories>'.PHP_EOL;
            $xmlContent .= '        <maxmark>'.(float) ($question['maxmark'] ?? 0).'</maxmark>'.PHP_EOL;
            $xmlContent .= '      </question_instance>'.PHP_EOL;
            $slotIndex++;
        }
        $xmlContent .= '    </question_instances>'.PHP_EOL;

        // Sections
        $xmlContent .= '    <sections>'.PHP_EOL;
        $xmlContent .= '      <section id="'.(int) ($quizData['id'] ?? 0).'">'.PHP_EOL;
        $xmlContent .= '        <firstslot>1</firstslot>'.PHP_EOL;
        $xmlContent .= '        <shufflequestions>0</shufflequestions>'.PHP_EOL;
        $xmlContent .= '      </section>'.PHP_EOL;
        $xmlContent .= '    </sections>'.PHP_EOL;

        // Feedbacks
        $xmlContent .= '    <feedbacks>'.PHP_EOL;
        foreach (($quizData['feedbacks'] ?? []) as $feedback) {
            $xmlContent .= '      <feedback id="'.(int) ($quizData['id'] ?? 0).'">'.PHP_EOL;
            $xmlContent .= '        <feedbacktext>'.htmlspecialchars((string) ($feedback['feedbacktext'] ?? '')).'</feedbacktext>'.PHP_EOL;
            $xmlContent .= '        <feedbacktextformat>1</feedbacktextformat>'.PHP_EOL;
            $xmlContent .= '        <mingrade>'.(float) ($feedback['mingrade'] ?? 0).'</mingrade>'.PHP_EOL;
            $xmlContent .= '        <maxgrade>'.(float) ($feedback['maxgrade'] ?? 0).'</maxgrade>'.PHP_EOL;
            $xmlContent .= '      </feedback>'.PHP_EOL;
        }
        $xmlContent .= '    </feedbacks>'.PHP_EOL;

        // Placeholders
        $xmlContent .= '    <overrides></overrides>'.PHP_EOL;
        $xmlContent .= '    <grades></grades>'.PHP_EOL;
        $xmlContent .= '    <attempts></attempts>'.PHP_EOL;

        $xmlContent .= '  </quiz>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

        $xmlFile = $destinationDir.'/quiz.xml';
        if (false === file_put_contents($xmlFile, $xmlContent)) {
            throw new Exception(get_lang('ErrorCreatingQuizXml'));
        }
    }

    private function exportMultichoiceQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_multichoice_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;
        foreach (($question['answers'] ?? []) as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;
        $xmlContent .= '          <multichoice id="'.(int) ($question['id'] ?? 0).'">'.PHP_EOL;
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

    private function exportMultichoiceNosingleQuestion(array $question): string
    {
        return str_replace('<single>1</single>', '<single>0</single>', $this->exportMultichoiceQuestion($question));
    }

    private function exportTrueFalseQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_truefalse_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;

        // Normalize array to avoid non-sequential indexes
        $answers = array_values($question['answers'] ?? []);

        foreach ($answers as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;

        // Robust mapping: determine true/false ids without assuming positions
        $trueId = 0;
        $falseId = 0;

        foreach ($answers as $ans) {
            $id = (int) ($ans['id'] ?? 0);
            $fraction = (int) ($ans['fraction'] ?? 0); // 100 means correct (true), 0 means false
            if ($fraction > 0 && 0 === $trueId) {
                $trueId = $id;
            } elseif ($fraction <= 0 && 0 === $falseId) {
                $falseId = $id;
            }
        }

        // Fallbacks to avoid undefined references in odd data cases
        if (0 === $trueId && isset($answers[0]['id'])) {
            $trueId = (int) $answers[0]['id'];
        }
        if (0 === $falseId && isset($answers[1]['id'])) {
            $falseId = (int) $answers[1]['id'];
        }
        // As last resort, mirror one id so XML stays valid
        if (0 === $trueId && $falseId > 0) {
            $trueId = $falseId;
        }
        if (0 === $falseId && $trueId > 0) {
            $falseId = $trueId;
        }

        $xmlContent .= '          <truefalse id="'.(int) ($question['id'] ?? 0).'">'.PHP_EOL;
        $xmlContent .= '            <trueanswer>'.$trueId.'</trueanswer>'.PHP_EOL;
        $xmlContent .= '            <falseanswer>'.$falseId.'</falseanswer>'.PHP_EOL;
        $xmlContent .= '          </truefalse>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_truefalse_question>'.PHP_EOL;

        return $xmlContent;
    }

    private function exportShortAnswerQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_shortanswer_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;
        foreach (($question['answers'] ?? []) as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }
        $xmlContent .= '          </answers>'.PHP_EOL;
        $xmlContent .= '          <shortanswer id="'.(int) ($question['id'] ?? 0).'">'.PHP_EOL;
        $xmlContent .= '            <usecase>0</usecase>'.PHP_EOL;
        $xmlContent .= '          </shortanswer>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_shortanswer_question>'.PHP_EOL;

        return $xmlContent;
    }

    private function exportMatchQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_match_question>'.PHP_EOL;
        $xmlContent .= '          <matchoptions id="'.htmlspecialchars((string) ($question['id'] ?? '0')).'">'.PHP_EOL;
        $xmlContent .= '            <shuffleanswers>1</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '            <correctfeedback>'.htmlspecialchars((string) ($question['correctfeedback'] ?? '')).'</correctfeedback>'.PHP_EOL;
        $xmlContent .= '            <correctfeedbackformat>0</correctfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedback>'.htmlspecialchars((string) ($question['partiallycorrectfeedback'] ?? '')).'</partiallycorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <partiallycorrectfeedbackformat>0</partiallycorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedback>'.htmlspecialchars((string) ($question['incorrectfeedback'] ?? '')).'</incorrectfeedback>'.PHP_EOL;
        $xmlContent .= '            <incorrectfeedbackformat>0</incorrectfeedbackformat>'.PHP_EOL;
        $xmlContent .= '            <shownumcorrect>0</shownumcorrect>'.PHP_EOL;
        $xmlContent .= '          </matchoptions>'.PHP_EOL;
        $xmlContent .= '          <matches>'.PHP_EOL;

        // Be defensive: not all datasets will have answers[0]['text'] populated
        $answers = array_values($question['answers'] ?? []);
        $firstText = (string) ($answers[0]['text'] ?? '');

        $res = FillBlanks::getAnswerInfo($firstText);
        $words = (array) ($res['words'] ?? []);
        $common = (array) ($res['common_words'] ?? []);

        // Use the shortest length to avoid "Undefined array key" warnings
        $limit = min(\count($common), \count($words));

        for ($i = 0; $i < $limit; $i++) {
            $leftRaw = (string) ($common[$i] ?? '');
            $left = htmlspecialchars(trim(strip_tags($leftRaw)));
            if ('' === $left) {
                continue;
            }

            $pair = explode('|', (string) ($words[$i] ?? ''));
            $right = htmlspecialchars((string) ($pair[0] ?? ''));

            $xmlContent .= '            <match id="'.($i + 1).'">'.PHP_EOL;
            $xmlContent .= '              <questiontext>'.$left.'</questiontext>'.PHP_EOL;
            $xmlContent .= '              <questiontextformat>0</questiontextformat>'.PHP_EOL;
            $xmlContent .= '              <answertext>'.$right.'</answertext>'.PHP_EOL;
            $xmlContent .= '            </match>'.PHP_EOL;
        }

        $xmlContent .= '          </matches>'.PHP_EOL;
        $xmlContent .= '        </plugin_qtype_match_question>'.PHP_EOL;

        return $xmlContent;
    }

    private function exportAnswer(array $answer): string
    {
        return '            <answer id="'.(int) ($answer['id'] ?? 0).'">'.PHP_EOL
            .'              <answertext>'.htmlspecialchars((string) ($answer['text'] ?? 'No answer text')).'</answertext>'.PHP_EOL
            .'              <answerformat>1</answerformat>'.PHP_EOL
            .'              <fraction>'.(int) ($answer['fraction'] ?? 0).'</fraction>'.PHP_EOL
            .'              <feedback>'.htmlspecialchars((string) ($answer['feedback'] ?? '')).'</feedback>'.PHP_EOL
            .'              <feedbackformat>1</feedbackformat>'.PHP_EOL
            .'            </answer>'.PHP_EOL;
    }
}
