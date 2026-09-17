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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
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
    private const GRADING_ELECTRONIC_COURSE_FIELD =
        'plugin_gradingelectronic_course_id';

    private const int LINK_EXERCISE = 1;
    private const int LINK_STUDENT_PUBLICATION = 3;
    private const int LINK_FORUM_THREAD = 5;

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

    public function hasLegacyGradebookConfirmation(int $courseId): bool
    {
        $fieldIds = $this->connection->fetchFirstColumn(
            'SELECT id
             FROM extra_field
             WHERE item_type = :itemType
               AND variable = :variable
             ORDER BY id',
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => self::GRADING_ELECTRONIC_COURSE_FIELD,
            ]
        );

        if ([] === $fieldIds) {
            return false;
        }

        if (1 !== \count($fieldIds)) {
            throw new RuntimeException(\sprintf('Multiple course extra fields were found for %s.', self::GRADING_ELECTRONIC_COURSE_FIELD));
        }

        $values = $this->connection->fetchFirstColumn(
            'SELECT field_value
             FROM extra_field_values
             WHERE field_id = :fieldId
               AND item_id = :courseId
             ORDER BY id',
            [
                'fieldId' => (int) $fieldIds[0],
                'courseId' => $courseId,
            ]
        );

        if ([] === $values) {
            return false;
        }

        if (1 !== \count($values)) {
            throw new RuntimeException(\sprintf('Course %d has multiple values for %s.', $courseId, self::GRADING_ELECTRONIC_COURSE_FIELD));
        }

        return '' !== trim((string) $values[0]);
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
     * Resolve multiple eligible root categories without selecting one merely
     * by numeric ID. For the base course in course-rule mode, completion-rule
     * components are the primary evidence. Historical certificate usage is
     * the fallback. A non-unique result remains unresolved.
     *
     * @param list<array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string}> $categories
     *
     * @return array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string}|null
     */
    public function resolveAmbiguousCategory(
        int $courseId,
        int $sessionId,
        array $categories,
        string $completionMode
    ): ?array {
        if ([] === $categories) {
            return null;
        }

        if (1 === \count($categories)) {
            return $categories[0];
        }

        $categoryIds = array_map(
            static fn (array $category): int => (int) $category['id'],
            $categories
        );
        $candidates = $categoryIds;

        // The migrated completion rule describes the base-course structure.
        // Do not project those course-level links onto a session context.
        if (0 === $sessionId && 'course-rule' === $completionMode) {
            $components = $this->loadCompletionRuleComponentsForCategoryResolution($courseId);
            $scores = $this->scoreCategoriesByCompletionComponents($categoryIds, $components);

            if ([] !== $scores) {
                arsort($scores);
                $bestScore = (int) reset($scores);

                if ($bestScore > 0) {
                    $bestIds = array_map(
                        'intval',
                        array_keys(array_filter(
                            $scores,
                            static fn (int $score): bool => $score === $bestScore
                        ))
                    );

                    if (1 === \count($bestIds)) {
                        return $this->findCategoryRow($categories, $bestIds[0]);
                    }

                    $candidates = $bestIds;
                }
            }
        }

        $historyCategoryId = $this->resolveCategoryByCertificateHistory($candidates);
        if (null === $historyCategoryId) {
            return null;
        }

        return $this->findCategoryRow($categories, $historyCategoryId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadCompletionRuleComponentsForCategoryResolution(int $courseId): array
    {
        $fieldIds = $this->connection->fetchFirstColumn(
            'SELECT id
             FROM extra_field
             WHERE item_type = :itemType
               AND variable = :variable
             ORDER BY id',
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => CourseCompletionRuleEvaluator::COURSE_RULE_FIELD_VARIABLE,
            ]
        );

        if ([] === $fieldIds) {
            return [];
        }

        if (1 !== \count($fieldIds)) {
            throw new RuntimeException('Multiple course completion rule extra fields were found.');
        }

        $values = $this->connection->fetchFirstColumn(
            'SELECT field_value
             FROM extra_field_values
             WHERE field_id = :fieldId
               AND item_id = :courseId
             ORDER BY id',
            [
                'fieldId' => (int) $fieldIds[0],
                'courseId' => $courseId,
            ]
        );

        if ([] === $values) {
            return [];
        }

        if (1 !== \count($values)) {
            throw new RuntimeException(\sprintf('Course %d has multiple completion rule values.', $courseId));
        }

        $rule = json_decode((string) $values[0], true);
        if (!\is_array($rule) || !isset($rule['components']) || !\is_array($rule['components'])) {
            throw new RuntimeException(\sprintf('Course %d has an invalid completion rule while resolving its certificate category.', $courseId));
        }

        return array_values(array_filter(
            $rule['components'],
            static fn (mixed $component): bool => \is_array($component)
        ));
    }

    /**
     * @param list<int>                  $categoryIds
     * @param list<array<string, mixed>> $components
     *
     * @return array<int, int>
     */
    private function scoreCategoriesByCompletionComponents(
        array $categoryIds,
        array $components
    ): array {
        $scores = array_fill_keys($categoryIds, 0);

        foreach ($categoryIds as $categoryId) {
            foreach ($components as $component) {
                $type = trim((string) ($component['type'] ?? ''));
                $resourceId = $this->positiveComponentIdOrNull($component['resource_id'] ?? null);
                $sourceId = $this->positiveComponentIdOrNull($component['source_resource_id'] ?? null);

                if ('evaluation' === $type && null !== $resourceId) {
                    $scores[$categoryId] += (int) $this->connection->fetchOne(
                        'SELECT COUNT(*)
                         FROM gradebook_evaluation
                         WHERE id = :resourceId
                           AND category_id = :categoryId',
                        [
                            'resourceId' => $resourceId,
                            'categoryId' => $categoryId,
                        ]
                    );

                    continue;
                }

                $linkType = match ($type) {
                    'exercise' => self::LINK_EXERCISE,
                    'work' => self::LINK_STUDENT_PUBLICATION,
                    'forum' => self::LINK_FORUM_THREAD,
                    default => null,
                };

                if (null === $linkType) {
                    continue;
                }

                $resourceIds = array_values(array_unique(array_filter(
                    [$resourceId, $sourceId],
                    static fn (?int $id): bool => null !== $id && $id > 0
                )));

                if ([] === $resourceIds) {
                    continue;
                }

                $scores[$categoryId] += (int) $this->connection->fetchOne(
                    'SELECT COUNT(*)
                     FROM gradebook_link
                     WHERE category_id = :categoryId
                       AND type = :type
                       AND ref_id IN (:resourceIds)',
                    [
                        'categoryId' => $categoryId,
                        'type' => $linkType,
                        'resourceIds' => $resourceIds,
                    ],
                    ['resourceIds' => ArrayParameterType::INTEGER]
                );
            }
        }

        return $scores;
    }

    /**
     * @param list<int> $categoryIds
     */
    private function resolveCategoryByCertificateHistory(array $categoryIds): ?int
    {
        if ([] === $categoryIds) {
            return null;
        }

        $counts = array_fill_keys($categoryIds, 0);
        $rows = $this->connection->executeQuery(
            'SELECT cat_id AS category_id, COUNT(*) AS certificate_count
             FROM gradebook_certificate
             WHERE cat_id IN (:categoryIds)
             GROUP BY cat_id',
            ['categoryIds' => $categoryIds],
            ['categoryIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];

            if (\array_key_exists($categoryId, $counts)) {
                $counts[$categoryId] = (int) $row['certificate_count'];
            }
        }

        arsort($counts);
        $bestCount = (int) reset($counts);

        if ($bestCount <= 0) {
            return null;
        }

        $bestIds = array_map(
            'intval',
            array_keys(array_filter(
                $counts,
                static fn (int $count): bool => $count === $bestCount
            ))
        );

        return 1 === \count($bestIds) ? $bestIds[0] : null;
    }

    /**
     * @param list<array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string}> $categories
     *
     * @return array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string}|null
     */
    private function findCategoryRow(array $categories, int $categoryId): ?array
    {
        foreach ($categories as $category) {
            if ((int) $category['id'] === $categoryId) {
                return $category;
            }
        }

        return null;
    }

    private function positiveComponentIdOrNull(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
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
