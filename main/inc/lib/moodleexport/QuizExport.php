<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class QuizExport.
 *
 * @package moodleexport
 */
class QuizExport
{

    private $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export a quiz to the specified directory.
     *
     * @param int $quizId The ID of the quiz.
     * @param string $exportDir The directory where the quiz will be exported.
     * @param int $moduleId The ID of the module.
     * @param int $sectionId The ID of the section.
     */
    public function exportQuiz($quizId, $exportDir, $moduleId, $sectionId)
    {
        // Directory where the quiz export will be saved (activities/quiz_XXX)
        $quizDir = $exportDir . "/activities/quiz_{$moduleId}";

        if (!is_dir($quizDir)) {
            mkdir($quizDir, api_get_permissions_for_new_directories(), true);
        }

        $quizData = $this->getQuizData($quizId, $sectionId);

        $this->createQuizXml($quizData, $quizDir);
        $this->createGradesXml($quizData, $quizDir);
        $this->createCompletionXml($quizData, $quizDir);
        $this->createCommentsXml($quizData, $quizDir);
        $this->createCompetenciesXml($quizData, $quizDir);
        $this->createFiltersXml($quizData, $quizDir);
        $this->createGradeHistoryXml($quizData, $quizDir);
        $this->createInforefXml($quizData, $quizDir);
        $this->createRolesXml($quizData, $quizDir);
        $this->createCalendarXml($quizData, $quizDir);
        $this->createModuleXml($quizData, $quizDir);
    }

