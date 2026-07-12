<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageAction;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<WikiReport>
 */
final readonly class WikiReportProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    private const REPORT_ALL = 'all';
    private const REPORT_RECENT = 'recent';
    private const REPORT_SEARCH = 'search';
    private const REPORT_BACKLINKS = 'backlinks';
    private const REPORT_ACTIVE_USERS = 'active-users';
    private const REPORT_USER_CONTRIBUTIONS = 'user-contributions';
    private const REPORT_MOST_CHANGED = 'most-changed';
    private const REPORT_MOST_VISITED = 'most-visited';
    private const REPORT_MOST_LINKED = 'most-linked';
    private const REPORT_ORPHANED = 'orphaned';
    private const REPORT_WANTED = 'wanted';
    private const REPORT_STATISTICS = 'statistics';

    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageRenderer $renderer,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $nodeId = $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadWikiContext($this->security, $this->settingsManager, $course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to read Wiki reports in this context.');
        }

        $studentView = $this->isWikiStudentView($request);
        $canManage = !$studentView && $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
        $reportName = $this->getReportName($request);

        if (self::REPORT_STATISTICS === $reportName && !$canManage) {
            throw new AccessDeniedHttpException('Only Wiki managers can view detailed statistics.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $allVersions = $this->wikiRepository->findVersionsInContext($courseId, $groupId, $sessionId, $canManage);
        $latestVersions = $this->wikiRepository->findLatestVersionsInContext(
            $courseId,
            $groupId,
            $sessionId,
            $canManage,
        );
        $taskPageIds = $this->getTaskPageIds($courseId, $latestVersions);
        $users = $this->getUsersById($allVersions);
        $addLock = $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);
        $canCreate = !$studentView && $this->canCreateWikiPage(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
            'new_page',
            $addLock,
        );

        $report = new WikiReport();
        $report->courseId = $courseId;
        $report->sessionId = $sessionId > 0 ? $sessionId : null;
        $report->groupId = $groupId > 0 ? $groupId : null;
        $report->nodeId = $nodeId;
        $report->report = $reportName;
        $report->title = $this->getReportTitle($reportName);
        $report->canManage = $canManage;
        $report->canCreate = $canCreate;
        $currentUser = $this->security->getUser();
        $report->canDeleteWiki = $canManage && [] !== $allVersions;
        $report->canSubscribeAll = $canManage;
        $report->allChangesSubscribed = $canManage
            && $currentUser instanceof User
            && $this->hasContextSubscription(
                $courseId,
                $sessionId,
                $groupId,
                (int) $currentUser->getId(),
            );
        $report->managementCsrfToken = $canManage
            ? (string) $this->csrfTokenManager->getToken(WikiPageAction::CSRF_TOKEN_ID)
            : '';
        $report->studentView = $studentView;
        $report->availableReports = $this->getAvailableReports($canManage);
        $report->categories = $this->getCategories($course, $session);
        $report->page = max(1, $request->query->getInt('page', 1));
        $report->itemsPerPage = min(
            self::MAX_ITEMS_PER_PAGE,
            max(1, $request->query->getInt('itemsPerPage', 20)),
        );
        $report->sortBy = trim((string) $request->query->get('sortBy', ''));
        $report->sortOrder = 'desc' === mb_strtolower((string) $request->query->get('sortOrder', 'asc'))
            ? 'desc'
            : 'asc';
        $report->search = trim((string) $request->query->get('search', ''));
        $report->searchContent = $request->query->getBoolean('searchContent');
        $report->allVersions = $request->query->getBoolean('allVersions');
        $report->categoryIds = $this->getCategoryIds($request, $report->categories);
        $report->matchAllCategories = $request->query->getBoolean('matchAllCategories');

        $items = match ($reportName) {
            self::REPORT_ALL => $this->buildPageItems(
                $latestVersions,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
                true,
            ),
            self::REPORT_RECENT => $this->buildPageItems(
                $allVersions,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
                false,
            ),
            self::REPORT_SEARCH => $this->buildSearchItems(
                $report,
                $latestVersions,
                $allVersions,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
            ),
            self::REPORT_BACKLINKS => $this->buildBacklinkItems(
                $report,
                $request,
                $latestVersions,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
            ),
            self::REPORT_ACTIVE_USERS => $this->buildActiveUserItems($allVersions, $users),
            self::REPORT_USER_CONTRIBUTIONS => $this->buildUserContributionItems(
                $report,
                $request,
                $allVersions,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
            ),
            self::REPORT_MOST_CHANGED => $this->buildMostChangedItems($allVersions, $latestVersions, $taskPageIds),
            self::REPORT_MOST_VISITED => $this->buildMostVisitedItems($allVersions, $latestVersions, $taskPageIds),
            self::REPORT_MOST_LINKED => $this->buildMostLinkedItems($latestVersions, $taskPageIds),
            self::REPORT_ORPHANED => $this->buildOrphanedItems($latestVersions, $taskPageIds),
            self::REPORT_WANTED => $this->buildWantedItems($latestVersions, $canCreate),
            self::REPORT_STATISTICS => [],
            default => throw new BadRequestHttpException('The requested Wiki report is not supported.'),
        };

        if (self::REPORT_STATISTICS === $reportName) {
            $report->statistics = $this->buildStatistics($allVersions, $latestVersions, $taskPageIds, $addLock);

            return $report;
        }

        [$items, $sortBy, $sortOrder] = $this->sortItems($items, $reportName, $report->sortBy, $report->sortOrder);
        $report->sortBy = $sortBy;
        $report->sortOrder = $sortOrder;
        $report->totalItems = \count($items);
        $report->items = \array_slice(
            $items,
            ($report->page - 1) * $report->itemsPerPage,
            $report->itemsPerPage,
        );

        return $report;
    }

    private function hasContextSubscription(
        int $courseId,
        int $sessionId,
        int $groupId,
        int $userId,
    ): bool {
        $subscription = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.userId = :userId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('type', 'wiki', Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $subscription instanceof CWikiMailcue;
    }

    private function getReportName(Request $request): string
    {
        $report = mb_strtolower(trim((string) $request->query->get('report', self::REPORT_ALL)));
        $allowed = array_column($this->getAvailableReports(true), 'value');
        $allowed[] = self::REPORT_BACKLINKS;
        $allowed[] = self::REPORT_USER_CONTRIBUTIONS;

        if (!\in_array($report, $allowed, true)) {
            throw new BadRequestHttpException('The requested Wiki report is not supported.');
        }

        return $report;
    }

    private function getReportTitle(string $report): string
    {
        return match ($report) {
            self::REPORT_ALL => 'All pages',
            self::REPORT_RECENT => 'Latest changes',
            self::REPORT_SEARCH => 'Search',
            self::REPORT_BACKLINKS => 'What links here',
            self::REPORT_ACTIVE_USERS => 'Most active users',
            self::REPORT_USER_CONTRIBUTIONS => 'User contributions',
            self::REPORT_MOST_CHANGED => 'Most changed pages',
            self::REPORT_MOST_VISITED => 'Most visited pages',
            self::REPORT_MOST_LINKED => 'Pages most linked',
            self::REPORT_ORPHANED => 'Orphaned pages',
            self::REPORT_WANTED => 'Wanted pages',
            self::REPORT_STATISTICS => 'Statistics',
            default => 'Wiki',
        };
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private function getAvailableReports(bool $canManage): array
    {
        $reports = [
            ['value' => self::REPORT_ALL, 'label' => 'All pages'],
            ['value' => self::REPORT_RECENT, 'label' => 'Latest changes'],
            ['value' => self::REPORT_SEARCH, 'label' => 'Search'],
            ['value' => self::REPORT_ACTIVE_USERS, 'label' => 'Most active users'],
            ['value' => self::REPORT_MOST_VISITED, 'label' => 'Most visited pages'],
            ['value' => self::REPORT_MOST_CHANGED, 'label' => 'Most changed pages'],
            ['value' => self::REPORT_ORPHANED, 'label' => 'Orphaned pages'],
            ['value' => self::REPORT_WANTED, 'label' => 'Wanted pages'],
            ['value' => self::REPORT_MOST_LINKED, 'label' => 'Pages most linked'],
        ];

        if ($canManage) {
            $reports[] = ['value' => self::REPORT_STATISTICS, 'label' => 'Statistics'];
        }

        return $reports;
    }

    /**
     * @return array<int, array{id:int, title:string}>
     */
    private function getCategories(Course $course, ?Session $session): array
    {
        if (!$this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_categories_enabled',
            false,
        )) {
            return [];
        }

        $categories = $this->entityManager->getRepository(CWikiCategory::class)->findBy(
            ['course' => $course, 'session' => $session],
            ['lft' => 'ASC'],
        );
        $result = [];

        foreach ($categories as $category) {
            if (!$category instanceof CWikiCategory || null === $category->getId()) {
                continue;
            }

            $result[] = [
                'id' => (int) $category->getId(),
                'title' => $category->getTitle(),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, CWiki> $versions
     *
     * @return array<int, int>
     */
    private function getTaskPageIds(int $courseId, array $versions): array
    {
        $pageIds = [];

        foreach ($versions as $wiki) {
            if (null !== $wiki->getPageId()) {
                $pageIds[(int) $wiki->getPageId()] = (int) $wiki->getPageId();
            }
        }

        if ([] === $pageIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->getRepository(CWikiConf::class)->createQueryBuilder('c');
        $rows = $queryBuilder
            ->select('c.pageId AS pageId')
            ->andWhere('c.cId = :courseId')
            ->andWhere($queryBuilder->expr()->in('c.pageId', ':pageIds'))
            ->andWhere('c.task <> :emptyTask')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('pageIds', array_values($pageIds), ArrayParameterType::INTEGER)
            ->setParameter('emptyTask', '', Types::STRING)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_values(array_map(
            static fn (array $row): int => (int) $row['pageId'],
            $rows,
        ));
    }

    /**
     * @param array<int, CWiki> $versions
     *
     * @return array<int, User>
     */
    private function getUsersById(array $versions): array
    {
        $userIds = [];

        foreach ($versions as $wiki) {
            if ($wiki->getUserId() > 0) {
                $userIds[$wiki->getUserId()] = $wiki->getUserId();
            }
        }

        if ([] === $userIds) {
            return [];
        }

        $users = $this->entityManager->getRepository(User::class)->findBy(['id' => array_values($userIds)]);
        $mapped = [];

        foreach ($users as $user) {
            if ($user instanceof User && null !== $user->getId()) {
                $mapped[(int) $user->getId()] = $user;
            }
        }

        return $mapped;
    }

    /**
     * @param array<int, array{id:int, title:string}> $categories
     *
     * @return array<int, int>
     */
    private function getCategoryIds(Request $request, array $categories): array
    {
        $singleValue = trim((string) $request->query->get('categoryIds', ''));
        $rawValue = '' === $singleValue ? [] : explode(',', $singleValue);

        $allowedIds = array_fill_keys(array_map(
            static fn (array $category): int => $category['id'],
            $categories,
        ), true);
        $result = [];

        foreach ($rawValue as $candidate) {
            $categoryId = (int) trim($candidate);
            if ($categoryId > 0 && isset($allowedIds[$categoryId])) {
                $result[$categoryId] = $categoryId;
            }
        }

        return array_values($result);
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, int>   $taskPageIds
     * @param array<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPageItems(
        array $versions,
        array $taskPageIds,
        array $users,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $studentView,
        bool $allowEdit,
    ): array {
        $items = [];

        foreach ($versions as $wiki) {
            $items[] = $this->normalizePageItem(
                $wiki,
                $taskPageIds,
                $users,
                $course,
                $session,
                $group,
                $studentView,
                $allowEdit,
            );
        }

        return $items;
    }

    /**
     * @param array<int, int>  $taskPageIds
     * @param array<int, User> $users
     *
     * @return array<string, mixed>
     */
    private function normalizePageItem(
        CWiki $wiki,
        array $taskPageIds,
        array $users,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $studentView,
        bool $allowEdit,
    ): array {
        $user = $users[$wiki->getUserId()] ?? null;
        $pageId = (int) ($wiki->getPageId() ?? 0);
        $categories = [];

        foreach ($wiki->getCategories() as $category) {
            if (!$category instanceof CWikiCategory || null === $category->getId()) {
                continue;
            }

            $categories[] = [
                'id' => (int) $category->getId(),
                'title' => $category->getTitle(),
            ];
        }

        return [
            'iid' => (int) ($wiki->getIid() ?? 0),
            'pageId' => $pageId,
            'reflink' => $wiki->getReflink(),
            'title' => $this->renderer->displayTitle($wiki->getReflink(), $wiki->getTitle()),
            'assignment' => $wiki->getAssignment(),
            'hasTask' => \in_array($pageId, $taskPageIds, true),
            'authorId' => $wiki->getUserId() > 0 ? $wiki->getUserId() : null,
            'authorName' => $user instanceof User ? $user->getFullName() : 'Anonymous',
            'updatedAt' => $wiki->getDtime()?->format(DATE_ATOM),
            'version' => (int) ($wiki->getVersion() ?? 0),
            'comment' => $wiki->getComment(),
            'progress' => $this->renderer->normalizeStoredProgress($wiki->getProgress()),
            'score' => (int) ($wiki->getScore() ?? 0),
            'hits' => (int) ($wiki->getHits() ?? 0),
            'visible' => 1 === $wiki->getVisibility(),
            'canEdit' => $allowEdit
                && !$studentView
                && $this->canEditWikiPage(
                    $this->entityManager,
                    $this->security,
                    $this->settingsManager,
                    $course,
                    $session,
                    $group,
                    $wiki,
                ),
            'categories' => $categories,
        ];
    }

    /**
     * @param array<int, CWiki> $latestVersions
     * @param array<int, CWiki> $allVersions
     * @param array<int, int>   $taskPageIds
     * @param array<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSearchItems(
        WikiReport $report,
        array $latestVersions,
        array $allVersions,
        array $taskPageIds,
        array $users,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $studentView,
    ): array {
        if ('' === $report->search) {
            return [];
        }

        if (mb_strlen($report->search) < 3) {
            throw new BadRequestHttpException('The Wiki search term must contain at least 3 characters.');
        }

        $needle = mb_strtolower($report->search);
        $versions = $report->allVersions ? $allVersions : $latestVersions;
        $matches = array_values(array_filter(
            $versions,
            static function (CWiki $wiki) use ($needle, $report): bool {
                $titleMatches = str_contains(mb_strtolower($wiki->getTitle()), $needle);
                $contentMatches = $report->searchContent
                    && str_contains(mb_strtolower($wiki->getContent()), $needle);

                if (!$titleMatches && !$contentMatches) {
                    return false;
                }

                if ([] === $report->categoryIds) {
                    return true;
                }

                $pageCategoryIds = [];
                foreach ($wiki->getCategories() as $category) {
                    if ($category instanceof CWikiCategory && null !== $category->getId()) {
                        $pageCategoryIds[] = (int) $category->getId();
                    }
                }

                $matchingCategories = array_intersect($report->categoryIds, $pageCategoryIds);

                return $report->matchAllCategories
                    ? \count($matchingCategories) === \count($report->categoryIds)
                    : [] !== $matchingCategories;
            },
        ));

        return $this->buildPageItems(
            $matches,
            $taskPageIds,
            $users,
            $course,
            $session,
            $group,
            $studentView,
            !$report->allVersions,
        );
    }

    /**
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     * @param array<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildBacklinkItems(
        WikiReport $report,
        Request $request,
        array $latestVersions,
        array $taskPageIds,
        array $users,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $studentView,
    ): array {
        $targetReflink = $this->renderer->normalizeReflink((string) $request->query->get('target', ''));
        $target = $this->findLatestPageByReflink($latestVersions, $targetReflink);

        if (!$target instanceof CWiki) {
            throw new BadRequestHttpException('A valid Wiki page is required to list backlinks.');
        }

        $report->targetReflink = $targetReflink;
        $report->targetTitle = $this->renderer->displayTitle($targetReflink, $target->getTitle());
        $matching = array_values(array_filter(
            $latestVersions,
            fn (CWiki $wiki): bool => \in_array($targetReflink, $this->parseLinkTokens($wiki->getLinksto()), true),
        ));

        return $this->buildPageItems(
            $matching,
            $taskPageIds,
            $users,
            $course,
            $session,
            $group,
            $studentView,
            true,
        );
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildActiveUserItems(array $versions, array $users): array
    {
        /** @var array<string, array{id:string, userId:?int, authorName:string, contributions:int}> $counts */
        $counts = [];

        foreach ($versions as $wiki) {
            $isKnownUser = $wiki->getUserId() > 0;
            $key = $isKnownUser ? 'user:'.$wiki->getUserId() : 'anonymous:'.$wiki->getUserIp();

            if (!isset($counts[$key])) {
                $counts[$key] = [
                    'id' => $isKnownUser ? $key : 'anonymous:'.\count($counts),
                    'userId' => $isKnownUser ? $wiki->getUserId() : null,
                    'authorName' => ($users[$wiki->getUserId()] ?? null)?->getFullName() ?? 'Anonymous',
                    'contributions' => 0,
                ];
            }

            ++$counts[$key]['contributions'];
        }

        return array_values($counts);
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, int>   $taskPageIds
     * @param array<int, User>  $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildUserContributionItems(
        WikiReport $report,
        Request $request,
        array $versions,
        array $taskPageIds,
        array $users,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $studentView,
    ): array {
        $userId = max(0, $request->query->getInt('userId'));
        $report->userId = $userId > 0 ? $userId : null;
        $report->userName = $userId > 0
            ? (($users[$userId] ?? null)?->getFullName() ?? 'Unknown user')
            : 'Anonymous';
        $matching = array_values(array_filter(
            $versions,
            static fn (CWiki $wiki): bool => $wiki->getUserId() === $userId,
        ));

        return $this->buildPageItems(
            $matching,
            $taskPageIds,
            $users,
            $course,
            $session,
            $group,
            $studentView,
            false,
        );
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMostChangedItems(array $versions, array $latestVersions, array $taskPageIds): array
    {
        $changes = [];

        foreach ($versions as $wiki) {
            $changes[$wiki->getReflink()] = max(
                $changes[$wiki->getReflink()] ?? 0,
                (int) ($wiki->getVersion() ?? 0),
            );
        }

        $items = [];
        foreach ($latestVersions as $wiki) {
            $items[] = $this->normalizeMetricPageItem(
                $wiki,
                $taskPageIds,
                'changes',
                $changes[$wiki->getReflink()] ?? 0,
            );
        }

        return $items;
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMostVisitedItems(array $versions, array $latestVersions, array $taskPageIds): array
    {
        $hits = [];

        foreach ($versions as $wiki) {
            $hits[$wiki->getReflink()] = ($hits[$wiki->getReflink()] ?? 0) + (int) ($wiki->getHits() ?? 0);
        }

        $items = [];
        foreach ($latestVersions as $wiki) {
            $items[] = $this->normalizeMetricPageItem(
                $wiki,
                $taskPageIds,
                'hits',
                $hits[$wiki->getReflink()] ?? 0,
            );
        }

        return $items;
    }

    /**
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMostLinkedItems(array $latestVersions, array $taskPageIds): array
    {
        $existing = array_fill_keys(array_map(
            static fn (CWiki $wiki): string => $wiki->getReflink(),
            $latestVersions,
        ), true);
        $linked = [];

        foreach ($latestVersions as $wiki) {
            foreach ($this->parseLinkTokens($wiki->getLinksto()) as $token) {
                if ($token === $wiki->getReflink() || !isset($existing[$token])) {
                    continue;
                }

                $linked[$token] = true;
            }
        }

        $items = [];
        foreach ($latestVersions as $wiki) {
            if (!isset($linked[$wiki->getReflink()])) {
                continue;
            }

            $items[] = $this->normalizeMetricPageItem($wiki, $taskPageIds, 'linked', true);
        }

        return $items;
    }

    /**
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildOrphanedItems(array $latestVersions, array $taskPageIds): array
    {
        $linked = [];

        foreach ($latestVersions as $wiki) {
            foreach ($this->parseLinkTokens($wiki->getLinksto()) as $token) {
                if ($token !== $wiki->getReflink()) {
                    $linked[$token] = true;
                }
            }
        }

        $items = [];
        foreach ($latestVersions as $wiki) {
            if (isset($linked[$wiki->getReflink()])) {
                continue;
            }

            $items[] = $this->normalizeMetricPageItem($wiki, $taskPageIds, 'orphaned', true);
        }

        return $items;
    }

    /**
     * @param array<int, CWiki> $latestVersions
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildWantedItems(array $latestVersions, bool $canCreate): array
    {
        $existing = array_fill_keys(array_map(
            static fn (CWiki $wiki): string => $wiki->getReflink(),
            $latestVersions,
        ), true);
        $wanted = [];

        foreach ($latestVersions as $wiki) {
            foreach ($this->parseLinkTokens($wiki->getLinksto()) as $token) {
                if (!isset($existing[$token])) {
                    $wanted[$token] = $token;
                }
            }
        }

        $items = [];
        foreach ($wanted as $reflink) {
            $items[] = [
                'reflink' => $reflink,
                'title' => $this->renderer->displayTitle($reflink),
                'canCreate' => $canCreate,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $taskPageIds
     *
     * @return array<string, mixed>
     */
    private function normalizeMetricPageItem(CWiki $wiki, array $taskPageIds, string $metric, bool|int $value): array
    {
        $pageId = (int) ($wiki->getPageId() ?? 0);

        return [
            'iid' => (int) ($wiki->getIid() ?? 0),
            'pageId' => $pageId,
            'reflink' => $wiki->getReflink(),
            'title' => $this->renderer->displayTitle($wiki->getReflink(), $wiki->getTitle()),
            'assignment' => $wiki->getAssignment(),
            'hasTask' => \in_array($pageId, $taskPageIds, true),
            $metric => $value,
        ];
    }

    /**
     * @param array<int, CWiki> $versions
     * @param array<int, CWiki> $latestVersions
     * @param array<int, int>   $taskPageIds
     *
     * @return array<string, mixed>
     */
    private function buildStatistics(
        array $versions,
        array $latestVersions,
        array $taskPageIds,
        int $addLock,
    ): array {
        $allContent = $this->calculateContentMetrics($versions);
        $latestContent = $this->calculateContentMetrics($latestVersions);
        $score = 0;
        $progress = 0;
        $editing = 0;
        $hidden = 0;
        $protected = 0;
        $discussionLocked = 0;
        $discussionHidden = 0;
        $teacherRatingOnly = 0;
        $teacherAssignments = 0;
        $learnerAssignments = 0;
        $users = [];
        $ips = [];
        $firstDate = null;
        $lastDate = null;

        foreach ($versions as $wiki) {
            $users[$wiki->getUserId()] = true;
            if ('' !== $wiki->getUserIp()) {
                $ips[$wiki->getUserIp()] = true;
            }

            $date = $wiki->getDtime();
            if ($date instanceof DateTimeInterface) {
                if (!$firstDate instanceof DateTimeInterface || $date < $firstDate) {
                    $firstDate = $date;
                }

                if (!$lastDate instanceof DateTimeInterface || $date > $lastDate) {
                    $lastDate = $date;
                }
            }
        }

        foreach ($latestVersions as $wiki) {
            $score += (int) ($wiki->getScore() ?? 0);
            $progress += $this->renderer->normalizeStoredProgress($wiki->getProgress());
            $editing += 0 !== $wiki->getIsEditing() ? 1 : 0;
            $hidden += 0 === $wiki->getVisibility() ? 1 : 0;
            $protected += 1 === $wiki->getEditlock() ? 1 : 0;
            $discussionLocked += 0 === $wiki->getAddlockDisc() ? 1 : 0;
            $discussionHidden += 0 === $wiki->getVisibilityDisc() ? 1 : 0;
            $teacherRatingOnly += 0 === $wiki->getRatinglockDisc() ? 1 : 0;
            $teacherAssignments += 1 === $wiki->getAssignment() ? 1 : 0;
            $learnerAssignments += 2 === $wiki->getAssignment() ? 1 : 0;
        }

        $pageCount = \count($latestVersions);

        return [
            'general' => [
                'learnersCanAddPages' => 1 === $addLock,
                'firstCreatedAt' => $firstDate?->format(DATE_ATOM),
                'lastUpdatedAt' => $lastDate?->format(DATE_ATOM),
                'averageScore' => $pageCount > 0 ? round($score / $pageCount, 2) : 0,
                'averageProgress' => $pageCount > 0 ? round($progress / $pageCount, 2) : 0,
                'contributors' => \count($users),
                'contributorIpAddresses' => \count($ips),
            ],
            'pages' => [
                'pages' => $pageCount,
                'versions' => \count($versions),
                'emptyPages' => $latestContent['empty'],
                'emptyVersions' => $allContent['empty'],
                'visits' => $latestContent['visits'],
                'versionVisits' => $allContent['visits'],
                'editingNow' => $editing,
                'hiddenPages' => $hidden,
                'protectedPages' => $protected,
                'discussionLockedPages' => $discussionLocked,
                'discussionHiddenPages' => $discussionHidden,
                'versionComments' => $allContent['comments'],
                'teacherRatingOnlyPages' => $teacherRatingOnly,
                'learnerRatingPages' => max(0, $pageCount - $teacherRatingOnly),
                'teacherAssignmentPages' => $teacherAssignments,
                'learnerAssignmentPages' => $learnerAssignments,
                'taskPages' => \count($taskPageIds),
            ],
            'content' => [
                'latest' => $latestContent,
                'allVersions' => $allContent,
            ],
        ];
    }

    /**
     * @param array<int, CWiki> $versions
     *
     * @return array<string, int>
     */
    private function calculateContentMetrics(array $versions): array
    {
        $metrics = [
            'words' => 0,
            'externalLinks' => 0,
            'anchors' => 0,
            'mailLinks' => 0,
            'ftpLinks' => 0,
            'ircLinks' => 0,
            'newsLinks' => 0,
            'wikiLinks' => 0,
            'images' => 0,
            'flashFiles' => 0,
            'mp3Files' => 0,
            'flvFiles' => 0,
            'youtubeVideos' => 0,
            'multimediaFiles' => 0,
            'tables' => 0,
            'empty' => 0,
            'comments' => 0,
            'visits' => 0,
        ];

        foreach ($versions as $wiki) {
            $content = $wiki->getContent();
            $metrics['words'] += $this->renderer->wordCount($content);
            $metrics['externalLinks'] += substr_count($content, 'href=');
            $metrics['anchors'] += substr_count($content, 'href="#');
            $metrics['mailLinks'] += substr_count($content, 'href="mailto');
            $metrics['ftpLinks'] += substr_count($content, 'href="ftp');
            $metrics['ircLinks'] += substr_count($content, 'href="irc');
            $metrics['newsLinks'] += substr_count($content, 'href="news');
            $metrics['wikiLinks'] += substr_count($content, '[[');
            $metrics['images'] += substr_count($content, '<img');
            $metrics['flashFiles'] += substr_count((string) preg_replace('/player\.swf/', ' ', $content), '.swf"');
            $metrics['mp3Files'] += substr_count($content, '.mp3');
            $metrics['flvFiles'] += (int) (substr_count($content, '.flv') / 5);
            $metrics['youtubeVideos'] += substr_count($content, 'http://www.youtube.com');
            $metrics['multimediaFiles'] += substr_count($content, 'video/x-msvideo');
            $metrics['tables'] += substr_count($content, '<table');
            $metrics['empty'] += '' === $content ? 1 : 0;
            $metrics['comments'] += '' !== $wiki->getComment() ? 1 : 0;
            $metrics['visits'] += (int) ($wiki->getHits() ?? 0);
        }

        return $metrics;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array{0:array<int, array<string, mixed>>, 1:string, 2:string}
     */
    private function sortItems(array $items, string $report, string $requestedSort, string $requestedOrder): array
    {
        [$defaultSort, $defaultOrder] = match ($report) {
            self::REPORT_RECENT, self::REPORT_SEARCH, self::REPORT_USER_CONTRIBUTIONS => ['updatedAt', 'desc'],
            self::REPORT_ACTIVE_USERS => ['contributions', 'desc'],
            self::REPORT_MOST_CHANGED => ['changes', 'desc'],
            self::REPORT_MOST_VISITED => ['hits', 'desc'],
            default => ['title', 'asc'],
        };
        $allowed = [
            'title',
            'authorName',
            'updatedAt',
            'version',
            'hits',
            'changes',
            'contributions',
        ];
        $sortBy = \in_array($requestedSort, $allowed, true) ? $requestedSort : $defaultSort;
        $sortOrder = '' !== $requestedSort ? $requestedOrder : $defaultOrder;

        usort($items, static function (array $left, array $right) use ($sortBy, $sortOrder): int {
            $leftValue = $left[$sortBy] ?? null;
            $rightValue = $right[$sortBy] ?? null;

            if (is_numeric($leftValue) && is_numeric($rightValue)) {
                $result = (float) $leftValue <=> (float) $rightValue;
            } else {
                $result = strnatcasecmp((string) $leftValue, (string) $rightValue);
            }

            return 'desc' === $sortOrder ? -$result : $result;
        });

        return [$items, $sortBy, $sortOrder];
    }

    /**
     * @param array<int, CWiki> $versions
     */
    private function findLatestPageByReflink(array $versions, string $reflink): ?CWiki
    {
        foreach ($versions as $wiki) {
            if ($wiki->getReflink() === $reflink) {
                return $wiki;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function parseLinkTokens(string $linksto): array
    {
        $tokens = preg_split('/\s+/u', trim($linksto)) ?: [];
        $result = [];

        foreach ($tokens as $token) {
            $reflink = $this->renderer->normalizeReflink($token);
            if ('' !== $reflink) {
                $result[$reflink] = $reflink;
            }
        }

        return array_values($result);
    }
}
