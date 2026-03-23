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
    private static $embeddedFileGlobalSeq = 0;

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
        $quizDir = $this->prepareActivityDirectory($exportDir, 'quiz', (int) $moduleId);
        $quizData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);

        if (empty($quizData)) {
            return;
        }

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

        if ($behaviour === '' || !in_array($behaviour, $allowed, true)) {
            return 'deferredfeedback';
        }

        return $behaviour;
    }

    /**
     * Normalize an integer-like value.
     */
    private function sanitizeInt($raw, int $default): int
    {
        if ($raw === null) {
            return $default;
        }
        if (is_string($raw) && trim($raw) === '') {
            return $default;
        }
        return is_numeric($raw) ? (int) $raw : $default;
    }

    /**
     * Parse a float or return null when missing.
     */
    private function parseNullableFloat($raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw) && trim($raw) === '') {
            return null;
        }
        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Moodle quiz navmethod accepted values.
     */
    private function sanitizeNavMethod($raw): string
    {
        $v = trim((string) $raw);
        return in_array($v, ['free', 'sequential'], true) ? $v : 'free';
    }

    /**
     * Build a stable question category id per exported quiz occurrence.
     */
    private function buildQuizQuestionCategoryId(int $moduleId): int
    {
        return 1000000000 + max(1, $moduleId);
    }

    /**
     * Retrieves the quiz data.
     */
    public function getData(int $quizId, int $sectionId, ?int $moduleId = null): array
    {
        $quizResources = $this->course->resources[RESOURCE_QUIZ] ?? [];

        foreach ($quizResources as $quiz) {
            if ($quiz->obj->iid == -1) {
                continue;
            }

            if ((int) $quiz->obj->iid !== $quizId) {
                continue;
            }

            $courseCode = $this->course->info['code'] ?? ($this->course->code ?? '');
            $courseInfo = api_get_course_info($courseCode);
            $courseRealId = (int) ($courseInfo['real_id'] ?? 0);

            $contextid = $this->buildQuestionBankContextId($courseRealId);

            $effectiveModuleId = (int) ($moduleId ?? ($quiz->obj->iid ?? 0));
            if ($effectiveModuleId <= 0) {
                $effectiveModuleId = (int) ($quiz->obj->iid ?? 0);
            }

            $questionCategoryId = $this->buildQuizQuestionCategoryId($effectiveModuleId);

            $name = (string) ($quiz->obj->title ?? '');
            if ($sectionId > 0) {
                $name = $this->lpItemTitle($sectionId, RESOURCE_QUIZ, $quizId, $name);
            }
            $name = $this->sanitizeMoodleActivityName($name, 255);

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
                'id' => (int) ($quiz->obj->iid ?? 0),
                'name' => $name,
                'intro' => (string) ($quiz->obj->description ?? ''),
                'timeopen' => $this->sanitizeInt($quiz->obj->start_time ?? null, 0),
                'timeclose' => $this->sanitizeInt($quiz->obj->end_time ?? null, 0),
                'timelimit' => $this->sanitizeInt($quiz->obj->timelimit ?? null, 0),
                'grademethod' => $this->sanitizeInt($quiz->obj->grademethod ?? null, 1),
                'decimalpoints' => $this->sanitizeInt($quiz->obj->decimalpoints ?? null, 2),
                'sumgrades' => $sumgrades,
                'grade' => $grade,
                'questionsperpage' => $this->sanitizeInt($quiz->obj->questionsperpage ?? null, 1),
                'preferredbehaviour' => $this->sanitizePreferredBehaviour($quiz->obj->preferredbehaviour ?? null),
                'shuffleanswers' => $this->sanitizeInt($quiz->obj->shuffleanswers ?? null, 1),
                'navmethod' => $this->sanitizeNavMethod($quiz->obj->navmethod ?? null),
                'question_category_id' => $questionCategoryId,
                'questions' => $questions,
                'files' => $quizFiles,
                'feedbacks' => $this->getFeedbacksForQuiz($quizId),
                'sectionid' => $sectionId,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'quiz',
                'contextid' => $contextid,
                'courseid' => $courseRealId,
                'overduehandling' => (string) ($quiz->obj->overduehandling ?? 'autosubmit'),
                'graceperiod' => $this->sanitizeInt($quiz->obj->graceperiod ?? null, 0),
                'canredoquestions' => $this->sanitizeInt($quiz->obj->canredoquestions ?? null, 0),
                'attempts_number' => $this->sanitizeInt($quiz->obj->attempts_number ?? null, 0),
                'attemptonlast' => $this->sanitizeInt($quiz->obj->attemptonlast ?? null, 0),
                'questiondecimalpoints' => $this->sanitizeInt($quiz->obj->questiondecimalpoints ?? null, 2),
                'reviewattempt' => $this->sanitizeInt($quiz->obj->reviewattempt ?? null, 0),
                'reviewcorrectness' => $this->sanitizeInt($quiz->obj->reviewcorrectness ?? null, 0),
                'reviewmarks' => $this->sanitizeInt($quiz->obj->reviewmarks ?? null, 0),
                'reviewspecificfeedback' => $this->sanitizeInt($quiz->obj->reviewspecificfeedback ?? null, 0),
                'reviewgeneralfeedback' => $this->sanitizeInt($quiz->obj->reviewgeneralfeedback ?? null, 0),
                'reviewrightanswer' => $this->sanitizeInt($quiz->obj->reviewrightanswer ?? null, 0),
                'reviewoverallfeedback' => $this->sanitizeInt($quiz->obj->reviewoverallfeedback ?? null, 0),
                'timecreated' => $this->sanitizeInt($quiz->obj->insert_date ?? null, time()),
                'timemodified' => $this->sanitizeInt($quiz->obj->lastedit_date ?? null, time()),
                'password' => (string) ($quiz->obj->password ?? ''),
                'subnet' => (string) ($quiz->obj->subnet ?? ''),
                'browsersecurity' => (string) ($quiz->obj->browsersecurity ?? '-'),
                'delay1' => $this->sanitizeInt($quiz->obj->delay1 ?? null, 0),
                'delay2' => $this->sanitizeInt($quiz->obj->delay2 ?? null, 0),
                'showuserpicture' => $this->sanitizeInt($quiz->obj->showuserpicture ?? null, 0),
                'showblocks' => $this->sanitizeInt($quiz->obj->showblocks ?? null, 0),
                'completionattemptsexhausted' => $this->sanitizeInt($quiz->obj->completionattemptsexhausted ?? null, 0),
                'completionpass' => $this->sanitizeInt($quiz->obj->completionpass ?? null, 0),
                'completionminattempts' => $this->sanitizeInt($quiz->obj->completionminattempts ?? null, 0),
                'allowofflineattempts' => $this->sanitizeInt($quiz->obj->allowofflineattempts ?? null, 0),
                'users' => [],
            ];
        }

        return [];
    }

    /**
     * Exports a question in XML format.
     */
    public function exportQuestion(array $question): string
    {
        $questionText = (string) ($question['questiontext'] ?? 'No question text');
        $questionName = $this->sanitizeQuestionName($questionText, 255);

        $xmlContent = '      <question id="'.($question['id'] ?? '0').'">'.PHP_EOL;
        $xmlContent .= '        <parent>0</parent>'.PHP_EOL;
        $xmlContent .= '        <name>'.htmlspecialchars($questionName).'</name>'.PHP_EOL;
        $xmlContent .= '        <questiontext>'.htmlspecialchars($questionText).'</questiontext>'.PHP_EOL;
        $xmlContent .= '        <questiontextformat>1</questiontextformat>'.PHP_EOL;
        $xmlContent .= '        <generalfeedback></generalfeedback>'.PHP_EOL;
        $xmlContent .= '        <generalfeedbackformat>1</generalfeedbackformat>'.PHP_EOL;
        $xmlContent .= '        <defaultmark>'.($question['maxmark'] ?? '0').'</defaultmark>'.PHP_EOL;
        $xmlContent .= '        <penalty>0.3333333</penalty>'.PHP_EOL;
        $xmlContent .= '        <qtype>'.htmlspecialchars((string) str_replace('_nosingle', '', $question['qtype'] ?? 'unknown')).'</qtype>'.PHP_EOL;
        $xmlContent .= '        <length>1</length>'.PHP_EOL;
        $xmlContent .= '        <stamp>moodle+'.time().'+QUESTIONSTAMP</stamp>'.PHP_EOL;
        $xmlContent .= '        <version>moodle+'.time().'+VERSIONSTAMP</version>'.PHP_EOL;
        $xmlContent .= '        <hidden>0</hidden>'.PHP_EOL;
        $xmlContent .= '        <timecreated>'.time().'</timecreated>'.PHP_EOL;
        $xmlContent .= '        <timemodified>'.time().'</timemodified>'.PHP_EOL;
        $xmlContent .= '        <createdby>2</createdby>'.PHP_EOL;
        $xmlContent .= '        <modifiedby>2</modifiedby>'.PHP_EOL;

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
    private function getQuestionsForQuiz(
        int $quizId,
        int $questionCategoryId,
        int $contextId,
        array $courseInfo,
        int $adminId,
        array &$quizFiles
    ): array {
        $questions = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionId => $questionData) {
            if (!in_array($questionId, $this->course->resources[RESOURCE_QUIZ][$quizId]->obj->question_ids)) {
                continue;
            }

            $qRes = $this->extractQuestionEmbeddedFilesAndNormalizeContent(
                (string) ($questionData->question ?? ''),
                $contextId,
                'questiontext',
                (int) $questionData->source_id,
                '',
                $courseInfo,
                $adminId
            );

            if (!empty($qRes['files'])) {
                $quizFiles = array_merge($quizFiles, $qRes['files']);
            }

            $answers = $this->getAnswersForQuestion(
                (int) $questionData->source_id,
                $contextId,
                $courseInfo,
                $adminId,
                $quizFiles
            );

            $mappedType = $this->mapQuestionType((string) $questionData->quiz_type);

            if ($mappedType === 'unknown') {
                continue;
            }

            $questions[] = [
                'id' => (int) $questionData->source_id,
                'questiontext' => $qRes['content'],
                'qtype' => $mappedType,
                'questioncategoryid' => $questionCategoryId,
                'answers' => $answers,
                'maxmark' => $questionData->ponderation ?? 1,
            ];
        }

        return $questions;
    }

    /**
     * Maps the quiz type code to a descriptive string.
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
     */
    private function getAnswersForQuestion(
        int $questionId,
        int $contextId,
        array $courseInfo,
        int $adminId,
        array &$collectedFiles
    ): array {
        $answers = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? [];

        foreach ($quizResources as $questionData) {
            if ((int) $questionData->source_id !== $questionId) {
                continue;
            }

            $answerIndex = 0;

            foreach ($questionData->answers as $answer) {
                $answerIndex++;

                $answerId = $this->buildStableQuestionAnswerId($questionId, $answerIndex);
                $rawText = (string) ($answer['answer'] ?? '');

                $res = $this->extractQuestionEmbeddedFilesAndNormalizeContent(
                    $rawText,
                    $contextId,
                    'answer',
                    $answerId,
                    '',
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
                    'maxgrade' => $quiz->obj->grade ?? 10.00000,
                ];
            }
        }

        return $feedbacks;
    }

    /**
     * Creates the quiz.xml file.
     */
    private function createQuizXml(array $quizData, string $destinationDir): void
    {
        $preferredBehaviour = trim((string) ($quizData['preferredbehaviour'] ?? ''));
        if ($preferredBehaviour === '') {
            $preferredBehaviour = 'deferredfeedback';
        }

        $gradeMethod = (int) ($quizData['grademethod'] ?? 1);
        $decimalPoints = (int) ($quizData['decimalpoints'] ?? 2);
        $questionsPerPage = (int) ($quizData['questionsperpage'] ?? 1);
        $navMethod = (string) ($quizData['navmethod'] ?? 'free');
        $shuffleAnswers = (int) ($quizData['shuffleanswers'] ?? 1);
        $sumGrades = (float) ($quizData['sumgrades'] ?? 0.0);
        $grade = (float) ($quizData['grade'] ?? 0.0);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$quizData['id'].'" moduleid="'.$quizData['moduleid'].'" modulename="quiz" contextid="'.$quizData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <quiz id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $quizData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) $quizData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <timeopen>'.($quizData['timeopen'] ?? 0).'</timeopen>'.PHP_EOL;
        $xmlContent .= '    <timeclose>'.($quizData['timeclose'] ?? 0).'</timeclose>'.PHP_EOL;
        $xmlContent .= '    <timelimit>'.($quizData['timelimit'] ?? 0).'</timelimit>'.PHP_EOL;
        $xmlContent .= '    <overduehandling>'.($quizData['overduehandling'] ?? 'autosubmit').'</overduehandling>'.PHP_EOL;
        $xmlContent .= '    <graceperiod>'.($quizData['graceperiod'] ?? 0).'</graceperiod>'.PHP_EOL;
        $xmlContent .= '    <preferredbehaviour>'.htmlspecialchars($preferredBehaviour).'</preferredbehaviour>'.PHP_EOL;
        $xmlContent .= '    <canredoquestions>'.($quizData['canredoquestions'] ?? 0).'</canredoquestions>'.PHP_EOL;
        $xmlContent .= '    <attempts_number>'.($quizData['attempts_number'] ?? 0).'</attempts_number>'.PHP_EOL;
        $xmlContent .= '    <attemptonlast>'.($quizData['attemptonlast'] ?? 0).'</attemptonlast>'.PHP_EOL;
        $xmlContent .= '    <grademethod>'.$gradeMethod.'</grademethod>'.PHP_EOL;
        $xmlContent .= '    <decimalpoints>'.$decimalPoints.'</decimalpoints>'.PHP_EOL;
        $xmlContent .= '    <questiondecimalpoints>'.($quizData['questiondecimalpoints'] ?? -1).'</questiondecimalpoints>'.PHP_EOL;
        $xmlContent .= '    <reviewattempt>'.($quizData['reviewattempt'] ?? 69888).'</reviewattempt>'.PHP_EOL;
        $xmlContent .= '    <reviewcorrectness>'.($quizData['reviewcorrectness'] ?? 4352).'</reviewcorrectness>'.PHP_EOL;
        $xmlContent .= '    <reviewmarks>'.($quizData['reviewmarks'] ?? 4352).'</reviewmarks>'.PHP_EOL;
        $xmlContent .= '    <reviewspecificfeedback>'.($quizData['reviewspecificfeedback'] ?? 4352).'</reviewspecificfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewgeneralfeedback>'.($quizData['reviewgeneralfeedback'] ?? 4352).'</reviewgeneralfeedback>'.PHP_EOL;
        $xmlContent .= '    <reviewrightanswer>'.($quizData['reviewrightanswer'] ?? 4352).'</reviewrightanswer>'.PHP_EOL;
        $xmlContent .= '    <reviewoverallfeedback>'.($quizData['reviewoverallfeedback'] ?? 4352).'</reviewoverallfeedback>'.PHP_EOL;
        $xmlContent .= '    <questionsperpage>'.$questionsPerPage.'</questionsperpage>'.PHP_EOL;
        $xmlContent .= '    <navmethod>'.htmlspecialchars($navMethod).'</navmethod>'.PHP_EOL;
        $xmlContent .= '    <shuffleanswers>'.$shuffleAnswers.'</shuffleanswers>'.PHP_EOL;
        $xmlContent .= '    <sumgrades>'.$sumGrades.'</sumgrades>'.PHP_EOL;
        $xmlContent .= '    <grade>'.$grade.'</grade>'.PHP_EOL;
        $xmlContent .= '    <timecreated>'.($quizData['timecreated'] ?? time()).'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.($quizData['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <password>'.(isset($quizData['password']) ? htmlspecialchars((string) $quizData['password']) : '').'</password>'.PHP_EOL;
        $xmlContent .= '    <subnet>'.(isset($quizData['subnet']) ? htmlspecialchars((string) $quizData['subnet']) : '').'</subnet>'.PHP_EOL;
        $xmlContent .= '    <browsersecurity>'.(isset($quizData['browsersecurity']) ? htmlspecialchars((string) $quizData['browsersecurity']) : '-').'</browsersecurity>'.PHP_EOL;
        $xmlContent .= '    <delay1>'.($quizData['delay1'] ?? 0).'</delay1>'.PHP_EOL;
        $xmlContent .= '    <delay2>'.($quizData['delay2'] ?? 0).'</delay2>'.PHP_EOL;
        $xmlContent .= '    <showuserpicture>'.($quizData['showuserpicture'] ?? 0).'</showuserpicture>'.PHP_EOL;
        $xmlContent .= '    <showblocks>'.($quizData['showblocks'] ?? 0).'</showblocks>'.PHP_EOL;
        $xmlContent .= '    <completionattemptsexhausted>'.($quizData['completionattemptsexhausted'] ?? 0).'</completionattemptsexhausted>'.PHP_EOL;
        $xmlContent .= '    <completionpass>'.($quizData['completionpass'] ?? 0).'</completionpass>'.PHP_EOL;
        $xmlContent .= '    <completionminattempts>'.($quizData['completionminattempts'] ?? 0).'</completionminattempts>'.PHP_EOL;
        $xmlContent .= '    <allowofflineattempts>'.($quizData['allowofflineattempts'] ?? 0).'</allowofflineattempts>'.PHP_EOL;
        $xmlContent .= '    <subplugin_quizaccess_seb_quiz>'.PHP_EOL;
        $xmlContent .= '    </subplugin_quizaccess_seb_quiz>'.PHP_EOL;
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
        $xmlContent .= '    <sections>'.PHP_EOL;
        $xmlContent .= '      <section id="'.$quizData['id'].'">'.PHP_EOL;
        $xmlContent .= '        <firstslot>1</firstslot>'.PHP_EOL;
        $xmlContent .= '        <shufflequestions>0</shufflequestions>'.PHP_EOL;
        $xmlContent .= '      </section>'.PHP_EOL;
        $xmlContent .= '    </sections>'.PHP_EOL;
        $xmlContent .= '    <feedbacks>'.PHP_EOL;
        foreach ($quizData['feedbacks'] as $feedback) {
            $xmlContent .= '      <feedback id="'.$quizData['id'].'">'.PHP_EOL;
            $xmlContent .= '        <feedbacktext>'.htmlspecialchars((string) $feedback['feedbacktext']).'</feedbacktext>'.PHP_EOL;
            $xmlContent .= '        <feedbacktextformat>1</feedbacktextformat>'.PHP_EOL;
            $xmlContent .= '        <mingrade>'.$feedback['mingrade'].'</mingrade>'.PHP_EOL;
            $xmlContent .= '        <maxgrade>'.$feedback['maxgrade'].'</maxgrade>'.PHP_EOL;
            $xmlContent .= '      </feedback>'.PHP_EOL;
        }
        $xmlContent .= '    </feedbacks>'.PHP_EOL;
        $xmlContent .= '    <overrides>'.PHP_EOL.'    </overrides>'.PHP_EOL;
        $xmlContent .= '    <grades>'.PHP_EOL.'    </grades>'.PHP_EOL;
        $xmlContent .= '    <attempts>'.PHP_EOL.'    </attempts>'.PHP_EOL;
        $xmlContent .= '  </quiz>'.PHP_EOL;
        $xmlContent .= '</activity>'.PHP_EOL;

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
        return str_replace('<single>1</single>', '<single>0</single>', $this->exportMultichoiceQuestion($question));
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
            '              <answertext>'.htmlspecialchars((string) ($answer['text'] ?? 'No answer text')).'</answertext>'.PHP_EOL.
            '              <answerformat>1</answerformat>'.PHP_EOL.
            '              <fraction>'.($answer['fraction'] ?? '0').'</fraction>'.PHP_EOL.
            '              <feedback>'.htmlspecialchars((string) ($answer['feedback'] ?? '')).'</feedback>'.PHP_EOL.
            '              <feedbackformat>1</feedbackformat>'.PHP_EOL.
            '            </answer>'.PHP_EOL;
    }

    private function ensureTrailingSlash(string $path): string
    {
        if ($path === '' || $path === '.' || $path === '/') {
            return '/';
        }
        $path = preg_replace('#/+#', '/', $path);
        return rtrim($path, '/').'/';
    }

    private function buildQuestionEmbeddedFileId(int $baseId, int $sequence): int
    {
        self::$embeddedFileGlobalSeq++;

        return 1300000000 + self::$embeddedFileGlobalSeq;
    }

    /**
     * Extract embedded /document/* images from HTML and rewrite src to @@PLUGINFILE@@.
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractQuestionEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        string $fileArea,
        int $itemId,
        string $prefix,
        array $courseInfo,
        int $adminId
    ): array {
        if ($html === '') {
            return ['content' => '', 'files' => []];
        }

        $fileExport = new FileExport($this->course);
        $files = [];
        $seenDocIds = [];
        $sequence = 0;

        $normalizedHtml = preg_replace_callback(
            '#<img[^>]+src=["\'](?<url>[^"\']+)["\']#i',
            function ($match) use (
                $contextId,
                $fileArea,
                $itemId,
                $courseInfo,
                $adminId,
                $fileExport,
                &$files,
                &$seenDocIds,
                &$sequence
            ) {
                $src = (string) ($match['url'] ?? '');

                if (!preg_match('#/document(?P<path>/[^"\']+)#', $src, $m)) {
                    return $match[0];
                }

                $docRelPath = (string) $m['path'];
                $docId = \DocumentManager::get_document_id($courseInfo, $docRelPath);
                if (empty($docId)) {
                    return $match[0];
                }

                $docId = (int) $docId;

                if (!isset($seenDocIds[$docId])) {
                    $doc = \DocumentManager::get_document_data_by_id($docId, $courseInfo['code']);

                    if (!empty($doc) && !empty($doc['path'])) {
                        $docPath = (string) $doc['path'];
                        $rel = ltrim(str_replace('\\', '/', $docPath), '/');
                        $dir = dirname($rel);
                        $dir = ($dir === '.' ? '' : $dir.'/');
                        $filepath = '/'.$dir;
                        $filepath = $this->ensureTrailingSlash($filepath);

                        $filename = basename($rel);
                        $absolutePath = $this->course->path.'document'.$docPath;

                        $sequence++;
                        $fileId = $this->buildQuestionEmbeddedFileId($itemId, $sequence);

                        $files[] = [
                            'id' => $fileId,
                            'contenthash' => is_file($absolutePath) ? sha1_file($absolutePath) : hash('sha1', $filename),
                            'contextid' => $contextId,
                            'component' => 'question',
                            'filearea' => $fileArea,
                            'itemid' => $itemId,
                            'filepath' => $filepath,
                            'documentpath' => 'document'.$docPath,
                            'filename' => $filename,
                            'userid' => $adminId,
                            'filesize' => (int) ($doc['size'] ?? 0),
                            'mimetype' => $fileExport->getMimeType($docPath),
                            'status' => 0,
                            'timecreated' => time() - 3600,
                            'timemodified' => time(),
                            'source' => (string) ($doc['title'] ?? $filename),
                            'author' => 'Unknown',
                            'license' => 'allrightsreserved',
                        ];
                    }

                    $seenDocIds[$docId] = true;
                }

                $relHtml = ltrim($docRelPath, '/');
                $pluginPath = '@@PLUGINFILE@@/'.$relHtml;

                return str_replace($src, $pluginPath, $match[0]);
            },
            $html
        );

        return [
            'content' => (string) $normalizedHtml,
            'files' => $files,
        ];
    }

    /**
     * Build a deterministic answer id for one question answer.
     */
    private function buildStableQuestionAnswerId(int $questionId, int $answerIndex): int
    {
        return ($questionId * 1000) + $answerIndex;
    }

    /**
     * Build a stable question-bank course context id for backup purposes.
     */
    private function buildQuestionBankContextId(int $courseId): int
    {
        $contextId = MoodleExport::getBackupCourseContextId();

        if ($contextId > 0) {
            return $contextId;
        }

        return 700000000 + max(1, $courseId);
    }

    /**
     * Build a clean question name from the question text.
     */
    private function sanitizeQuestionName(string $raw, int $maxLen = 255): string
    {
        $text = trim($raw);
        if ($text === '') {
            return 'Question';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return 'Question';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxLen) {
                $text = mb_substr($text, 0, $maxLen, 'UTF-8');
            }
        } elseif (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen);
        }

        return $text;
    }
}
