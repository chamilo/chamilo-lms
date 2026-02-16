<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseStudentInfoHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<CourseRelUser>
 */
final class UserCourseSubscriptionsStateProvider implements ProviderInterface
{
    private const DEFAULT_ITEMS_PER_PAGE = 20;
    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CourseRelUserRepository $courseRelUserRepository,
        private readonly CourseStudentInfoHelper $courseStudentInfoHelper,
        private readonly SequenceResourceRepository $sequenceResourceRepository,
        private readonly RequestStack $requestStack,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $currentUser = $this->userHelper->getCurrent();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        $url = $this->accessUrlHelper->getCurrent() ?? $this->accessUrlHelper->getFirstAccessUrl();
        if (!$url instanceof AccessUrl) {
            throw new RuntimeException('Access URL not found');
        }

        $filters = $context['filters'] ?? [];
        $request = $this->requestStack->getCurrentRequest();

        // Pagination:
        // - Prefer Api Platform filters when available
        // - Fallback to query params for robustness
        $page = (int) ($filters['page'] ?? ($request?->query->getInt('page', 1) ?? 1));
        $itemsPerPage = (int) ($filters['itemsPerPage'] ?? ($request?->query->getInt('itemsPerPage', self::DEFAULT_ITEMS_PER_PAGE) ?? self::DEFAULT_ITEMS_PER_PAGE));

        if ($page < 1) {
            $page = 1;
        }

        if ($itemsPerPage < 1) {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        } elseif ($itemsPerPage > self::MAX_ITEMS_PER_PAGE) {
            $itemsPerPage = self::MAX_ITEMS_PER_PAGE;
        }

        $offset = ($page - 1) * $itemsPerPage;

        // Optional filters (kept for compatibility).
        $status = isset($filters['status']) ? (int) $filters['status'] : null;
        $special = isset($filters['special']) ? (int) $filters['special'] : 0;
        $search = isset($filters['q']) ? trim((string) $filters['q']) : '';

        $qb = $this->courseRelUserRepository->createQueryBuilder('cru')
            ->innerJoin('cru.course', 'c')
            ->addSelect('c')
            ->innerJoin('c.urls', 'cur')
            ->innerJoin('cur.url', 'u')
            ->andWhere('cru.user = :user')
            ->andWhere('u = :url')
            ->setParameter('user', $currentUser)
            ->setParameter('url', $url)
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage)
            ->addOrderBy('cru.sort', 'ASC')
            ->addOrderBy('c.title', 'ASC');

        if (null !== $status) {
            $qb->andWhere('cru.status = :status')->setParameter('status', $status);
        }

        // Example: special=1 defaults to teacher subscriptions when status is not explicitly provided.
        if (1 === $special && null === $status) {
            $qb->andWhere('cru.status = :teacherStatus')
                ->setParameter('teacherStatus', CourseRelUser::TEACHER);
        }

        if ('' !== $search) {
            $qb->andWhere('(LOWER(c.title) LIKE :q OR LOWER(c.code) LIKE :q)')
                ->setParameter('q', '%'.mb_strtolower($search).'%');
        }

        /** @var CourseRelUser[] $items */
        $items = $qb->getQuery()->getResult();

        if (empty($items)) {
            return [];
        }

        $userId = (int) $currentUser->getId();

        // Build buckets by session id when available (defensive, keeps compatibility).
        $courseIdsBySid = [];

        foreach ($items as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            if ($courseId <= 0) {
                continue;
            }

            $sid = 0;

            // Not all installs expose session on CourseRelUser. Be defensive.
            if (method_exists($cru, 'getSession') && $cru->getSession()) {
                $sid = (int) $cru->getSession()->getId();
            } elseif (method_exists($cru, 'getSessionId')) {
                $sid = (int) $cru->getSessionId();
            }

            $courseIdsBySid[$sid] ??= [];
            $courseIdsBySid[$sid][] = $courseId;
        }

        foreach ($courseIdsBySid as $sid => $ids) {
            $courseIdsBySid[$sid] = array_values(array_unique(array_map('intval', $ids)));
        }

        // Batch student info by session id.
        $batchBySid = [];
        foreach ($courseIdsBySid as $sid => $ids) {
            if (empty($ids)) {
                $batchBySid[$sid] = [];
                continue;
            }

            // Returns array keyed by (string) courseId.
            $batchBySid[$sid] = $this->courseStudentInfoHelper->getStudentInfoBatchForCourses(
                $userId,
                $ids,
                (int) $sid
            );
        }

        // Per-request cache for requirements checks to avoid repeated work.
        $requirementsCache = [];

        foreach ($items as $cru) {
            $courseId = (int) $cru->getCourse()->getId();
            if ($courseId <= 0) {
                continue;
            }

            $sid = 0;
            if (method_exists($cru, 'getSession') && $cru->getSession()) {
                $sid = (int) $cru->getSession()->getId();
            } elseif (method_exists($cru, 'getSessionId')) {
                $sid = (int) $cru->getSessionId();
            }

            // Hydrate student-info fields (existing logic).
            $stats = $batchBySid[$sid][(string) $courseId] ?? null;
            if (is_array($stats)) {
                $cru->setTrackingProgress($stats['progress'] ?? null);
                $cru->setScore($stats['score'] ?? null);
                $cru->setBestScore($stats['bestScore'] ?? null);
                $cru->setTimeSpentSeconds($stats['timeSpentSeconds'] ?? null);
                $cru->setCertificateAvailable($stats['certificateAvailable'] ?? null);
                $cru->setCompleted($stats['completed'] ?? null);
                $cru->setHasNewContent($stats['hasNewContent'] ?? null);
            }

            // Hydrate lightweight course requirements flags (no graph, no list).
            // Teachers should not be locked by requirements in UI lists.
            if ((int) $cru->getStatus() !== CourseRelUser::STUDENT) {
                $cru->setHasRequirements(false);
                $cru->setAllowSubscription(true);
                continue;
            }

            $cacheKey = $courseId.':'.$sid;

            if (!isset($requirementsCache[$cacheKey])) {
                $sequences = $this->sequenceResourceRepository->getRequirements($courseId, SequenceResource::COURSE_TYPE);

                // Detect if there is at least one valid Course requirement.
                $hasValidRequirement = false;
                foreach ($sequences as $sequence) {
                    foreach ($sequence['requirements'] ?? [] as $resource) {
                        if ($resource instanceof Course) {
                            $hasValidRequirement = true;
                            break 2;
                        }
                    }
                }

                if (!$hasValidRequirement) {
                    $requirementsCache[$cacheKey] = [
                        'hasRequirements' => false,
                        'allowSubscription' => true,
                    ];
                } else {
                    $checked = $this->sequenceResourceRepository->checkRequirementsForUser(
                        $sequences,
                        SequenceResource::COURSE_TYPE,
                        $userId,
                        $sid
                    );

                    $isUnlocked = $this->sequenceResourceRepository->checkSequenceAreCompleted($checked);

                    $requirementsCache[$cacheKey] = [
                        'hasRequirements' => true,
                        'allowSubscription' => (bool) $isUnlocked,
                    ];
                }
            }

            $cru->setHasRequirements($requirementsCache[$cacheKey]['hasRequirements']);
            $cru->setAllowSubscription($requirementsCache[$cacheKey]['allowSubscription']);
        }

        return $items;
    }
}
