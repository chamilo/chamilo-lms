<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Gradebook;

use Category;
use Chamilo\CoreBundle\Component\LegacyCliBootstrapper;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container as LegacyContainer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

use const STUDENT;

/**
 * Shared, batch-safe building blocks for generating achievement certificates
 * from a gradebook category — used by both chamilo:migration:process-
 * achievement-certificates (one course at a time) and chamilo:gradebook:
 * process-achievement-certificates (every eligible course, base or inside a
 * session, in one platform-wide run).
 *
 * Kept here rather than autowired: CoreBundle Component classes are excluded
 * from service auto-discovery (see config/services.yaml), matching
 * CourseCompletionRuleEvaluator, which callers instantiate directly too.
 */
final class AchievementCertificateBatchService
{
    private const CERTIFICATE_SUBJECT_FIELD =
        'plugin_gradingelectronic_certificate_notification_subject';
    private const CERTIFICATE_MESSAGE_FIELD =
        'plugin_gradingelectronic_certificate_notification_message';

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseCompletionRuleEvaluator $evaluator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function bootstrapLegacy(KernelInterface $kernel): void
    {
        // Re-anchors the legacy Container/Database static bridges back to this
        // command's own container/EntityManager — see LegacyCliBootstrapper for why.
        LegacyCliBootstrapper::bootstrap($kernel, $this->entityManager);
        $this->warmAccessUrlColorThemes();
    }

    /**
     * Works around a cross-EntityManager Doctrine error seen only in CLI:
     * ResourceListener::prePersist() (see AccessUrlListenerTrait) attaches the
     * current AccessUrl to every newly-created resource, including the
     * certificate itself. When that AccessUrl's ColorTheme relations were
     * never loaded through the exact EntityManager instance legacy code
     * resolves via Container::$container (a side effect of global.inc.php's
     * CLI branch booting its own second kernel — see bootstrapLegacy() above),
     * Doctrine's flush finds an unmanaged ColorTheme proxy through
     * AccessUrlRelColorTheme#colorTheme, which isn't configured to cascade
     * persist, and the whole certificate HTML upsert fails, falling back to a
     * bare stub file (see certificate viewing returning 404).
     *
     * Force-loading every ColorTheme relation up front, through that same
     * EntityManager, puts them in its identity map as already-managed before
     * generation runs, so the flush no longer treats them as new. Best-effort
     * only: never let a failure here block certificate generation itself.
     */
    private function warmAccessUrlColorThemes(): void
    {
        try {
            // Must match exactly how the failing code path resolves its EntityManager:
            // Container::getGradeBookCertificateRepository() gets it from ManagerRegistry
            // (Doctrine's ServiceEntityRepository base class does this internally), not
            // from the 'doctrine.orm.default_entity_manager' service id directly — the
            // two can land on different EntityManager instances in this CLI context (see
            // bootstrapLegacy() above).
            $registry = LegacyContainer::$container?->get('doctrine');
            if (!$registry instanceof ManagerRegistry) {
                return;
            }

            $legacyEntityManager = $registry->getManagerForClass(GradebookCertificate::class);
            if (!$legacyEntityManager instanceof EntityManagerInterface) {
                return;
            }

            $accessUrls = $legacyEntityManager->getRepository(AccessUrl::class)->findAll();
            foreach ($accessUrls as $accessUrl) {
                foreach ($accessUrl->getColorThemes() as $relation) {
                    $relation->getColorTheme()?->getId();
                }
            }
        } catch (Throwable) {
            // Best-effort warm-up; certificate generation still proceeds without it.
        }
    }

    public function supports(int $courseId): bool
    {
        return $this->evaluator->supports($courseId);
    }

