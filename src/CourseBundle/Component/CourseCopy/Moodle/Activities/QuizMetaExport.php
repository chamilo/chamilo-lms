<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

/**
 * Dumps raw quiz data (quiz, questions, answers) as JSON sidecar files under chamilo/quiz/.
 * No mapping, no transformation: we persist exactly what the builder already prepared
 * inside $this->course->resources for the selected quiz.
 */
class QuizMetaExport extends ActivityExport
{
    /**
     * Export JSON files for the given quiz:
     *   chamilo/quiz/quiz_{moduleId}/quiz.json
     *   chamilo/quiz/quiz_{moduleId}/questions.json
     *   chamilo/quiz/quiz_{moduleId}/answers.json
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        // Build destination folder
        $baseDir = rtrim($exportDir, '/').'/chamilo/quiz/quiz_'.$moduleId;
        $this->ensureDir($baseDir);

        // Resolve quiz bag (accept constant or string key)
        $quizBag =
            $this->course->resources[\defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz'] ??
            $this->course->resources['quiz'] ?? [];

        if (empty($quizBag[$activityId])) {
            @error_log('[QuizMetaExport] WARN quiz not found in resources: id='.$activityId);
            return;
        }

        // Unwrap quiz wrapper → raw payload already prepared by build_quizzes()
        $quizWrap = $quizBag[$activityId];
        $quizObj  = $this->unwrap($quizWrap);          // stdClass payload
        $quizArr  = $this->toArray($quizObj);          // array payload for JSON

        // Keep minimal context references (section/module) for traceability
        $quizArr['_context'] = [
            'module_id'  => (int) $moduleId,
            'section_id' => (int) $sectionId,
        ];

        // Persist quiz.json
        $this->writeJson($baseDir.'/quiz.json', ['quiz' => $quizArr]);

        // Collect questions for this quiz (preserve order if available)
        $questionIds = [];
        $orders      = [];

        if (isset($quizArr['question_ids']) && \is_array($quizArr['question_ids'])) {
            $questionIds = array_map('intval', $quizArr['question_ids']);
        }
        if (isset($quizArr['question_orders']) && \is_array($quizArr['question_orders'])) {
            $orders = array_map('intval', $quizArr['question_orders']);
        }

        $qBag =
            $this->course->resources[\defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : 'Exercise_Question']
            ?? $this->course->resources['Exercise_Question']
            ?? [];

        // Build ordered questions array (raw, with their nested answers)
        $questions = [];
        $answersFlat = [];
        $orderMap = [];

        // If we have question_orders aligned with question_ids, build an order map
        if (!empty($questionIds) && !empty($orders) && \count($questionIds) === \count($orders)) {
            foreach ($questionIds as $idx => $qid) {
                $orderMap[(int) $qid] = (int) $orders[$idx];
            }
        }

        foreach ($questionIds as $qid) {
            if (!isset($qBag[$qid])) {
                continue;
            }
            $qWrap = $qBag[$qid];
            $qObj  = $this->unwrap($qWrap);     // stdClass payload from build_quiz_questions()
            $qArr  = $this->toArray($qObj);

            // Attach quiz reference
            $qArr['_links']['quiz_id'] = (int) $activityId;

            // Optional: attach explicit question_id for clarity
            $qArr['id'] = $qArr['id'] ?? (int) ($qWrap->source_id ?? $qid);

            // Flatten answers to a standalone list (still keep them nested in question)
            $answers = [];
            if (isset($qArr['answers']) && \is_array($qArr['answers'])) {
                foreach ($qArr['answers'] as $ans) {
                    $answers[] = $ans;

                    $answersFlat[] = [
                        'quiz_id'     => (int) $activityId,
                        'question_id' => (int) $qArr['id'],
                        // Persist raw answer data verbatim
                        'data'        => $ans,
                    ];
                }
            }

            // Preserve original order if available; fallback to question "position"
            $qArr['_order'] = $orderMap[$qid] ?? (int) ($qArr['position'] ?? 0);
            $questions[] = $qArr;
        }

        // Sort questions by _order asc (stable)
        usort($questions, static function (array $a, array $b): int {
            return ($a['_order'] ?? 0) <=> ($b['_order'] ?? 0);
        });

        // Persist questions.json (full raw)
        $this->writeJson($baseDir.'/questions.json', ['questions' => $questions]);

        // Persist answers.json (flat list)
        $this->writeJson($baseDir.'/answers.json', ['answers' => $answersFlat]);
    }

    /** Ensure directory exists (recursive). */
    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, api_get_permissions_for_new_directories(), true)) {
            @error_log('[QuizMetaExport] ERROR mkdir failed: '.$dir);
        }
    }

    /** Write pretty JSON with utf8/slashes preserved. */
    private function writeJson(string $file, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (false === @file_put_contents($file, (string) $json)) {
            @error_log('[QuizMetaExport] ERROR writing file: '.$file);
        }
    }

    /**
     * Unwraps a legacy wrapper produced by mkLegacyItem() into its raw stdClass payload.
     * We prefer ->obj if present; otherwise return the object itself.
     */
    private function unwrap(object $wrap): object
    {
        // Some wrappers keep original payload at ->obj
        if (isset($wrap->obj) && \is_object($wrap->obj)) {
            return $wrap->obj;
        }
        return $wrap;
    }

    /** Deep convert stdClass/objects to arrays. */
    private function toArray($value)
    {
        if (\is_array($value)) {
            return array_map([$this, 'toArray'], $value);
        }
        if (\is_object($value)) {
            // Convert to array and recurse
            return array_map([$this, 'toArray'], get_object_vars($value));
        }
        return $value;
    }
}
