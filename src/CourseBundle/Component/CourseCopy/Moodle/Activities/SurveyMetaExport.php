<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

/**
 * Preserve Chamilo survey data losslessly alongside Moodle feedback.xml.
 *
 * Moodle's feedback item <name> is plain text, so translate_html markup in
 * Chamilo survey questions cannot be represented there without changing the
 * standard Moodle format. The JSON sidecar is ignored by Moodle and is used
 * only by Chamilo when restoring its own backups.
 */
class SurveyMetaExport extends ActivityExport
{
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $baseDir = rtrim($exportDir, '/').'/chamilo/survey/survey_'.$moduleId;
        $this->ensureDir($baseDir);

        $surveyBag =
            $this->course->resources[\defined('RESOURCE_SURVEY') ? RESOURCE_SURVEY : 'survey']
            ?? $this->course->resources['survey']
            ?? $this->course->resources['surveys']
            ?? [];

        $surveyWrap = $surveyBag[$activityId] ?? null;
        if (!\is_object($surveyWrap)) {
            @error_log('[SurveyMetaExport] WARN survey not found in resources: id='.$activityId);
            return;
        }

        $surveyArr = $this->toArray($this->unwrap($surveyWrap));
        $surveyArr['id'] = (int) ($surveyArr['id'] ?? $surveyWrap->source_id ?? $activityId);
        $surveyArr['survey_id'] = (int) ($surveyArr['survey_id'] ?? $surveyArr['id'] ?? $activityId);
        $surveyArr['_context'] = [
            'module_id' => $moduleId,
            'section_id' => $sectionId,
        ];

        $questionBag =
            $this->course->resources[\defined('RESOURCE_SURVEYQUESTION') ? RESOURCE_SURVEYQUESTION : 'survey_question']
            ?? $this->course->resources['survey_question']
            ?? $this->course->resources['survey_questions']
            ?? [];

        $questionIds = array_map('intval', (array) ($surveyArr['question_ids'] ?? []));
        if (empty($questionIds)) {
            foreach ($questionBag as $questionId => $questionWrap) {
                if (!\is_object($questionWrap)) {
                    continue;
                }

                $questionObj = $this->unwrap($questionWrap);
                if ((int) ($questionObj->survey_id ?? 0) === $activityId) {
                    $questionIds[] = (int) $questionId;
                }
            }
        }

        $questions = [];
        foreach ($questionIds as $questionId) {
            $questionWrap = $questionBag[$questionId] ?? null;
            if (!\is_object($questionWrap)) {
                continue;
            }

            $questionArr = $this->toArray($this->unwrap($questionWrap));
            $questionArr['id'] = (int) ($questionArr['id'] ?? $questionWrap->source_id ?? $questionId);
            $questionArr['survey_id'] = $activityId;
            $questions[] = $questionArr;
        }

        $this->writeJson($baseDir.'/survey.json', ['survey' => $surveyArr]);
        $this->writeJson($baseDir.'/questions.json', ['questions' => $questions]);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, api_get_permissions_for_new_directories(), true) && !is_dir($dir)) {
            @error_log('[SurveyMetaExport] ERROR mkdir failed: '.$dir);
        }
    }

    private function writeJson(string $file, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if (false === @file_put_contents($file, (string) $json)) {
            @error_log('[SurveyMetaExport] ERROR writing file: '.$file);
        }
    }

    private function unwrap(object $wrap): object
    {
        if (isset($wrap->obj) && \is_object($wrap->obj)) {
            return $wrap->obj;
        }

        return $wrap;
    }

    private function toArray(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map([$this, 'toArray'], $value);
        }

        if (\is_object($value)) {
            return array_map([$this, 'toArray'], get_object_vars($value));
        }

        return $value;
    }
}