    /**
     * Validates the sender, then authenticates it as the current user for the
     * rest of the run. Console commands start with no Security token, but
     * creating a certificate persists a new AbstractResource, and
     * ResourceListener requires a creator — falling back to
     * Security::getUser() exactly like the manual "Generate" button implicitly
     * relies on the logged-in admin who clicked it. The validated sender is
     * the natural stand-in for that admin here.
     */
    public function assertValidSender(int $senderId): void
    {
        LegacyCliBootstrapper::authenticateSender($senderId, $this->entityManager, $this->tokenStorage);
    }

    /**
     * @return array{subject?: string, message?: string}
     */
    public function resolveNotification(int $courseId): array
    {
        $fieldIds = [];

        foreach (
            [
                'subject' => self::CERTIFICATE_SUBJECT_FIELD,
                'message' => self::CERTIFICATE_MESSAGE_FIELD,
            ] as $key => $variable
        ) {
            $fieldId = $this->connection->fetchOne(
                'SELECT id
                 FROM extra_field
                 WHERE item_type = :itemType
                   AND variable = :variable
                 LIMIT 1',
                [
                    'itemType' => ExtraField::COURSE_FIELD_TYPE,
                    'variable' => $variable,
                ]
            );

            if (false === $fieldId || (int) $fieldId <= 0) {
                return [];
            }

            $fieldIds[$key] = (int) $fieldId;
        }

        $notification = [];

        foreach ($fieldIds as $key => $fieldId) {
            $rows = $this->connection->fetchFirstColumn(
                'SELECT field_value
                 FROM extra_field_values
                 WHERE field_id = :fieldId
                   AND item_id = :courseId
                 ORDER BY id',
                [
                    'fieldId' => $fieldId,
                    'courseId' => $courseId,
                ]
            );

            if (\count($rows) > 1) {
                return [];
            }

            $notification[$key] = trim((string) ($rows[0] ?? ''));
        }

        if ('' === $notification['subject'] && '' === $notification['message']) {
            return [];
        }

        return $notification;
    }

    /**
     * Root gradebook categories eligible to generate a certificate for a
     * course: not a sub-category, and Generate Certificates enabled.
     *
     * Pass $sessionId to scope to one context (0 = the base course, outside
     * any session); pass null to return every eligible root category of the
     * course, base and every session alike — a platform-wide scan groups the
     * result by session_id itself to detect a genuinely ambiguous context.
     *
     * @return list<array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string}>
     */
    public function loadEligibleCategories(int $courseId, ?int $sessionId = 0, ?int $categoryId = null): array
    {
        $params = ['courseId' => $courseId];
        $filters = '';

        if (null !== $sessionId) {
            $filters .= ' AND COALESCE(session_id, 0) = :sessionId';
            $params['sessionId'] = $sessionId;
        }

        if (null !== $categoryId) {
            $filters .= ' AND id = :categoryId';
            $params['categoryId'] = $categoryId;
        }

        return $this->connection->fetchAllAssociative(
            'SELECT id, title, certif_min_score, COALESCE(session_id, 0) AS session_id
             FROM gradebook_category
             WHERE c_id = :courseId
               AND (parent_id IS NULL OR parent_id = 0)
               AND generate_certificates = 1'
            .$filters.'
             ORDER BY session_id, id',
            $params
        );
    }

    /**
     * @return array{complete: bool, score: float|int|null, ...}
     */
    public function evaluateCandidate(
        string $completionMode,
        int $userId,
        int $courseId,
        string $courseCode,
        float $minimumScore,
        int $sessionId,
        GradebookCategory $category
    ): array {
        if ('course-rule' === $completionMode) {
            return $this->evaluator->evaluate(
                $userId,
                $courseId,
                $courseCode,
                $minimumScore,
                $sessionId
            );
        }

        $score = Category::getCurrentScore(
            $userId,
            $category,
            true,
            $courseId,
            $sessionId
        );

        return [
            'complete' => (float) $score >= $minimumScore,
            'score' => $score,
        ];
    }

