<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class FeedbackExport.
 *
 * Handles the export of surveys from Chamilo to Moodle Feedback.
 */
class FeedbackExport extends ActivityExport
{
    /**
     * Export a survey to Moodle Feedback format.
     *
     * @param int    $activityId The ID of the survey.
     * @param string $exportDir  The directory where the feedback will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory for export
        $feedbackDir = $this->prepareActivityDirectory($exportDir, 'feedback', $moduleId);

        // Get survey data from Chamilo
        $surveyData = $this->getData($activityId, $sectionId);

        // Create XML files for the survey
        $this->createFeedbackXml($surveyData, $feedbackDir);
        $this->createModuleXml($surveyData, $feedbackDir);
        $this->createInforefXml($surveyData, $feedbackDir);
        $this->createCalendarXml($surveyData, $feedbackDir);
        $this->createCommentsXml($surveyData, $feedbackDir);
        $this->createCompetenciesXml($surveyData, $feedbackDir);
        $this->createCompletionXml($surveyData, $feedbackDir);
        $this->createFiltersXml($surveyData, $feedbackDir);
        $this->createGradeHistoryXml($surveyData, $feedbackDir);
        $this->createGradesXml($surveyData, $feedbackDir);
        $this->createRolesXml($surveyData, $feedbackDir);
    }

    /**
     * Get survey data including questions and answers from Chamilo.
     */
    public function getData(int $surveyId, int $sectionId): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];

        $survey = $this->course->resources['survey'][$surveyId];
        $questions = [];
        foreach ($this->course->resources['survey_question'] as $question) {
            if ((int) $question->survey_id === $surveyId) {
                // Debugging
                $questions[] = [
                    'id' => $question->id,
                    'text' => $question->survey_question,
                    'type' => $question->survey_question_type,
                    'options' => array_map(function ($answer) {
                        return $answer['option_text'];
                    }, $question->answers),
                    'position' => $question->sort,
                    'label' => '', // Default empty label
                ];
            }
        }

        return [
            'id' => $surveyId,
            'moduleid' => $surveyId,
            'modulename' => 'feedback',
            'contextid' => $this->course->info['real_id'],
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'name' => $survey->title,
            'intro' => $survey->intro,
            'timemodified' => time(),
            'questions' => $questions,
            'users' => [$adminId],
            'files' => [],
        ];
    }

    /**
     * Create the feedback.xml file for the Moodle feedback activity.
     */
    private function createFeedbackXml(array $surveyData, string $feedbackDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$surveyData['id'].'" moduleid="'.$surveyData['moduleid'].'" modulename="feedback" contextid="'.$surveyData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <feedback id="'.$surveyData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($surveyData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars($surveyData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <anonymous>1</anonymous>'.PHP_EOL;
        $xmlContent .= '    <email_notification>0</email_notification>'.PHP_EOL;
        $xmlContent .= '    <multiple_submit>0</multiple_submit>'.PHP_EOL;
        $xmlContent .= '    <autonumbering>1</autonumbering>'.PHP_EOL;
        $xmlContent .= '    <site_after_submit></site_after_submit>'.PHP_EOL;
        $xmlContent .= '    <page_after_submit></page_after_submit>'.PHP_EOL;
        $xmlContent .= '    <page_after_submitformat>1</page_after_submitformat>'.PHP_EOL;
        $xmlContent .= '    <publish_stats>0</publish_stats>'.PHP_EOL;
        $xmlContent .= '    <timeopen>0</timeopen>'.PHP_EOL;
        $xmlContent .= '    <timeclose>0</timeclose>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$surveyData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <completionsubmit>0</completionsubmit>'.PHP_EOL;
        $xmlContent .= '    <items>'.PHP_EOL;

        // Map Chamilo questions to Moodle Feedback format
        foreach ($surveyData['questions'] as $question) {
            $xmlContent .= $this->createQuestionXml($question);
        }

        $xmlContent .= '    </items>'.PHP_EOL;
        $xmlContent .= '  </feedback>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('feedback', $xmlContent, $feedbackDir);
    }

    /**
     * Create the XML for a single question in Moodle Feedback format.
     */
    private function createQuestionXml(array $question): string
    {
        $name = htmlspecialchars(strip_tags($question['text']), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars(strip_tags($question['label']), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $presentation = $this->getPresentation($question);
        $hasValue = ($question['type'] === 'pagebreak') ? '0' : '1';

        $xmlContent = '<item id="'.$question['id'].'">'.PHP_EOL;
        $xmlContent .= '  <template>0</template>'.PHP_EOL;
        $xmlContent .= '  <name>'.$name.'</name>'.PHP_EOL;
        $xmlContent .= '  <label>'.$label.'</label>'.PHP_EOL;
        $xmlContent .= '  <presentation>'.$presentation.'</presentation>'.PHP_EOL;
        $xmlContent .= '  <typ>'.$this->mapQuestionType($question['type']).'</typ>'.PHP_EOL;
        $xmlContent .= '  <hasvalue>'.$hasValue.'</hasvalue>'.PHP_EOL;
        $xmlContent .= '  <position>'.$question['position'].'</position>'.PHP_EOL;
        $xmlContent .= '  <required>0</required>'.PHP_EOL;
        $xmlContent .= '  <dependitem>0</dependitem>'.PHP_EOL;
        $xmlContent .= '  <dependvalue></dependvalue>'.PHP_EOL;
        $xmlContent .= '  <options>h</options>'.PHP_EOL;
        $xmlContent .= '</item>'.PHP_EOL;

        return $xmlContent;
    }

    /**
     * Get presentation for different question types.
     */
    private function getPresentation(array $question): string
    {
        $options = array_map('strip_tags', $question['options']);
        $sanitizedOptions = array_map(function ($option) {
            return htmlspecialchars($option, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }, $options);

        switch ($question['type']) {
            case 'yesno':
            case 'multiplechoice':
            case 'multiplechoiceother':
                return 'r&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $sanitizedOptions);
            case 'multipleresponse':
                return 'c&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $sanitizedOptions);
            case 'dropdown':
                return 'd&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $sanitizedOptions);
            case 'open':
                return '30|5'; // Textarea with rows and cols
            default:
                return '';
        }
    }

    /**
     * Map Chamilo question type to Moodle Feedback type.
     */
    private function mapQuestionType(string $chamiloType): string
    {
        $typeMap = [
            'yesno' => 'multichoice',
            'multiplechoice' => 'multichoice',
            'multipleresponse' => 'multichoice',
            'dropdown' => 'multichoice',
            'multiplechoiceother' => 'multichoice',
            'open' => 'textarea',
            'pagebreak' => 'pagebreak',
        ];

        return $typeMap[$chamiloType] ?? 'unknown';
    }
}
