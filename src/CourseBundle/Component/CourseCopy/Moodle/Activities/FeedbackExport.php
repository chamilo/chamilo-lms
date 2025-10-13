<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const ENT_QUOTES;
use const ENT_XML1;
use const PHP_EOL;

/**
 * Exports Chamilo surveys into a single Moodle "feedback" activity.
 *
 * NOTE:
 * - Relies on $this->course structure injected via ActivityExport constructor.
 * - Expects a helper providing admin user data. Replace MoodleExport::getAdminUserData()
 *   with your actual provider or adapter if it lives elsewhere.
 */
class FeedbackExport extends ActivityExport
{
    /**
     * Export a survey to Moodle Feedback format.
     *
     * @param int    $activityId survey ID in Chamilo
     * @param string $exportDir  destination root for the export (course temp dir)
     * @param int    $moduleId   module ID for Moodle backup structure
     * @param int    $sectionId  target section ID within the course
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the activity folder: .../activities/feedback_<moduleId>
        $feedbackDir = $this->prepareActivityDirectory($exportDir, 'feedback', (int) $moduleId);

        // Gather normalized survey data from Chamilo resources
        $surveyData = $this->getData((int) $activityId, (int) $sectionId);

        // Produce all required XML artifacts for Moodle backup
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
     * Collect survey data, including questions and options, from Chamilo.
     */
    public function getData(int $surveyId, int $sectionId): array
    {
        // TODO: Replace this with your own provider if different:
        // e.g. $adminId = $this->adminProvider->getAdminUserId();
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) $adminData['id'];

        $survey = $this->course->resources['survey'][$surveyId] ?? null;

        $questions = [];
        foreach ($this->course->resources['survey_question'] ?? [] as $question) {
            if ((int) ($question->survey_id ?? 0) === $surveyId) {
                $questions[] = [
                    'id' => (int) $question->id,
                    'text' => (string) $question->survey_question,
                    'type' => (string) $question->survey_question_type,
                    'options' => array_map(
                        static fn ($answer) => $answer['option_text'],
                        (array) ($question->answers ?? [])
                    ),
                    'position' => (int) ($question->sort ?? 0),
                    'label' => '', // Keep empty unless you map labels explicitly
                ];
            }
        }

        return [
            'id' => $surveyId,
            'moduleid' => $surveyId,
            'modulename' => 'feedback',
            'contextid' => (int) $this->course->info['real_id'],
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'name' => (string) ($survey->title ?? ('Survey '.$surveyId)),
            'intro' => (string) ($survey->intro ?? ''),
            'timemodified' => time(),
            'questions' => $questions,
            'users' => [$adminId],
            'files' => [],
        ];
    }

    /**
     * Build feedback.xml (the core activity file for Moodle backup).
     */
    private function createFeedbackXml(array $surveyData, string $feedbackDir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$surveyData['id'].'" moduleid="'.$surveyData['moduleid'].'" modulename="feedback" contextid="'.$surveyData['contextid'].'">'.PHP_EOL;
        $xml .= '  <feedback id="'.$surveyData['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $surveyData['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro>'.htmlspecialchars((string) $surveyData['intro']).'</intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <anonymous>1</anonymous>'.PHP_EOL;
        $xml .= '    <email_notification>0</email_notification>'.PHP_EOL;
        $xml .= '    <multiple_submit>0</multiple_submit>'.PHP_EOL;
        $xml .= '    <autonumbering>1</autonumbering>'.PHP_EOL;
        $xml .= '    <site_after_submit></site_after_submit>'.PHP_EOL;
        $xml .= '    <page_after_submit></page_after_submit>'.PHP_EOL;
        $xml .= '    <page_after_submitformat>1</page_after_submitformat>'.PHP_EOL;
        $xml .= '    <publish_stats>0</publish_stats>'.PHP_EOL;
        $xml .= '    <timeopen>0</timeopen>'.PHP_EOL;
        $xml .= '    <timeclose>0</timeclose>'.PHP_EOL;
        $xml .= '    <timemodified>'.$surveyData['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '    <completionsubmit>0</completionsubmit>'.PHP_EOL;
        $xml .= '    <items>'.PHP_EOL;

        foreach ($surveyData['questions'] as $q) {
            $xml .= $this->createQuestionXml($q);
        }

        $xml .= '    </items>'.PHP_EOL;
        $xml .= '  </feedback>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('feedback', $xml, $feedbackDir);
    }

    /**
     * Render a single question in Moodle Feedback XML format.
     */
    private function createQuestionXml(array $question): string
    {
        $name = htmlspecialchars(strip_tags((string) ($question['text'] ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars(strip_tags((string) ($question['label'] ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $presentation = $this->getPresentation($question);
        $hasValue = (($question['type'] ?? '') === 'pagebreak') ? '0' : '1';
        $pos = (int) ($question['position'] ?? 0);
        $id = (int) ($question['id'] ?? 0);
        $typ = $this->mapQuestionType((string) ($question['type'] ?? ''));

        $xml = '      <item id="'.$id.'">'.PHP_EOL;
        $xml .= '        <template>0</template>'.PHP_EOL;
        $xml .= '        <name>'.$name.'</name>'.PHP_EOL;
        $xml .= '        <label>'.$label.'</label>'.PHP_EOL;
        $xml .= '        <presentation>'.$presentation.'</presentation>'.PHP_EOL;
        $xml .= '        <typ>'.$typ.'</typ>'.PHP_EOL;
        $xml .= '        <hasvalue>'.$hasValue.'</hasvalue>'.PHP_EOL;
        $xml .= '        <position>'.$pos.'</position>'.PHP_EOL;
        $xml .= '        <required>0</required>'.PHP_EOL;
        $xml .= '        <dependitem>0</dependitem>'.PHP_EOL;
        $xml .= '        <dependvalue></dependvalue>'.PHP_EOL;
        $xml .= '        <options>h</options>'.PHP_EOL;
        $xml .= '      </item>'.PHP_EOL;

        return $xml;
    }

    /**
     * Encode presentation string depending on question type.
     */
    private function getPresentation(array $question): string
    {
        $type = (string) ($question['type'] ?? '');
        $opts = array_map('strip_tags', (array) ($question['options'] ?? []));
        $opts = array_map(
            static fn ($o) => htmlspecialchars((string) $o, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            $opts
        );

        // Moodle feedback encodes the widget type as a single char:
        // r = radio, c = checkbox, d = dropdown, textareas use "<cols>|<rows>"
        return match ($type) {
            'yesno', 'multiplechoice', 'multiplechoiceother' => 'r&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $opts),
            'multipleresponse' => 'c&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $opts),
            'dropdown' => 'd&gt;&gt;&gt;&gt;&gt;'.implode(PHP_EOL.'|', $opts),
            'open' => '30|5', // textarea: cols|rows
            default => '',
        };
    }

    /**
     * Map Chamilo survey question types to Moodle feedback types.
     */
    private function mapQuestionType(string $chamiloType): string
    {
        return [
            'yesno' => 'multichoice',
            'multiplechoice' => 'multichoice',
            'multipleresponse' => 'multichoice',
            'dropdown' => 'multichoice',
            'multiplechoiceother' => 'multichoice',
            'open' => 'textarea',
            'pagebreak' => 'pagebreak',
        ][$chamiloType] ?? 'unknown';
    }
}