    /**
     * Pending students for one (course, category) context: enrolled, active,
     * and without a certificate for that category yet. $sessionId selects
     * the right subscription table — base-course enrollment (course_rel_user)
     * for 0, session enrollment (session_rel_course_rel_user) otherwise, since
     * the two use different tables *and* different "student" status codes
     * (STUDENT = 5 for course_rel_user, Session::STUDENT = 0 for
     * session_rel_course_rel_user).
     *
     * @return list<int>
     */
    public function loadPendingUserIds(
        int $courseId,
        int $categoryId,
        int $sessionId,
        int $afterUserId,
        int $batchSize,
        ?int $requestedUserId
    ): array {
        $params = [
            'courseId' => $courseId,
            'categoryId' => $categoryId,
            'afterUserId' => $afterUserId,
        ];

        if (0 === $sessionId) {
            $params['studentStatus'] = STUDENT;
            $userFilter = '';
            if (null !== $requestedUserId) {
                $userFilter = ' AND course_user.user_id = :requestedUserId';
                $params['requestedUserId'] = $requestedUserId;
            }

            $sql = 'SELECT DISTINCT course_user.user_id
                    FROM course_rel_user course_user
                    INNER JOIN user selected_user
                       ON selected_user.id = course_user.user_id
                      AND selected_user.active = 1
                    LEFT JOIN gradebook_certificate certificate
                       ON certificate.cat_id = :categoryId
                      AND certificate.user_id = course_user.user_id
                    WHERE course_user.c_id = :courseId
                      AND course_user.status = :studentStatus
                      AND course_user.user_id > :afterUserId
                      AND certificate.id IS NULL'
                .$userFilter.'
                    ORDER BY course_user.user_id ASC
                    LIMIT '.$batchSize;
        } else {
            $params['sessionId'] = $sessionId;
            $params['studentStatus'] = Session::STUDENT;
            $userFilter = '';
            if (null !== $requestedUserId) {
                $userFilter = ' AND session_user.user_id = :requestedUserId';
                $params['requestedUserId'] = $requestedUserId;
            }

            $sql = 'SELECT DISTINCT session_user.user_id
                    FROM session_rel_course_rel_user session_user
                    INNER JOIN user selected_user
                       ON selected_user.id = session_user.user_id
                      AND selected_user.active = 1
                    LEFT JOIN gradebook_certificate certificate
                       ON certificate.cat_id = :categoryId
                      AND certificate.user_id = session_user.user_id
                    WHERE session_user.c_id = :courseId
                      AND session_user.session_id = :sessionId
                      AND session_user.status = :studentStatus
                      AND session_user.user_id > :afterUserId
                      AND certificate.id IS NULL'
                .$userFilter.'
                    ORDER BY session_user.user_id ASC
                    LIMIT '.$batchSize;
        }

        $rows = $this->connection->fetchFirstColumn($sql, $params);

        return array_map('intval', $rows);
    }

    /**
     * @param array{subject: string, message: string, sender_id: int} $notification
     */
    public function generateCertificate(
        GradebookCategory $category,
        int $userId,
        bool $sendNotification,
        array $notification
    ): bool {
        Category::generateUserCertificate(
            $category,
            $userId,
            $sendNotification,
            true,
            $notification
        );

        $certificate = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id
             FROM gradebook_certificate
             WHERE cat_id = :categoryId
               AND user_id = :userId
             ORDER BY id DESC
             LIMIT 1',
            [
                'categoryId' => (int) $category->getId(),
                'userId' => $userId,
            ]
        );

        return false !== $certificate
            && (int) ($certificate['id'] ?? 0) > 0
            && (int) ($certificate['resource_node_id'] ?? 0) > 0;
    }

    /**
     * @param array<string, int> $summary
     */
    public function generationLimitReached(array $summary, int $limit, bool $dryRun): bool
    {
        if (0 === $limit) {
            return false;
        }

        $processed = $dryRun ? $summary['would_generate'] : $summary['generated'];

        return $processed >= $limit;
    }

    public function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