    /**
     * Retrieves the quiz data.
     *
     * @param int $quizId The ID of the quiz.
     * @param int $sectionId The ID of the section.
     *
     * @return array Quiz data.
     */
    public function getQuizData($quizId, $sectionId)
    {
        $quizResources = $this->course->resources[RESOURCE_QUIZ];

        foreach ($quizResources as $quiz) {
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
                ];
            }
        }

        return null;
    }

    /**
     * Gets the section ID for a given quiz ID.
     *
     * @param int $quizId The ID of the quiz.
     *
     * @return int The section ID or 0 if not found.
     */
    public function getSectionIdForQuiz($quizId)
    {
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            foreach ($learnpath->items as $item) {
                if ($item['item_type'] == 'quiz' && $item['path'] == $quizId) {
                    return $learnpath->source_id;
                }
            }
        }

        return 0;
    }

    /**
     * Retrieves the questions for a specific quiz.
     *
     * @param int $quizId The ID of the quiz.
     *
     * @return array List of questions related to the quiz.
     */
    private function getQuestionsForQuiz($quizId)
    {
        $questions = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? []; // Get quiz questions

        // Loop through quiz questions
        foreach ($quizResources as $questionId => $questionData) {
            // Check if question is part of the quiz
            if (in_array($questionId, $this->course->resources[RESOURCE_QUIZ][$quizId]->obj->question_ids)) {
                $questions[] = [
                    'id' => $questionData->source_id ?? $questionId, // Question ID
                    'questiontext' => $questionData->question ?? '', // Question text
                    'qtype' => $this->mapQuizType($questionData->quiz_type ?? '0'), // Question type
                    'questioncategoryid' => $questionData->question_category ?? 0, // Category ID
                    'answers' => $this->getAnswersForQuestion($questionData->source_id ?? $questionId), // Answers
                    'maxmark' => $questionData->ponderation ?? 0, // Maximum mark
                ];
            }
        }

        return $questions;
    }

    /**
     * Retrieves the answers for a specific question ID.
     *
     * @param int $questionId The ID of the question.
     *
     * @return array List of answers for the question.
     */
    private function getAnswersForQuestion($questionId)
    {
        $answers = [];
        $quizResources = $this->course->resources[RESOURCE_QUIZQUESTION] ?? []; // Get quiz questions

        // Loop through quiz resources
        foreach ($quizResources as $questionData) {
            // Check if the question ID matches
            if ($questionData->source_id == $questionId) {
                foreach ($questionData->answers as $answer) {
                    $answers[] = [
                        'text' => $answer['answer'] ?? '', // Answer text
                        'fraction' => $answer['correct'] == '1' ? 1.0000000 : 0.0000000, // Answer fraction
                        'feedback' => $answer['comment'] ?? '', // Feedback text
                    ];
                }
            }
        }

        return $answers;
    }


    /**
     * Maps the quiz type code to a descriptive string.
     *
     * @param string $quizType The code for the quiz type.
     *
     * @return string The descriptive quiz type.
     */
    private function mapQuizType($quizType)
    {
        // Maps the quiz type code to a descriptive string.
        switch ($quizType) {
            case '1':
                return 'multichoice'; // Multiple choice questions.
            case '2':
                return 'truefalse'; // True/false questions.
            // Add other types as needed.
            default:
                return 'unknown'; // Unknown quiz type.
        }
    }

    /**
     * Retrieves feedbacks for a specific quiz.
     *
     * @param int $quizId The ID of the quiz.
     * @return array Feedbacks associated with the quiz.
     */
    private function getFeedbacksForQuiz($quizId)
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

    /**
     * Creates the quiz.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createQuizXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<activity id="' . $quizData['id'] . '" moduleid="' . $quizData['moduleid'] . '" modulename="quiz" contextid="' . $quizData['contextid'] . '">' . PHP_EOL;
        $xmlContent .= '  <quiz id="' . $quizData['id'] . '">' . PHP_EOL;
        $xmlContent .= '    <name>' . htmlspecialchars($quizData['name']) . '</name>' . PHP_EOL;
        $xmlContent .= '    <intro>' . htmlspecialchars($quizData['intro']) . '</intro>' . PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>' . PHP_EOL;
        $xmlContent .= '    <timeopen>' . ($quizData['timeopen'] ?? 0) . '</timeopen>' . PHP_EOL;
        $xmlContent .= '    <timeclose>' . ($quizData['timeclose'] ?? 0) . '</timeclose>' . PHP_EOL;
        $xmlContent .= '    <timelimit>' . ($quizData['timelimit'] ?? 0) . '</timelimit>' . PHP_EOL;
        $xmlContent .= '    <overduehandling>' . ($quizData['overduehandling'] ?? 'autosubmit') . '</overduehandling>' . PHP_EOL;
        $xmlContent .= '    <graceperiod>' . ($quizData['graceperiod'] ?? 0) . '</graceperiod>' . PHP_EOL;
        $xmlContent .= '    <preferredbehaviour>' . htmlspecialchars($quizData['preferredbehaviour']) . '</preferredbehaviour>' . PHP_EOL;
        $xmlContent .= '    <canredoquestions>' . ($quizData['canredoquestions'] ?? 0) . '</canredoquestions>' . PHP_EOL;
        $xmlContent .= '    <attempts_number>' . ($quizData['attempts_number'] ?? 0) . '</attempts_number>' . PHP_EOL;
        $xmlContent .= '    <attemptonlast>' . ($quizData['attemptonlast'] ?? 0) . '</attemptonlast>' . PHP_EOL;
        $xmlContent .= '    <grademethod>' . $quizData['grademethod'] . '</grademethod>' . PHP_EOL;
        $xmlContent .= '    <decimalpoints>' . $quizData['decimalpoints'] . '</decimalpoints>' . PHP_EOL;
        $xmlContent .= '    <questiondecimalpoints>' . ($quizData['questiondecimalpoints'] ?? -1) . '</questiondecimalpoints>' . PHP_EOL;

        // Review options
        $xmlContent .= '    <reviewattempt>' . ($quizData['reviewattempt'] ?? 69888) . '</reviewattempt>' . PHP_EOL;
        $xmlContent .= '    <reviewcorrectness>' . ($quizData['reviewcorrectness'] ?? 4352) . '</reviewcorrectness>' . PHP_EOL;
        $xmlContent .= '    <reviewmarks>' . ($quizData['reviewmarks'] ?? 4352) . '</reviewmarks>' . PHP_EOL;
        $xmlContent .= '    <reviewspecificfeedback>' . ($quizData['reviewspecificfeedback'] ?? 4352) . '</reviewspecificfeedback>' . PHP_EOL;
        $xmlContent .= '    <reviewgeneralfeedback>' . ($quizData['reviewgeneralfeedback'] ?? 4352) . '</reviewgeneralfeedback>' . PHP_EOL;
        $xmlContent .= '    <reviewrightanswer>' . ($quizData['reviewrightanswer'] ?? 4352) . '</reviewrightanswer>' . PHP_EOL;
        $xmlContent .= '    <reviewoverallfeedback>' . ($quizData['reviewoverallfeedback'] ?? 4352) . '</reviewoverallfeedback>' . PHP_EOL;

        // Navigation and presentation settings
        $xmlContent .= '    <questionsperpage>' . $quizData['questionsperpage'] . '</questionsperpage>' . PHP_EOL;
        $xmlContent .= '    <navmethod>' . htmlspecialchars($quizData['navmethod']) . '</navmethod>' . PHP_EOL;
        $xmlContent .= '    <shuffleanswers>' . $quizData['shuffleanswers'] . '</shuffleanswers>' . PHP_EOL;
        $xmlContent .= '    <sumgrades>' . $quizData['sumgrades'] . '</sumgrades>' . PHP_EOL;
        $xmlContent .= '    <grade>' . $quizData['grade'] . '</grade>' . PHP_EOL;

        // Timing and security
        $xmlContent .= '    <timecreated>' . ($quizData['timecreated'] ?? time()) . '</timecreated>' . PHP_EOL;
        $xmlContent .= '    <timemodified>' . ($quizData['timemodified'] ?? time()) . '</timemodified>' . PHP_EOL;
        $xmlContent .= '    <password>' . (isset($quizData['password']) ? htmlspecialchars($quizData['password']) : '') . '</password>' . PHP_EOL;
        $xmlContent .= '    <subnet>' . (isset($quizData['subnet']) ? htmlspecialchars($quizData['subnet']) : '') . '</subnet>' . PHP_EOL;
        $xmlContent .= '    <browsersecurity>' . (isset($quizData['browsersecurity']) ? htmlspecialchars($quizData['browsersecurity']) : '-') . '</browsersecurity>' . PHP_EOL;
        $xmlContent .= '    <delay1>' . ($quizData['delay1'] ?? 0) . '</delay1>' . PHP_EOL;
        $xmlContent .= '    <delay2>' . ($quizData['delay2'] ?? 0) . '</delay2>' . PHP_EOL;

        // Additional options
        $xmlContent .= '    <showuserpicture>' . ($quizData['showuserpicture'] ?? 0) . '</showuserpicture>' . PHP_EOL;
        $xmlContent .= '    <showblocks>' . ($quizData['showblocks'] ?? 0) . '</showblocks>' . PHP_EOL;
        $xmlContent .= '    <completionattemptsexhausted>' . ($quizData['completionattemptsexhausted'] ?? 0) . '</completionattemptsexhausted>' . PHP_EOL;
        $xmlContent .= '    <completionpass>' . ($quizData['completionpass'] ?? 0) . '</completionpass>' . PHP_EOL;
        $xmlContent .= '    <completionminattempts>' . ($quizData['completionminattempts'] ?? 0) . '</completionminattempts>' . PHP_EOL;
        $xmlContent .= '    <allowofflineattempts>' . ($quizData['allowofflineattempts'] ?? 0) . '</allowofflineattempts>' . PHP_EOL;

        // Subplugin, if applicable
        $xmlContent .= '    <subplugin_quizaccess_seb_quiz>' . PHP_EOL;
        $xmlContent .= '    </subplugin_quizaccess_seb_quiz>' . PHP_EOL;

        // Add question instances
        $xmlContent .= '    <question_instances>' . PHP_EOL;
        foreach ($quizData['questions'] as $question) {
            $xmlContent .= '      <question_instance id="' . $question['id'] . '">' . PHP_EOL;
            $xmlContent .= '        <slot>' . $question['id'] . '</slot>' . PHP_EOL;
            $xmlContent .= '        <page>1</page>' . PHP_EOL;
            $xmlContent .= '        <requireprevious>0</requireprevious>' . PHP_EOL;
            $xmlContent .= '        <questionid>' . $question['id'] . '</questionid>' . PHP_EOL;
            $xmlContent .= '        <questioncategoryid>' . $question['questioncategoryid'] . '</questioncategoryid>' . PHP_EOL;
            $xmlContent .= '        <includingsubcategories>$@NULL@$</includingsubcategories>' . PHP_EOL;
            $xmlContent .= '        <maxmark>' . $question['maxmark'] . '</maxmark>' . PHP_EOL;
            $xmlContent .= '      </question_instance>' . PHP_EOL;
        }
        $xmlContent .= '    </question_instances>' . PHP_EOL;

        // Quiz sections
        $xmlContent .= '    <sections>' . PHP_EOL;
        $xmlContent .= '      <section id="'.$quizData['id'].'">' . PHP_EOL;
        $xmlContent .= '        <firstslot>1</firstslot>' . PHP_EOL;
        $xmlContent .= '        <shufflequestions>0</shufflequestions>' . PHP_EOL;
        $xmlContent .= '      </section>' . PHP_EOL;
        $xmlContent .= '    </sections>' . PHP_EOL;

        // Add feedbacks
        $xmlContent .= '    <feedbacks>' . PHP_EOL;
        foreach ($quizData['feedbacks'] as $feedback) {
            $xmlContent .= '      <feedback id="'.$quizData['id'].'">' . PHP_EOL;
            $xmlContent .= '        <feedbacktext>' . htmlspecialchars($feedback['feedbacktext']) . '</feedbacktext>' . PHP_EOL;
            $xmlContent .= '        <feedbacktextformat>1</feedbacktextformat>' . PHP_EOL;
            $xmlContent .= '        <mingrade>' . $feedback['mingrade'] . '</mingrade>' . PHP_EOL;
            $xmlContent .= '        <maxgrade>' . $feedback['maxgrade'] . '</maxgrade>' . PHP_EOL;
            $xmlContent .= '      </feedback>' . PHP_EOL;
        }
        $xmlContent .= '    </feedbacks>' . PHP_EOL;

        // Complete with placeholders for attempts and grades
        $xmlContent .= '    <overrides>' . PHP_EOL . '    </overrides>' . PHP_EOL;
        $xmlContent .= '    <grades>' . PHP_EOL . '    </grades>' . PHP_EOL;
        $xmlContent .= '    <attempts>' . PHP_EOL . '    </attempts>' . PHP_EOL;

        // Close the activity tag
        $xmlContent .= '  </quiz>' . PHP_EOL;
        $xmlContent .= '</activity>' . PHP_EOL;

        // Save the XML file
        $xmlFile = $destinationDir . '/quiz.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingQuizXml'));
        }
    }

    /**
     * Creates the module.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param int    $sectionId       ID of the section related to the quiz.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createModuleXml($quizData, $destinationDir)
    {

        $section = new SectionExport($this->course);
        $learnpath = $section->getLearnpathById($quizData['sectionid']);
        $sectionData = $section->getSectionData($learnpath);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<module id="' . $quizData['moduleid'] . '" version="2021051700">' . PHP_EOL;
        $xmlContent .= '  <modulename>'.$quizData['modulename'].'</modulename>' . PHP_EOL;
        $xmlContent .= '  <sectionid>' . $quizData['sectionid'] . '</sectionid>' . PHP_EOL;
        $xmlContent .= '  <sectionnumber>'.$sectionData['number'] .'</sectionnumber>' . PHP_EOL;
        $xmlContent .= '  <idnumber></idnumber>' . PHP_EOL;
        $xmlContent .= '  <added>' . time() . '</added>' . PHP_EOL;
        $xmlContent .= '  <score>0</score>' . PHP_EOL;
        $xmlContent .= '  <indent>0</indent>' . PHP_EOL;
        $xmlContent .= '  <visible>1</visible>' . PHP_EOL;
        $xmlContent .= '  <visibleoncoursepage>1</visibleoncoursepage>' . PHP_EOL;
        $xmlContent .= '  <visibleold>1</visibleold>' . PHP_EOL;
        $xmlContent .= '  <groupmode>0</groupmode>' . PHP_EOL;
        $xmlContent .= '  <groupingid>0</groupingid>' . PHP_EOL;
        $xmlContent .= '  <completion>1</completion>' . PHP_EOL;
        $xmlContent .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>' . PHP_EOL;
        $xmlContent .= '  <completionview>0</completionview>' . PHP_EOL;
        $xmlContent .= '  <completionexpected>0</completionexpected>' . PHP_EOL;
        $xmlContent .= '  <availability>$@NULL@$</availability>' . PHP_EOL;
        $xmlContent .= '  <showdescription>0</showdescription>' . PHP_EOL;
        $xmlContent .= '  <tags></tags>' . PHP_EOL;
        $xmlContent .= '</module>' . PHP_EOL;

        $xmlFile = $destinationDir . '/module.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception('Error creating module.xml');
        }
    }

    /**
     * Creates the grades.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createGradesXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<activity_gradebook>' . PHP_EOL;
        $xmlContent .= '  <grade_items>' . PHP_EOL;
        $xmlContent .= '    <grade_item id="' . $quizData['id'] . '">' . PHP_EOL;
        $xmlContent .= '      <categoryid>1</categoryid>' . PHP_EOL;
        $xmlContent .= '      <itemname>' . htmlspecialchars($quizData['name']) . '</itemname>' . PHP_EOL;
        $xmlContent .= '      <itemtype>mod</itemtype>' . PHP_EOL;
        $xmlContent .= '      <itemmodule>quiz</itemmodule>' . PHP_EOL;
        $xmlContent .= '      <iteminstance>' . $quizData['id'] . '</iteminstance>' . PHP_EOL;
        $xmlContent .= '      <itemnumber>0</itemnumber>' . PHP_EOL;
        $xmlContent .= '      <iteminfo>$@NULL@$</iteminfo>' . PHP_EOL;
        $xmlContent .= '      <idnumber></idnumber>' . PHP_EOL;
        $xmlContent .= '      <calculation>$@NULL@$</calculation>' . PHP_EOL;
        $xmlContent .= '      <gradetype>1</gradetype>' . PHP_EOL;
        $xmlContent .= '      <grademax>' . $quizData['grade'] . '</grademax>' . PHP_EOL;
        $xmlContent .= '      <grademin>0.00000</grademin>' . PHP_EOL;
        $xmlContent .= '      <scaleid>$@NULL@$</scaleid>' . PHP_EOL;
        $xmlContent .= '      <outcomeid>$@NULL@$</outcomeid>' . PHP_EOL;
        $xmlContent .= '      <gradepass>0.00000</gradepass>' . PHP_EOL;
        $xmlContent .= '      <multfactor>1.00000</multfactor>' . PHP_EOL;
        $xmlContent .= '      <plusfactor>0.00000</plusfactor>' . PHP_EOL;
        $xmlContent .= '      <aggregationcoef>0.00000</aggregationcoef>' . PHP_EOL;
        $xmlContent .= '      <aggregationcoef2>0.09091</aggregationcoef2>' . PHP_EOL;
        $xmlContent .= '      <weightoverride>0</weightoverride>' . PHP_EOL;
        $xmlContent .= '      <sortorder>3</sortorder>' . PHP_EOL;
        $xmlContent .= '      <display>0</display>' . PHP_EOL;
        $xmlContent .= '      <decimals>$@NULL@$</decimals>' . PHP_EOL;
        $xmlContent .= '      <hidden>0</hidden>' . PHP_EOL;
        $xmlContent .= '      <locked>0</locked>' . PHP_EOL;
        $xmlContent .= '      <locktime>0</locktime>' . PHP_EOL;
        $xmlContent .= '      <needsupdate>0</needsupdate>' . PHP_EOL;
        $xmlContent .= '      <timecreated>' . time() . '</timecreated>' . PHP_EOL;
        $xmlContent .= '      <timemodified>' . time() . '</timemodified>' . PHP_EOL;
        $xmlContent .= '      <grade_grades>' . PHP_EOL;
        $xmlContent .= '      </grade_grades>' . PHP_EOL;
        $xmlContent .= '    </grade_item>' . PHP_EOL;
        $xmlContent .= '  </grade_items>' . PHP_EOL;
        $xmlContent .= '  <grade_letters>' . PHP_EOL;
        $xmlContent .= '  </grade_letters>' . PHP_EOL;
        $xmlContent .= '</activity_gradebook>' . PHP_EOL;

        $xmlFile = $destinationDir . '/grades.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingGradesXml'));
        }
    }

    /**
     * Creates the completion.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createCompletionXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<completion>' . PHP_EOL;
        $xmlContent .= '  <completiondata>' . PHP_EOL;
        $xmlContent .= '    <completion>' . PHP_EOL;
        $xmlContent .= '      <timecompleted>0</timecompleted>' . PHP_EOL;
        $xmlContent .= '      <completionstate>1</completionstate>' . PHP_EOL;
        $xmlContent .= '    </completion>' . PHP_EOL;
        $xmlContent .= '  </completiondata>' . PHP_EOL;
        $xmlContent .= '</completion>' . PHP_EOL;

        $xmlFile = $destinationDir . '/completion.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingCompletionXml'));
        }
    }

    /**
     * Creates the comments.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createCommentsXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<comments>' . PHP_EOL;
        $xmlContent .= '  <comment>' . PHP_EOL;
        $xmlContent .= '    <content>This is a sample comment</content>' . PHP_EOL;
        $xmlContent .= '    <author>Professor</author>' . PHP_EOL;
        $xmlContent .= '  </comment>' . PHP_EOL;
        $xmlContent .= '</comments>' . PHP_EOL;

        $xmlFile = $destinationDir . '/comments.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingCommentsXml'));
        }
    }

    /**
     * Creates the competencies.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createCompetenciesXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<competencies>' . PHP_EOL;
        $xmlContent .= '  <competency>' . PHP_EOL;
        $xmlContent .= '    <name>Sample Competency</name>' . PHP_EOL;
        $xmlContent .= '  </competency>' . PHP_EOL;
        $xmlContent .= '</competencies>' . PHP_EOL;

        $xmlFile = $destinationDir . '/competencies.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingCompetenciesXml'));
        }
    }

    /**
     * Creates the filters.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createFiltersXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<filters>' . PHP_EOL;
        $xmlContent .= '  <filter>' . PHP_EOL;
        $xmlContent .= '    <filtername>filter_example</filtername>' . PHP_EOL;
        $xmlContent .= '    <active>1</active>' . PHP_EOL;
        $xmlContent .= '  </filter>' . PHP_EOL;
        $xmlContent .= '</filters>' . PHP_EOL;

        $xmlFile = $destinationDir . '/filters.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingFiltersXml'));
        }
    }

    /**
     * Creates the grade_history.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createGradeHistoryXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<grade_history>' . PHP_EOL;
        $xmlContent .= '  <history>' . PHP_EOL;
        $xmlContent .= '    <grade>' . $quizData['grade'] . '</grade>' . PHP_EOL;
        $xmlContent .= '    <timestamp>' . time() . '</timestamp>' . PHP_EOL;
        $xmlContent .= '  </history>' . PHP_EOL;
        $xmlContent .= '</grade_history>' . PHP_EOL;

        $xmlFile = $destinationDir . '/grade_history.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingGradeHistoryXml'));
        }
    }

    /**
     * Creates the inforef.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createInforefXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<inforef>' . PHP_EOL;
        $xmlContent .= '  <file id="1">/path/to/file.pdf</file>' . PHP_EOL;
        $xmlContent .= '</inforef>' . PHP_EOL;

        $xmlFile = $destinationDir . '/inforef.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingInforefXml'));
        }
    }

    /**
     * Creates the roles.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createRolesXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<roles>' . PHP_EOL;
        $xmlContent .= '  <role>' . PHP_EOL;
        $xmlContent .= '    <name>Professor</name>' . PHP_EOL;
        $xmlContent .= '  </role>' . PHP_EOL;
        $xmlContent .= '</roles>' . PHP_EOL;

        $xmlFile = $destinationDir . '/roles.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingRolesXml'));
        }
    }

    /**
     * Creates the calendar.xml file.
     *
     * @param array  $quizData        Quiz data.
     * @param string $destinationDir  Directory where the XML will be saved.
     *
     * @return void
     */
    private function createCalendarXml($quizData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<calendar>' . PHP_EOL;
        $xmlContent .= '  <event>' . PHP_EOL;
        $xmlContent .= '    <name>Due Date</name>' . PHP_EOL;
        $xmlContent .= '    <timestart>' . time() . '</timestart>' . PHP_EOL;
        $xmlContent .= '  </event>' . PHP_EOL;
        $xmlContent .= '</calendar>' . PHP_EOL;

        $xmlFile = $destinationDir . '/calendar.xml';
        if (file_put_contents($xmlFile, $xmlContent) === false) {
            throw new Exception(get_lang('ErrorCreatingCalendarXml'));
        }
    }
}
