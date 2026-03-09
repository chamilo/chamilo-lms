<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Entity\CDocument;
use DocumentManager;
use Exception;
use FillBlanks;
use Symfony\Component\Uid\Uuid;

use const PHP_EOL;

/**
 * Handles the export of quizzes within a course.
 *
 * This version keeps the C2 XML structure but restores the C1 logic for:
 * - stable question categories per exported quiz module
 * - stable answer ids
 * - embedded files in question text and answers
 * - synthetic question bank context ids
 */
class QuizExport extends ActivityExport
{
    /**
     * Unique file ids for embedded quiz files.
     */
    private static int $embeddedFileGlobalSeq = 0;

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

        $quizData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);

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
     * Direct inforef using file ids.
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (!empty($references['files']) && \is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;

            foreach ($references['files'] as $file) {
                $fileId = \is_array($file) ? (int) ($file['id'] ?? 0) : (int) $file;
                if ($fileId <= 0) {
                    continue;
                }

                $xmlContent .= '    <file>'.PHP_EOL;
                $xmlContent .= '      <id>'.$fileId.'</id>'.PHP_EOL;
                $xmlContent .= '    </file>'.PHP_EOL;
            }

            $xmlContent .= '  </fileref>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * Retrieves the quiz data.
     *
     * @return array<string,mixed>
     */
    public function getData(int $quizId, int $sectionId, ?int $moduleId = null): array
    {
        $quizResources =
            $this->course->resources[\defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz']
            ?? $this->course->resources['quiz']
            ?? [];

        foreach ($quizResources as $quiz) {
            if (!\is_object($quiz) || !isset($quiz->obj) || !\is_object($quiz->obj)) {
                continue;
            }

            if ((int) ($quiz->obj->iid ?? 0) === -1) {
                continue;
            }

            if ((int) ($quiz->obj->iid ?? 0) !== $quizId) {
                continue;
            }

            $courseCode = (string) ($this->course->info['code'] ?? ($this->course->code ?? ''));
            $courseInfo = api_get_course_info($courseCode);
            $courseRealId = (int) ($courseInfo['real_id'] ?? 0);

            $contextid = $this->buildQuestionBankContextId($courseRealId);

            $effectiveModuleId = $this->resolveEffectiveQuizModuleId(
                (int) ($quiz->obj->iid ?? 0),
                $sectionId,
                $moduleId
            );

            $questionCategoryId = $this->buildQuizQuestionCategoryId($effectiveModuleId);
            $adminData = MoodleExport::getAdminUserData();
            $adminId = (int) ($adminData['id'] ?? 1);

            $quizFiles = [];
            $questions = $this->getQuestionsForQuiz(
                $quizId,
                $questionCategoryId,
                $contextid,
                $courseInfo,
                $adminId,
                $quizFiles
            );

            $computedSumgrades = 0.0;
            foreach ($questions as $q) {
                $computedSumgrades += (float) ($q['maxmark'] ?? 0.0);
            }

            $rawSumgrades = $this->parseNullableFloat($quiz->obj->sumgrades ?? null);
            $rawGrade = $this->parseNullableFloat($quiz->obj->grade ?? null);

            $sumgrades = $rawSumgrades !== null ? $rawSumgrades : $computedSumgrades;
            $grade = $rawGrade !== null ? $rawGrade : ($sumgrades > 0 ? $sumgrades : 0.0);

            return [
                'id'                      => (int) ($quiz->obj->iid ?? 0),
                'name'                    => (string) ($quiz->obj->title ?? ('Quiz '.$quizId)),
                'intro'                   => (string) ($quiz->obj->description ?? ''),
                'timeopen'                => $this->sanitizeInt($quiz->obj->start_time ?? null, 0),
                'timeclose'               => $this->sanitizeInt($quiz->obj->end_time ?? null, 0),
                'timelimit'               => $this->sanitizeInt($quiz->obj->timelimit ?? null, 0),
                'grademethod'             => $this->sanitizeInt($quiz->obj->grademethod ?? null, 1),
                'decimalpoints'           => $this->sanitizeInt($quiz->obj->decimalpoints ?? null, 2),
                'sumgrades'               => $sumgrades,
                'grade'                   => $grade,
                'questionsperpage'        => $this->sanitizeInt($quiz->obj->questionsperpage ?? null, 1),
                'preferredbehaviour'      => $this->sanitizePreferredBehaviour($quiz->obj->preferredbehaviour ?? null),
                'navmethod'               => $this->sanitizeNavMethod($quiz->obj->navmethod ?? null),
                'shuffleanswers'          => $this->sanitizeInt($quiz->obj->shuffleanswers ?? null, 1),
                'question_category_id'    => $questionCategoryId,
                'questions'               => $questions,
                'files'                   => $quizFiles,
                'feedbacks'               => $this->getFeedbacksForQuiz($quizId),
                'sectionid'               => $sectionId,
                'moduleid'                => $effectiveModuleId,
                'modulename'              => 'quiz',
                'contextid'               => $contextid,
                'courseid'                => $courseRealId,
                'overduehandling'         => (string) ($quiz->obj->overduehandling ?? 'autosubmit'),
                'graceperiod'             => $this->sanitizeInt($quiz->obj->graceperiod ?? null, 0),
                'canredoquestions'        => $this->sanitizeInt($quiz->obj->canredoquestions ?? null, 0),
                'attempts_number'         => $this->sanitizeInt($quiz->obj->attempts_number ?? null, 0),
                'attemptonlast'           => $this->sanitizeInt($quiz->obj->attemptonlast ?? null, 0),
                'questiondecimalpoints'   => $this->sanitizeInt($quiz->obj->questiondecimalpoints ?? null, 2),
                'reviewattempt'           => $this->sanitizeInt($quiz->obj->reviewattempt ?? null, 69888),
                'reviewcorrectness'       => $this->sanitizeInt($quiz->obj->reviewcorrectness ?? null, 4352),
                'reviewmarks'             => $this->sanitizeInt($quiz->obj->reviewmarks ?? null, 4352),
                'reviewspecificfeedback'  => $this->sanitizeInt($quiz->obj->reviewspecificfeedback ?? null, 4352),
                'reviewgeneralfeedback'   => $this->sanitizeInt($quiz->obj->reviewgeneralfeedback ?? null, 4352),
                'reviewrightanswer'       => $this->sanitizeInt($quiz->obj->reviewrightanswer ?? null, 4352),
                'reviewoverallfeedback'   => $this->sanitizeInt($quiz->obj->reviewoverallfeedback ?? null, 4352),
                'timecreated'             => $this->sanitizeInt($quiz->obj->insert_date ?? null, time()),
                'timemodified'            => $this->sanitizeInt($quiz->obj->lastedit_date ?? null, time()),
                'password'                => (string) ($quiz->obj->password ?? ''),
                'subnet'                  => (string) ($quiz->obj->subnet ?? ''),
                'browsersecurity'         => (string) ($quiz->obj->browsersecurity ?? '-'),
                'delay1'                  => $this->sanitizeInt($quiz->obj->delay1 ?? null, 0),
                'delay2'                  => $this->sanitizeInt($quiz->obj->delay2 ?? null, 0),
                'showuserpicture'         => $this->sanitizeInt($quiz->obj->showuserpicture ?? null, 0),
                'showblocks'              => $this->sanitizeInt($quiz->obj->showblocks ?? null, 0),
                'completionattemptsexhausted' => $this->sanitizeInt($quiz->obj->completionattemptsexhausted ?? null, 0),
                'completionpass'          => $this->sanitizeInt($quiz->obj->completionpass ?? null, 0),
                'completionminattempts'   => $this->sanitizeInt($quiz->obj->completionminattempts ?? null, 0),
                'allowofflineattempts'    => $this->sanitizeInt($quiz->obj->allowofflineattempts ?? null, 0),
                'users'                   => [],
            ];
        }

        return [];
    }

    /**
     * Export one question entry in XML format.
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
     * Retrieve the questions for a specific quiz.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getQuestionsForQuiz(
        int $quizId,
        int $questionCategoryId,
        int $contextId,
        array $courseInfo,
        int $adminId,
        array &$quizFiles
    ): array {
        $questions = [];

        $quizResources =
            $this->course->resources[\defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : 'Exercise_Question']
            ?? $this->course->resources['Exercise_Question']
            ?? [];

        $quizQuestionIds = (array) ($this->course->resources[\defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz'][$quizId]->obj->question_ids ?? []);

        foreach ($quizResources as $questionId => $questionData) {
            if (!\is_object($questionData) || !\in_array($questionId, $quizQuestionIds, true)) {
                continue;
            }

            $questionPayload = $this->unwrapResourcePayload($questionData);
            if (!\is_object($questionPayload)) {
                continue;
            }

            $qtype = $this->mapQuestionType((string) ($questionPayload->quiz_type ?? $questionPayload->type ?? ''));
            if ('unknown' === $qtype) {
                continue;
            }

            $questionSourceId = (int) ($questionPayload->source_id ?? $questionPayload->id ?? $questionId);
            $qRes = $this->extractQuestionEmbeddedFilesAndNormalizeContent(
                (string) ($questionPayload->question ?? ''),
                $contextId,
                'questiontext',
                $questionSourceId,
                $courseInfo,
                $adminId
            );

            if (!empty($qRes['files'])) {
                $quizFiles = array_merge($quizFiles, $qRes['files']);
            }

            $answers = $this->getAnswersForQuestion(
                $questionSourceId,
                $contextId,
                $courseInfo,
                $adminId,
                $quizFiles
            );

            $questions[] = [
                'id' => $questionSourceId,
                'questiontext' => $qRes['content'],
                'qtype' => $qtype,
                'questioncategoryid' => $questionCategoryId,
                'answers' => $answers,
                'maxmark' => (float) ($questionPayload->ponderation ?? 1),
            ];
        }

        return $questions;
    }

    /**
     * Map legacy quiz type to Moodle qtype.
     */
    private function mapQuestionType(string $quizType): string
    {
        switch ($quizType) {
            case UNIQUE_ANSWER:
                return 'multichoice';

            case MULTIPLE_ANSWER:
                return 'multichoice_nosingle';

            case FILL_IN_BLANKS:
                return 'match';

            case FREE_ANSWER:
                return 'shortanswer';

            case CALCULATED_ANSWER:
                return 'calculated';

            case UPLOAD_ANSWER:
                return 'fileupload';

            default:
                return 'unknown';
        }
    }

    /**
     * Retrieves the answers for a specific question ID.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getAnswersForQuestion(
        int $questionId,
        int $contextId,
        array $courseInfo,
        int $adminId,
        array &$collectedFiles
    ): array {
        $answers = [];

        $quizResources =
            $this->course->resources[\defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : 'Exercise_Question']
            ?? $this->course->resources['Exercise_Question']
            ?? [];

        foreach ($quizResources as $questionData) {
            if (!\is_object($questionData)) {
                continue;
            }

            $questionPayload = $this->unwrapResourcePayload($questionData);
            if (!\is_object($questionPayload)) {
                continue;
            }

            $questionSourceId = (int) ($questionPayload->source_id ?? $questionPayload->id ?? 0);
            if ($questionSourceId !== $questionId) {
                continue;
            }

            $answerIndex = 0;
            foreach ((array) ($questionPayload->answers ?? []) as $answer) {
                $answerIndex++;

                $answerId = $this->buildStableQuestionAnswerId($questionId, $answerIndex);
                $rawText = (string) ($answer['answer'] ?? '');

                $res = $this->extractQuestionEmbeddedFilesAndNormalizeContent(
                    $rawText,
                    $contextId,
                    'answer',
                    $answerId,
                    $courseInfo,
                    $adminId
                );

                if (!empty($res['files'])) {
                    $collectedFiles = array_merge($collectedFiles, $res['files']);
                }

                $answers[] = [
                    'id' => $answerId,
                    'text' => $res['content'],
                    'fraction' => (($answer['correct'] ?? '0') == '1') ? 100 : 0,
                    'feedback' => (string) ($answer['comment'] ?? ''),
                ];
            }
        }

        return $answers;
    }

    /**
     * Retrieves feedbacks for a specific quiz.
     *
     * @return array<int,array<string,mixed>>
     */
    private function getFeedbacksForQuiz(int $quizId): array
    {
        $feedbacks = [];

        $quizResources =
            $this->course->resources[\defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz']
            ?? $this->course->resources['quiz']
            ?? [];

        foreach ($quizResources as $quiz) {
            if (!\is_object($quiz) || !isset($quiz->obj) || !\is_object($quiz->obj)) {
                continue;
            }

            if ((int) ($quiz->obj->iid ?? 0) === $quizId) {
                $feedbacks[] = [
                    'feedbacktext' => (string) ($quiz->obj->description ?? ''),
                    'mingrade' => 0.00000,
                    'maxgrade' => (float) ($quiz->obj->grade ?? 0.0),
                ];
            }
        }

        return $feedbacks;
    }

    /**
     * Creates the quiz.xml file.
     *
     * @param array<string,mixed> $quizData
     */
    private function createQuizXml(array $quizData, string $destinationDir): void
    {
        $preferredBehaviour = trim((string) ($quizData['preferredbehaviour'] ?? ''));
        if ('' === $preferredBehaviour) {
            $preferredBehaviour = 'deferredfeedback';
        }

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$quizData['id'].'" moduleid="'.$quizData['moduleid'].'" modulename="quiz" contextid="'.$quizData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <quiz id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) ($quizData['name'] ?? '')).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) ($quizData['intro'] ?? '')).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <timeopen>'.(int) ($quizData['timeopen'] ?? 0).'</timeopen>'.PHP_EOL;
        $xmlContent .= '    <timeclose>'.(int) ($quizData['timeclose'] ?? 0).'</timeclose>'.PHP_EOL;
        $xmlContent .= '    <timelimit>'.(int) ($quizData['timelimit'] ?? 0).'</timelimit>'.PHP_EOL;
        $xmlContent .= '    <overduehandling>'.htmlspecialchars((string) ($quizData['overduehandling'] ?? 'autosubmit')).'</overduehandling>'.PHP_EOL;
        $xmlContent .= '    <graceperiod>'.(int) ($quizData['graceperiod'] ?? 0).'</graceperiod>'.PHP_EOL;
        $xmlContent .= '    <preferredbehaviour>'.htmlspecialchars($preferredBehaviour).'</preferredbehaviour>'.PHP_EOL;
        $xmlContent .= '    <canredoquestions>'.(int) ($quizData['canredoquestions'] ?? 0).'</canredoquestions>'.PHP_EOL;
        $xmlContent .= '    <attempts_number>'.(int) ($quizData['attempts_number'] ?? 0).'</attempts_number>'.PHP_EOL;
        $xmlContent .= '    <attemptonlast>'.(int) ($quizData['attemptonlast'] ?? 0).'</attemptonlast>'.PHP_EOL;
        $xmlContent .= '    <grademethod>'.(int) ($quizData['grademethod'] ?? 1).'</grademethod>'.PHP_EOL;
        $xmlContent .= '    <decimalpoints>'.(int) ($quizData['decimalpoints'] ?? 2).'</decimalpoints>'.PHP_EOL;
        $xmlContent .= '    <questiondecimalpoints>'.(int) ($quizData['questiondecimalpoints'] ?? -1).'</questiondecimalpoints>'.PHP_EOL;
        $xmlContent .= '    <reviewattempt>'.(int) ($quizData['reviewattempt'] ?? 69888).'</reviewattempt>'.PHP_EOL;
        $xmlContent .= '    <reviewcorrectness>'.(int) ($quizData['reviewcorrectness'] ?? 4352).'</reviewcorrectness>'.PHP_EOL;
        $xmlContent .= '    <reviewmarks>'.(int) ($quizData['reviewmarks'] ?? 4352).'</reviewmarks>'.PHP_EOL;
        $xmlContent .= '    <reviewspecificfeedback>'.(int) ($quizData['reviewspecificfeedback'] ?? 4352).'</reviewspecificfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewgeneralfeedback>'.(int) ($quizData['reviewgeneralfeedback'] ?? 4352).'</reviewgeneralfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewrightanswer>'.(int) ($quizData['reviewrightanswer'] ?? 4352).'</reviewrightanswer>'.PHP_EOL;
        $xmlContent .= '    <reviewoverallfeedback>'.(int) ($quizData['reviewoverallfeedback'] ?? 4352).'</reviewoverallfeedback>'.PHP_EOL;
        $xmlContent .= '    <questionsperpage>'.(int) ($quizData['questionsperpage'] ?? 1).'</questionsperpage>'.PHP_EOL;
        $xmlContent .= '    <navmethod>'.htmlspecialchars((string) ($quizData['navmethod'] ?? 'free')).'</navmethod>'.PHP_EOL;
        $xmlContent .= '    <shuffleanswers>'.(int) ($quizData['shuffleanswers'] ?? 1).'</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '    <sumgrades>'.(float) ($quizData['sumgrades'] ?? 0.0).'</sumgrades>'.PHP_EOL;
        $xmlContent .= '    <grade>'.(float) ($quizData['grade'] ?? 0.0).'</grade>'.PHP_EOL;
        $xmlContent .= '    <timecreated>'.(int) ($quizData['timecreated'] ?? time()).'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.(int) ($quizData['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <password>'.htmlspecialchars((string) ($quizData['password'] ?? '')).'</password>'.PHP_EOL;
        $xmlContent .= '    <subnet>'.htmlspecialchars((string) ($quizData['subnet'] ?? '')).'</subnet>'.PHP_EOL;
        $xmlContent .= '    <browsersecurity>'.htmlspecialchars((string) ($quizData['browsersecurity'] ?? '-')).'</browsersecurity>'.PHP_EOL;
        $xmlContent .= '    <delay1>'.(int) ($quizData['delay1'] ?? 0).'</delay1>'.PHP_EOL;
        $xmlContent .= '    <delay2>'.(int) ($quizData['delay2'] ?? 0).'</delay2>'.PHP_EOL;
        $xmlContent .= '    <showuserpicture>'.(int) ($quizData['showuserpicture'] ?? 0).'</showuserpicture>'.PHP_EOL;
        $xmlContent .= '    <showblocks>'.(int) ($quizData['showblocks'] ?? 0).'</showblocks>'.PHP_EOL;
        $xmlContent .= '    <completionattemptsexhausted>'.(int) ($quizData['completionattemptsexhausted'] ?? 0).'</completionattemptsexhausted>'.PHP_EOL;
        $xmlContent .= '    <completionpass>'.(int) ($quizData['completionpass'] ?? 0).'</completionpass>'.PHP_EOL;
        $xmlContent .= '    <completionminattempts>'.(int) ($quizData['completionminattempts'] ?? 0).'</completionminattempts>'.PHP_EOL;
        $xmlContent .= '    <allowofflineattempts>'.(int) ($quizData['allowofflineattempts'] ?? 0).'</allowofflineattempts>'.PHP_EOL;
        $xmlContent .= '    <subplugin_quizaccess_seb_quiz>'.PHP_EOL;
        $xmlContent .= '    </subplugin_quizaccess_seb_quiz>'.PHP_EOL;

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

        $xmlContent .= '    <sections>'.PHP_EOL;
        $xmlContent .= '      <section id="'.(int) ($quizData['id'] ?? 0).'">'.PHP_EOL;
        $xmlContent .= '        <firstslot>1</firstslot>'.PHP_EOL;
        $xmlContent .= '        <shufflequestions>0</shufflequestions>'.PHP_EOL;
        $xmlContent .= '      </section>'.PHP_EOL;
        $xmlContent .= '    </sections>'.PHP_EOL;

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

        $xmlContent .= '    <overrides></overrides>'.PHP_EOL;
        $xmlContent .= '    <grades></grades>'.PHP_EOL;
        $xmlContent .= '    <attempts></attempts>'.PHP_EOL;
        $xmlContent .= '  </quiz>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

        $xmlFile = $destinationDir.'/quiz.xml';
        if (false === file_put_contents($xmlFile, $xmlContent)) {
            throw new Exception(sprintf(get_lang('Error creating %s'), 'quiz.xml'));
        }
    }

    /**
     * Exports a multiple-choice question in XML format.
     */
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

    /**
     * Exports a multiple-choice question with single=0 in XML format.
     */
    private function exportMultichoiceNosingleQuestion(array $question): string
    {
        return str_replace('<single>1</single>', '<single>0</single>', $this->exportMultichoiceQuestion($question));
    }

    /**
     * Exports a true/false question in XML format.
     */
    private function exportTrueFalseQuestion(array $question): string
    {
        $xmlContent = '        <plugin_qtype_truefalse_question>'.PHP_EOL;
        $xmlContent .= '          <answers>'.PHP_EOL;

        $answers = array_values($question['answers'] ?? []);
        foreach ($answers as $answer) {
            $xmlContent .= $this->exportAnswer($answer);
        }

        $xmlContent .= '          </answers>'.PHP_EOL;

        $trueId = 0;
        $falseId = 0;

        foreach ($answers as $ans) {
            $id = (int) ($ans['id'] ?? 0);
            $fraction = (int) ($ans['fraction'] ?? 0);

            if ($fraction > 0 && 0 === $trueId) {
                $trueId = $id;
            } elseif ($fraction <= 0 && 0 === $falseId) {
                $falseId = $id;
            }
        }

        if (0 === $trueId && isset($answers[0]['id'])) {
            $trueId = (int) $answers[0]['id'];
        }
        if (0 === $falseId && isset($answers[1]['id'])) {
            $falseId = (int) $answers[1]['id'];
        }
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

    /**
     * Exports a short answer question in XML format.
     */
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

    /**
     * Exports a matching question in XML format.
     */
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

        $answers = array_values($question['answers'] ?? []);
        $firstText = (string) ($answers[0]['text'] ?? '');

        $res = FillBlanks::getAnswerInfo($firstText);
        $words = (array) ($res['words'] ?? []);
        $common = (array) ($res['common_words'] ?? []);

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

    /**
     * Exports one answer in XML format.
     */
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

    /**
     * Moodle requires a valid question behaviour.
     */
    private function sanitizePreferredBehaviour($raw): string
    {
        $behaviour = trim((string) $raw);

        $allowed = [
            'deferredfeedback',
            'adaptive',
            'adaptivenopenalty',
            'immediatefeedback',
            'interactive',
            'manualgraded',
            'deferredcbm',
            'immediatecbm',
            'interactivecountback',
        ];

        if ('' === $behaviour || !\in_array($behaviour, $allowed, true)) {
            return 'deferredfeedback';
        }

        return $behaviour;
    }

    /**
     * Normalize an integer-like value.
     */
    private function sanitizeInt($raw, int $default): int
    {
        if (null === $raw) {
            return $default;
        }
        if (\is_string($raw) && '' === trim($raw)) {
            return $default;
        }

        return \is_numeric($raw) ? (int) $raw : $default;
    }

    /**
     * Parse a float or return null when missing.
     */
    private function parseNullableFloat($raw): ?float
    {
        if (null === $raw) {
            return null;
        }
        if (\is_string($raw) && '' === trim($raw)) {
            return null;
        }

        return \is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Moodle quiz navmethod accepted values.
     */
    private function sanitizeNavMethod($raw): string
    {
        $v = trim((string) $raw);

        return \in_array($v, ['free', 'sequential'], true) ? $v : 'free';
    }

    /**
     * Build a stable question category id per exported quiz module occurrence.
     */
    private function buildQuizQuestionCategoryId(int $moduleId): int
    {
        return 1000000000 + max(1, $moduleId);
    }

    /**
     * Build a stable question-bank context id for backup purposes.
     */
    private function buildQuestionBankContextId(int $courseId): int
    {
        return 600000000 + max(1, $courseId);
    }


    /**
     * Resolve the effective Moodle module id for one quiz export.
     */
    private function resolveEffectiveQuizModuleId(int $quizId, int $sectionId, ?int $moduleId = null): int
    {
        $effectiveModuleId = (int) ($moduleId ?? 0);
        if ($effectiveModuleId > 0) {
            return $effectiveModuleId;
        }

        if ($sectionId > 0) {
            $learnpaths =
                $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
                ?? $this->course->resources['learnpath']
                ?? [];

            if (\is_array($learnpaths)) {
                foreach ($learnpaths as $learnpathWrap) {
                    $learnpath = (\is_object($learnpathWrap) && isset($learnpathWrap->obj) && \is_object($learnpathWrap->obj))
                        ? $learnpathWrap->obj
                        : $learnpathWrap;

                    if (!\is_object($learnpath) || (int) ($learnpath->source_id ?? $learnpath->id ?? 0) !== $sectionId) {
                        continue;
                    }

                    foreach ((array) ($learnpath->items ?? []) as $item) {
                        if (!\is_array($item)) {
                            continue;
                        }

                        $itemType = (string) ($item['item_type'] ?? '');
                        $path = (string) ($item['path'] ?? '');
                        $lpItemId = (int) ($item['id'] ?? 0);

                        if ('quiz' === $itemType && ctype_digit($path) && (int) $path === $quizId && $lpItemId > 0) {
                            return 900000000 + $lpItemId;
                        }
                    }
                }
            }
        }

        return $quizId;
    }

    /**
     * Extract all candidate document URLs from HTML attributes and inline CSS.
     *
     * @return array<int,string>
     */
    private function extractDocumentReferenceUrlsFromHtml(string $html): array
    {
        if ('' === $html) {
            return [];
        }

        $urls = [];

        if (preg_match_all("~\\b(?:src|href|poster|data)\\s*=\\s*([\"'])([^\"']+)\\1~i", $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        if (preg_match_all("~\\bsrcset\\s*=\\s*([\"'])(.*?)\\1~is", $html, $matches)) {
            foreach ($matches[2] as $srcset) {
                foreach (array_map('trim', explode(',', (string) $srcset)) as $candidate) {
                    if ('' === $candidate) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', $candidate, -1, PREG_SPLIT_NO_EMPTY);
                    $url = $tokens[0] ?? '';
                    if ('' !== $url) {
                        $urls[] = $url;
                    }
                }
            }
        }

        if (preg_match_all("~url\\(([\"']?)([^)'\"]+)\\1\\)~i", $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Build a deterministic answer id for one question answer.
     */
    private function buildStableQuestionAnswerId(int $questionId, int $answerIndex): int
    {
        return ($questionId * 1000) + $answerIndex;
    }

    /**
     * Build a unique files.xml id for one embedded quiz file.
     */
    private function buildQuestionEmbeddedFileId(): int
    {
        self::$embeddedFileGlobalSeq++;

        return 1300000000 + self::$embeddedFileGlobalSeq;
    }

    /**
     * Extract embedded document files from quiz HTML and normalize URLs to @@PLUGINFILE@@.
     *
     * For quiz question/answer files:
     * - component = question
     * - filearea  = questiontext or answer
     * - itemid    = question id or answer id
     * - filepath  = real directory only
     * - HTML src  = @@PLUGINFILE@@/<relative path>
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractQuestionEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        string $fileArea,
        int $itemId,
        array $courseInfo,
        int $adminId
    ): array {
        if ('' === $html) {
            return ['content' => '', 'files' => []];
        }

        $fileExport = new \Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\FileExport($this->course);
        $files = [];
        $seenDocIds = [];

        $urls = $this->extractDocumentReferenceUrlsFromHtml($html);
        foreach ($urls as $src) {
            $document = $this->resolveEmbeddedQuestionDocumentData($src, $courseInfo);
            if (null === $document) {
                continue;
            }

            $docId = (int) ($document['id'] ?? 0);
            if ($docId <= 0 || isset($seenDocIds[$docId])) {
                continue;
            }

            $docPath = (string) ($document['path'] ?? '');
            if ('' === $docPath) {
                continue;
            }

            $absolutePath = $document['abs_path'] ?? $this->resolveQuestionDocumentAbsolutePath($docId, $docPath);
            $filename = basename($docPath);

            $files[] = [
                'id' => $this->buildQuestionEmbeddedFileId(),
                'contenthash' => is_file((string) $absolutePath) ? sha1_file((string) $absolutePath) : hash('sha1', $filename),
                'contextid' => $contextId,
                'component' => 'question',
                'filearea' => $fileArea,
                'itemid' => $itemId,
                'filepath' => $this->buildQuestionPluginFileDirectoryFromChamiloDocumentPath($docPath),
                'documentpath' => 'document/'.ltrim($docPath, '/'),
                'filename' => $filename,
                'userid' => $adminId,
                'filesize' => (int) ($document['size'] ?? 0),
                'mimetype' => $fileExport->getMimeType($docPath),
                'status' => 0,
                'timecreated' => time() - 3600,
                'timemodified' => time(),
                'source' => (string) ($document['title'] ?? $filename),
                'author' => 'Unknown',
                'license' => 'allrightsreserved',
                'abs_path' => $absolutePath,
            ];

            $seenDocIds[$docId] = true;
        }

        return [
            'content' => $this->normalizeQuestionHtmlContent($html),
            'files' => $files,
        ];
    }

    /**
     * Normalize HTML content by rewriting course document URLs to @@PLUGINFILE@@.
     */
    private function normalizeQuestionHtmlContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        $html = (string) preg_replace_callback(
            '~\bsrcset\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $val = $m[2];

                $parts = array_map('trim', explode(',', $val));
                foreach ($parts as &$p) {
                    if ('' === $p) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', $p, -1, PREG_SPLIT_NO_EMPTY);
                    if (empty($tokens)) {
                        continue;
                    }

                    $tokens[0] = $this->rewriteQuestionDocUrl($tokens[0]);
                    $p = implode(' ', $tokens);
                }

                return 'srcset='.$q.implode(', ', $parts).$q;
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])([^\'"]+)\2~i',
            function (array $m): string {
                return $m[1].'='.$m[2].$this->rewriteQuestionDocUrl($m[3]).$m[2];
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $style = $m[2];

                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        return 'url('.$mm[1].$this->rewriteQuestionDocUrl($mm[2]).$mm[1].')';
                    },
                    $style
                );

                return 'style='.$q.$style.$q;
            },
            $html
        );

        return (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m): string {
                $open = $m[1];
                $css = $m[2];
                $close = $m[3];

                $css = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        return 'url('.$mm[1].$this->rewriteQuestionDocUrl($mm[2]).$mm[1].')';
                    },
                    $css
                );

                return $open.$css.$close;
            },
            $html
        );
    }

    /**
     * Rewrite course document URLs to @@PLUGINFILE@@/<relative path>.
     */
    private function rewriteQuestionDocUrl(string $url): string
    {
        if ('' === $url || str_contains($url, '@@PLUGINFILE@@')) {
            return $url;
        }

        $documentPath = $this->resolveQuestionDocumentPathFromUrl($url);
        if (null === $documentPath || '' === trim($documentPath)) {
            return $url;
        }

        return '@@PLUGINFILE@@'.$this->buildQuestionPluginFilePathFromChamiloDocumentPath($documentPath);
    }

    /**
     * Build the pluginfile directory path from a Chamilo document path.
     *
     * Example:
     * - /folder/image.jpg -> /folder/
     * - image.jpg         -> /
     */
    private function buildQuestionPluginFileDirectoryFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $dir = dirname($relative);
        if ('.' === $dir || '/' === $dir) {
            return '/';
        }

        return '/'.trim($dir, '/').'/';
    }

    /**
     * Build the pluginfile full path used in question HTML.
     */
    private function buildQuestionPluginFilePathFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return '/'.$relative;
    }


    /**
     * Resolve a logical question document path from legacy or modern resource URLs.
     */
    private function resolveQuestionDocumentPathFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        $uuid = $this->extractQuestionResourceUuidFromUrl($url);
        if (null !== $uuid) {
            $doc = $this->findQuestionDocumentByResourceUuid($uuid);
            if ($doc instanceof CDocument) {
                $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
                if ('' !== $courseCode) {
                    $docData = DocumentManager::get_document_data_by_id((int) $doc->getIid(), $courseCode);
                    if (\is_array($docData) && !empty($docData['path'])) {
                        return '/'.ltrim((string) $docData['path'], '/');
                    }
                }

                $resourceNode = $doc->getResourceNode();
                if ($resourceNode instanceof ResourceNode) {
                    try {
                        $rawPath = (string) ($resourceNode->getPath() ?? '');
                        if ('' !== $rawPath) {
                            $displayPath = (string) $resourceNode->convertPathForDisplay($rawPath);
                            $displayPath = preg_replace('~^/?Documents/?~i', '', $displayPath) ?? $displayPath;
                            $displayPath = trim($displayPath, '/');
                            if ('' !== $displayPath) {
                                return '/'.$displayPath;
                            }
                        }
                    } catch (\Throwable) {
                        // Ignore and continue with URL parsing fallback.
                    }
                }
            }
        }

        $decoded = urldecode($url);
        $path = (string) (parse_url($decoded, PHP_URL_PATH) ?? '');
        if ('' === $path) {
            $path = $decoded;
        }

        if (preg_match('#/(?:courses/[^/]+/)?document(?P<docpath>/[^?\'" )]+)#i', $path, $m)) {
            return '/'.ltrim((string) $m['docpath'], '/');
        }

        if (preg_match('#^/?document(?P<docpath>/[^?\'" )]+)$#i', $path, $m)) {
            return '/'.ltrim((string) $m['docpath'], '/');
        }

        return null;
    }

    /**
     * Resolve embedded question document data from either a legacy document URL
     * or a modern /r/.../{uuid}/view URL.
     *
     * @return array<string,mixed>|null
     */
    private function resolveEmbeddedQuestionDocumentData(string $url, array $courseInfo): ?array
    {
        $courseCode = (string) ($courseInfo['code'] ?? $this->course->code ?? $this->course->info['code'] ?? '');
        if ('' === $courseCode) {
            return null;
        }

        $uuid = $this->extractQuestionResourceUuidFromUrl($url);
        if (null !== $uuid) {
            $doc = $this->findQuestionDocumentByResourceUuid($uuid);
            if ($doc instanceof CDocument) {
                $documentPath = null;

                $docData = DocumentManager::get_document_data_by_id((int) $doc->getIid(), $courseCode);
                if (\is_array($docData) && !empty($docData['path'])) {
                    $documentPath = (string) $docData['path'];
                }

                if ((null === $documentPath || '' === trim($documentPath)) && $doc->getResourceNode()) {
                    try {
                        $resourceNode = $doc->getResourceNode();
                        $rawPath = (string) ($resourceNode?->getPath() ?? '');
                        if ('' !== $rawPath) {
                            $displayPath = (string) $resourceNode->convertPathForDisplay($rawPath);
                            $displayPath = preg_replace('~^/?Documents/?~i', '', $displayPath) ?? $displayPath;
                            $displayPath = trim($displayPath, '/');
                            if ('' !== $displayPath) {
                                $documentPath = '/'.$displayPath;
                            }
                        }
                    } catch (\Throwable) {
                        // Ignore and continue with fallback logic.
                    }
                }

                if (null !== $documentPath && '' !== trim($documentPath)) {
                    $absolutePath = $this->resolveQuestionDocumentAbsolutePath((int) $doc->getIid(), $documentPath);
                    $size = 0;
                    $node = $doc->getResourceNode();
                    if ($node) {
                        $files = $node->getResourceFiles();
                        if ($files && $files->count() > 0) {
                            $first = $files->first();
                            if ($first instanceof ResourceFile) {
                                $size = (int) $first->getSize();
                            }
                        }
                    }

                    return [
                        'id' => (int) $doc->getIid(),
                        'path' => $documentPath,
                        'title' => method_exists($doc, 'getTitle') ? (string) $doc->getTitle() : basename($documentPath),
                        'size' => $size,
                        'abs_path' => $absolutePath,
                    ];
                }
            }
        }

        $documentPath = $this->resolveQuestionDocumentPathFromUrl($url);
        if (null === $documentPath || '' === trim($documentPath)) {
            return null;
        }

        $docId = DocumentManager::get_document_id($courseInfo, $documentPath);
        if (empty($docId)) {
            $docId = DocumentManager::get_document_id($courseInfo, ltrim($documentPath, '/'));
        }

        if (empty($docId)) {
            return null;
        }

        $document = DocumentManager::get_document_data_by_id((int) $docId, $courseCode);
        if (empty($document) || empty($document['path'])) {
            return null;
        }

        $document['abs_path'] = $this->resolveQuestionDocumentAbsolutePath((int) $docId, (string) $document['path']);

        return $document;
    }

    /**
     * Resolve the absolute document path for embedded quiz media.
     */
    private function resolveQuestionDocumentAbsolutePath(int $documentId, string $documentPath): ?string
    {
        if ($documentId > 0 && class_exists(Container::class)) {
            try {
                $repo = Container::getDocumentRepository();
                $doc = $repo->findOneBy(['iid' => $documentId]);
                if ($doc instanceof CDocument) {
                    $absPath = $repo->getAbsolutePathForDocument($doc);
                    if (is_file((string) $absPath)) {
                        return (string) $absPath;
                    }
                }
            } catch (\Throwable) {
                // Ignore and continue with fallback.
            }
        }

        $fallback = rtrim((string) $this->course->path, '/').'/document/'.ltrim($documentPath, '/');

        return is_file($fallback) ? $fallback : null;
    }

    /**
     * Extract a ResourceNode UUID from a modern resource URL.
     */
    private function extractQuestionResourceUuidFromUrl(string $url): ?string
    {
        if ('' === $url) {
            return null;
        }

        $decoded = urldecode($url);
        $path = (string) (parse_url($decoded, PHP_URL_PATH) ?? '');
        if ('' === $path) {
            $path = $decoded;
        }

        $path = ltrim($path, '/');

        if (preg_match('#^r/[^/]+/[^/]+/(?P<uuid>[A-Za-z0-9-]{16,64})/(?:view|download|link)/?$#i', $path, $matches)) {
            return (string) $matches['uuid'];
        }

        return null;
    }

    /**
     * Resolve a CDocument from a ResourceNode UUID.
     */
    private function findQuestionDocumentByResourceUuid(string $uuid): ?CDocument
    {
        if ('' === trim($uuid) || !class_exists(Container::class) || null === Container::$container) {
            return null;
        }

        try {
            /** @var ResourceNodeRepository $resourceNodeRepo */
            $resourceNodeRepo = Container::$container->get(ResourceNodeRepository::class);

            $resourceNode = $resourceNodeRepo->findOneBy(['uuid' => $uuid]);
            if (null === $resourceNode && class_exists(Uuid::class)) {
                try {
                    $resourceNode = $resourceNodeRepo->findOneBy(['uuid' => Uuid::fromString($uuid)]);
                } catch (\Throwable) {
                    $resourceNode = null;
                }
            }

            if (null === $resourceNode) {
                return null;
            }

            $docRepo = Container::getDocumentRepository();
            $doc = $docRepo->findOneBy(['resourceNode' => $resourceNode]);

            return $doc instanceof CDocument ? $doc : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Unwrap wrapped legacy payloads.
     *
     * @param mixed $resource
     */
    private function unwrapResourcePayload($resource): ?object
    {
        if (!\is_object($resource)) {
            return null;
        }

        if (isset($resource->obj) && \is_object($resource->obj)) {
            return $resource->obj;
        }

        return $resource;
    }

    /**
     * Remove the internal Chamilo document prefix from a path.
     */
    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        return (string) preg_replace('#^/?document/#', '', $path);
    }
}
