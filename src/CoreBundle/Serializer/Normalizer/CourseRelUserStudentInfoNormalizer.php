<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer\Normalizer;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Helpers\CourseStudentInfoHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CourseRelUserStudentInfoNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * Prevent infinite recursion when delegating to the serializer.
     */
    private const ALREADY_CALLED = 'chamilo_course_rel_user_student_info_already_called';

    /**
     * Hard limit to avoid flooding logs when serializing many edges.
     */
    private const MAX_LOG_LINES_PER_REQUEST = 40;

    /**
     * Per-request counter stored in context to limit logs.
     */
    private const LOG_COUNTER_KEY = '_chamilo_cru_student_info_log_counter';

    public function __construct(
        private readonly CourseStudentInfoHelper $studentInfoHelper,
        private readonly Security $security,
    ) {}

    /**
     * IMPORTANT:
     * This normalizer support depends on the context (ALREADY_CALLED / groups / api_sub_level),
     * so it MUST NOT be cacheable.
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            CourseRelUser::class => false, // do NOT cache supportsNormalization result
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!$data instanceof CourseRelUser) {
            return false;
        }

        if (!empty($context[self::ALREADY_CALLED])) {
            $this->log($context, 'supportsNormalization: skipped (already called)', [
                'format' => $format,
                'api_sub_level' => $context['api_sub_level'] ?? null,
            ]);

            return false;
        }

        // In GraphQL, api_sub_level might be 1 even for top-level nodes.
        // We only skip deeper nesting to avoid memory/perf issues.
        $subLevel = (int) ($context['api_sub_level'] ?? 0);
        if ($subLevel >= 2) {
            $this->log($context, 'supportsNormalization: skipped (api_sub_level >= 2)', [
                'format' => $format,
                'api_sub_level' => $subLevel,
            ]);

            return false;
        }

        // Groups can vary depending on the API layer.
        $groups = $context['groups'] ?? [];
        $groups = \is_array($groups) ? $groups : [$groups];

        $allowed = \in_array('course_rel_user:read', $groups, true);

        $this->log($context, 'supportsNormalization: '.($allowed ? 'YES' : 'NO (groups mismatch)'), [
            'format' => $format,
            'api_sub_level' => $subLevel,
            'groups' => $groups,
        ]);

        return $allowed;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var CourseRelUser $object */
        $context[self::ALREADY_CALLED] = true;

        $cruId = (int) ($object->getId() ?? 0);
        $status = $object->getStatus();
        $userId = (int) ($object->getUser()?->getId() ?? 0);
        $courseId = (int) ($object->getCourse()?->getId() ?? 0);
        $currentUserId = $this->getCurrentUserId();

        $this->log($context, 'normalize: called', [
            'cru_id' => $cruId,
            'status' => $status,
            'user_id' => $userId,
            'course_id' => $courseId,
            'current_user_id' => $currentUserId,
            'api_sub_level' => $context['api_sub_level'] ?? null,
            'format' => $format,
        ]);

        // Compute ONLY for the currently logged-in user to avoid heavy computations when serializing lists.
        // This also fixes the "status != STUDENT" case (e.g. teachers/coaches can still have tracking data).
        $isSelf = ($currentUserId > 0 && $userId > 0 && $userId === $currentUserId);
        $shouldCompute = $isSelf || ($currentUserId === 0 && $status === CourseRelUser::STUDENT);

        if ($shouldCompute) {
            if ($userId > 0 && $courseId > 0) {
                $sessionId = 0;

                // Allow passing sessionId explicitly from context when needed.
                if (isset($context['sessionId'])) {
                    $sessionId = (int) $context['sessionId'];
                }

                try {
                    $this->log($context, 'normalize: computing student info', [
                        'cru_id' => $cruId,
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'session_id' => $sessionId,
                        'mode' => $isSelf ? 'self' : 'student_fallback',
                    ]);

                    $info = $this->studentInfoHelper->getStudentInfo($userId, $courseId, $sessionId);

                    // Avoid logging huge payloads; log only a summary.
                    $this->log($context, 'normalize: student info computed', [
                        'cru_id' => $cruId,
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'session_id' => $sessionId,
                        'keys' => \is_array($info) ? array_keys($info) : [],
                        'progress' => $info['progress'] ?? null,
                        'certificateAvailable' => $info['certificateAvailable'] ?? null,
                        'completed' => $info['completed'] ?? null,
                    ]);

                    // Apply computed values into transient fields (no DB columns).
                    if (\method_exists($object, 'applyStudentInfo')) {
                        $object->applyStudentInfo($info);
                    } else {
                        $this->applyStudentInfoFallback($object, $info);
                    }
                } catch (\Throwable $e) {
                    $this->log($context, 'normalize: student info computation failed', [
                        'cru_id' => $cruId,
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'session_id' => $sessionId,
                        'exception' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->log($context, 'normalize: skipped (missing userId/courseId)', [
                    'cru_id' => $cruId,
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ]);
            }
        } else {
            $this->log($context, 'normalize: skipped (not self)', [
                'cru_id' => $cruId,
                'status' => $status,
                'user_id' => $userId,
                'current_user_id' => $currentUserId,
            ]);
        }

        // Delegate to serializer chain safely.
        $data = $this->normalizer->normalize($object, $format, $context);

        $this->log($context, 'normalize: done', [
            'cru_id' => $cruId,
        ]);

        return \is_array($data) ? $data : (array) $data;
    }

    private function applyStudentInfoFallback(CourseRelUser $object, array $info): void
    {
        // Expected keys:
        // progress, score, bestScore, timeSpentSeconds, certificateAvailable, completed, hasNewContent

        $progress = isset($info['progress']) && \is_numeric($info['progress']) ? (float) $info['progress'] : 0.0;

        if (\method_exists($object, 'setTrackingProgress')) {
            $object->setTrackingProgress($progress);
        }
        if (\method_exists($object, 'setScore')) {
            $object->setScore(isset($info['score']) && \is_numeric($info['score']) ? (float) $info['score'] : null);
        }
        if (\method_exists($object, 'setBestScore')) {
            $object->setBestScore(isset($info['bestScore']) && \is_numeric($info['bestScore']) ? (float) $info['bestScore'] : null);
        }
        if (\method_exists($object, 'setTimeSpentSeconds')) {
            $object->setTimeSpentSeconds(isset($info['timeSpentSeconds']) && \is_numeric($info['timeSpentSeconds']) ? (int) $info['timeSpentSeconds'] : null);
        }
        if (\method_exists($object, 'setCertificateAvailable')) {
            $object->setCertificateAvailable((bool) ($info['certificateAvailable'] ?? false));
        }
        if (\method_exists($object, 'setCompleted')) {
            $object->setCompleted((bool) ($info['completed'] ?? ($progress >= 100.0)));
        }
        if (\method_exists($object, 'setHasNewContent')) {
            $object->setHasNewContent((bool) ($info['hasNewContent'] ?? false));
        }
    }

    private function getCurrentUserId(): int
    {
        try {
            $user = $this->security->getUser();
            if (null === $user || !\method_exists($user, 'getId')) {
                return 0;
            }

            return (int) ($user->getId() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function log(array &$context, string $message, array $extra = []): void
    {
        $counter = (int) ($context[self::LOG_COUNTER_KEY] ?? 0);
        if ($counter >= self::MAX_LOG_LINES_PER_REQUEST) {
            return;
        }

        $context[self::LOG_COUNTER_KEY] = $counter + 1;

        // Write directly to PHP error log for immediate visibility.
        @error_log('[CRUStudentInfo] '.$message.' '.json_encode($extra));
    }
}
