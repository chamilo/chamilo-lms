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
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) $adminData['id'];

        $survey = $this->course->resources['survey'][$surveyId] ?? null;

        $questions = [];
        foreach ($this->course->resources['survey_question'] ?? [] as $q) {
            if ((int) ($q->survey_id ?? $q->obj->survey_id ?? 0) !== $surveyId) {
                continue;
            }

            $qo = (isset($q->obj) && is_object($q->obj)) ? $q->obj : $q;

            $rawType = (string)($qo->type ?? $qo->survey_question_type ?? '');
            $normalizedType = $this->normalizeChamiloType($rawType);

            $options = $this->normalizeOptions($qo, $normalizedType);

            $text = (string)($qo->survey_question ?? $q->survey_question ?? 'Question');
            $text = trim($text);

            $questions[] = [
                'id'       => (int) ($qo->id ?? $q->id ?? 0),
                'text'     => $text,
                'type'     => $normalizedType,
                'options'  => $options,
                'position' => (int) ($qo->sort ?? $q->sort ?? 0),
                'label'    => '',
            ];
        }

        return [
            'id'            => $surveyId,
            'moduleid'      => $surveyId,
            'modulename'    => 'feedback',
            'type'          => 'mod',
            'contextid'     => (int) $this->course->info['real_id'],
            'sectionid'     => $sectionId,
            'sectionnumber' => 0,
            'name'          => (string) ($survey->title ?? ('Survey '.$surveyId)),
            'intro'         => (string) ($survey->intro ?? ''),
            'timemodified'  => time(),
            'questions'     => $questions,
            'users'         => [$adminId],
            'files'         => [],
        ];
    }

    /** Converts types used multiple times in Chamilo to the 6–7 we export to Moodle. */
    private function normalizeChamiloType(string $t): string
    {
        $t = strtolower(trim($t));
        return match ($t) {
            'yes_no', 'yesno'                         => 'yesno',
            'multiple_single', 'single', 'radio',
            'multiplechoice'                          => 'multiplechoice',
            'multiple_multiple', 'multiple', 'checks',
            'multipleresponse'                        => 'multipleresponse',
            'multiple_dropdown', 'dropdown', 'select' => 'dropdown',
            'multiplechoiceother'                     => 'multiplechoiceother',
            'open_short', 'short', 'textfield'        => 'textfield',
            'open_long', 'open', 'textarea', 'comment'=> 'open',
            'pagebreak'                               => 'pagebreak',
            'numeric', 'number', 'score'              => 'numeric',
            'percentage'                              => 'percentage',
            default                                   => 'open',
        };
    }

    /** Extract options without breaking if the shape changes (array of objects, string “A|B”, etc.) */
    private function normalizeOptions(object $qo, string $normalizedType): array
    {
        if ($normalizedType === 'yesno') {
            if (!empty($qo->answers)) {
                $out = [];
                foreach ($qo->answers as $a) {
                    $txt = is_array($a) ? ($a['option_text'] ?? '') : (is_object($a) ? ($a->option_text ?? '') : (string)$a);
                    $txt = trim((string)$txt);
                    if ($txt !== '') { $out[] = $txt; }
                }
                if ($out) { return $out; }
            }
            return ['Yes','No'];
        }

        if (!empty($qo->answers) && is_iterable($qo->answers)) {
            $out = [];
            foreach ($qo->answers as $a) {
                $txt = is_array($a) ? ($a['option_text'] ?? '') : (is_object($a) ? ($a->option_text ?? '') : (string)$a);
                $txt = trim((string)$txt);
                if ($txt !== '') { $out[] = $txt; }
            }
            if ($out) { return $out; }
        }

        if (!empty($qo->options) && is_string($qo->options)) {
            $out = array_values(array_filter(array_map('trim', explode('|', $qo->options)), 'strlen'));
            if ($out) { return $out; }
        }

        if ($normalizedType === 'percentage') {
            return range(1, 100);
        }

        return [];
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

    private function createQuestionXml(array $q): string
    {
        $name  = htmlspecialchars(strip_tags((string) ($q['text'] ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars(strip_tags((string) ($q['label'] ?? '')), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $typ  = $this->mapQuestionType((string) ($q['type'] ?? ''));
        $pres = $this->getPresentation($q);

        $hasValue = in_array($typ, ['multichoice','textarea','textfield','numeric'], true) ? '1' : '0';

        $pos = (int) ($q['position'] ?? 0);
        $id  = (int) ($q['id'] ?? 0);

        $xml  = '      <item id="'.$id.'">'.PHP_EOL;
        $xml .= '        <template>0</template>'.PHP_EOL;
        $xml .= '        <name>'.$name.'</name>'.PHP_EOL;
        $xml .= '        <label>'.$label.'</label>'.PHP_EOL;
        $xml .= '        <presentation>'.$pres.'</presentation>'.PHP_EOL;
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

    private function getPresentation(array $q): string
    {
        $type = (string)($q['type'] ?? '');
        $opts = array_map(static fn($o) => htmlspecialchars((string)trim($o), ENT_XML1 | ENT_QUOTES, 'UTF-8'), (array)($q['options'] ?? []));
        $joined = implode('|', $opts);

        return match ($type) {
            'yesno', 'multiplechoice'                 => 'r&gt;&gt;&gt;&gt;&gt;'.$joined,
            'multiplechoiceother'                     => 'r&gt;&gt;&gt;&gt;&gt;'.$joined,
            'multipleresponse'                        => 'c&gt;&gt;&gt;&gt;&gt;'.$joined,
            'dropdown'                                => 'd&gt;&gt;&gt;&gt;&gt;'.$joined,
            'percentage'                              => 'r&gt;&gt;&gt;&gt;&gt;'.$joined,
            'textfield'                               => '30',
            'open'                                    => '30|5',
            'numeric'                                 => '',
            default                                   => '',
        };
    }

    private function mapQuestionType(string $chType): string
    {
        return match ($chType) {
            'yesno', 'multiplechoice', 'multiplechoiceother', 'percentage' => 'multichoice',
            'multipleresponse'                                             => 'multichoice',
            'dropdown'                                                     => 'multichoice',
            'textfield'                                                    => 'textfield',
            'open', 'comment'                                              => 'textarea',
            'numeric', 'score'                                             => 'numeric',
            'pagebreak'                                                    => 'pagebreak',
            'label'                                                        => 'label',
            default                                                        => 'textarea',
        };
    }
}
