<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;

/**
 * Builds the exact privacy-filtered payload that will later be sent to the AI
 * provider. It never includes the local Chamilo user identifier.
 */
final readonly class StudentSuccessPayloadBuilder
{
    public function __construct(
        private StudentLearningActivityCollector $activityCollector,
        private StudentLearningAnonymizer $anonymizer,
        private StudentSuccessAnalysisStorage $analysisStorage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Course $course,
        ?Session $session,
        User $student,
        string $teacherPrompt = '',
    ): array {
        $activity = $this->activityCollector->collect($course, $session, $student);
        $activityResult = $this->anonymizer->sanitize($activity, $student);
        $promptResult = $this->anonymizer->sanitizeTeacherPrompt($teacherPrompt, $student);

        $courseAnalysisContext = $this->analysisStorage->getCourseAnalysis($course, $session);
        $courseAnalysis = \is_array($courseAnalysisContext['analysis'] ?? null)
            ? $courseAnalysisContext['analysis']
            : null;
        $courseAnalysisRedactions = 0;
        if (\is_array($courseAnalysis)) {
            $courseAnalysisResult = $this->anonymizer->sanitize($courseAnalysis, $student);
            $courseAnalysis = $courseAnalysisResult['data'];
            $courseAnalysisRedactions = $courseAnalysisResult['redactions'];
        }

        $sanitizedActivity = $activityResult['data'];
        if (isset($sanitizedActivity['privacy']) && \is_array($sanitizedActivity['privacy'])) {
            $sanitizedActivity['privacy']['freeTextRequiresSanitizationBeforeAi'] = false;
            $sanitizedActivity['privacy']['freeTextSanitized'] = true;
        }

        return [
            'version' => 1,
            'courseAnalysis' => [
                'available' => \is_array($courseAnalysis) && [] !== $courseAnalysis,
                'analysis' => $courseAnalysis,
            ],
            'studentActivity' => $sanitizedActivity,
            'teacherPrompt' => $promptResult['text'],
            'privacy' => [
                'localUserIdIncluded' => false,
                'directProfileIdentifiersIncluded' => false,
                'assignmentSubmissionContentIncluded' => false,
                'freeTextSanitized' => true,
                'redactionsApplied' => $activityResult['redactions']
                    + $promptResult['redactions']
                    + $courseAnalysisRedactions,
            ],
        ];
    }
}
