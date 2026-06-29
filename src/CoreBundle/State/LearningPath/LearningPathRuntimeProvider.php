<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathRuntime;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\LpAdvancedAccessHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<LearningPathRuntime> */
final readonly class LearningPathRuntimeProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    private const AUDIO_EXTENSIONS = ['aac', 'm4a', 'mp3', 'ogg', 'wav', 'webm'];
    private const COMPLETED_STATUSES = ['completed', 'passed', 'succeeded', 'browsed', 'failed'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private LpAdvancedAccessHelper $advancedAccessHelper,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ResourceNodeRepository $resourceNodeRepository,
        private ScormRuntimeManager $scormRuntimeManager,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathRuntime
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lp = $this->getLearningPath((int) ($uriVariables['lpId'] ?? 0));
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $canEdit = $this->canManageLearningPaths($this->security);
        $canManage = $canEdit && !$this->isStudentViewRequest($this->requestStack);
        $this->assertRuntimeAccess($lp, $course, $session, $group, $user, $canEdit);

        $items = $this->getLearningPathItems($lp);
        $latestView = $canManage ? null : $this->findLatestView($lp, $course, $session, $user);
        $itemViewRows = $this->getItemViewRows($latestView);
        $itemViews = $this->indexLatestItemViews($itemViewRows);
        $itemsById = [];
        $childrenCountByParent = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            $itemsById[$itemId] = $item;
            $parent = $item->getParent();
            $parentId = $parent instanceof CLpItem && 'root' !== $parent->getItemType()
                ? (int) $parent->getIid()
                : 0;
            $childrenCountByParent[$parentId] = ($childrenCountByParent[$parentId] ?? 0) + 1;
        }

        $bypassPrerequisites = $canEdit && $this->isTruthySetting(
            $this->settingsManager->getSetting('lp.allow_teachers_to_access_blocked_lp_by_prerequisite', true),
        );
        $totalTime = $this->calculateTotalTime($itemViewRows, $latestView);
        $minimumTime = $this->minimumTimeEnabled() ? max(0, $lp->getAccumulateWorkTime() * 60) : 0;
        $minimumTimeReached = 0 === $minimumTime || $totalTime >= $minimumTime;
        $completedItemIds = $this->getCompletedItemIds($itemViews);
        $nonFinalContentIds = [];
        foreach ($items as $item) {
            if (!\in_array($item->getItemType(), ['dir', 'final_item'], true)) {
                $nonFinalContentIds[] = (int) $item->getIid();
            }
        }
        $allNonFinalItemsCompleted = [] === array_diff($nonFinalContentIds, $completedItemIds);

        $rows = [];
        $contentIds = [];
        $availableContentIds = [];
        $availabilityById = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            $itemType = trim($item->getItemType());
            $available = 'dir' === $itemType || $this->isItemAvailable(
                $item,
                $itemsById,
                $itemViews,
                $bypassPrerequisites,
            );
            if ('final_item' === $itemType && !$bypassPrerequisites) {
                $available = $available && $allNonFinalItemsCompleted && $minimumTimeReached;
            }
            $itemView = $itemViews[$itemId] ?? null;
            $status = $itemView instanceof CLpItemView ? trim($itemView->getStatus()) : 'not attempted';
            $parent = $item->getParent();
            $parentId = $parent instanceof CLpItem && 'root' !== $parent->getItemType()
                ? (int) $parent->getIid()
                : 0;

            if ('dir' !== $itemType) {
                $contentIds[] = $itemId;
                $availabilityById[$itemId] = $available;
                if ($available) {
                    $availableContentIds[] = $itemId;
                }
            }

            $rows[] = [
                'id' => $itemId,
                'title' => $this->plainTitle($item->getTitle()),
                'itemType' => $itemType,
                'parentId' => $parentId,
                'level' => $this->getItemDepth($item),
                'displayOrder' => (int) $item->getDisplayOrder(),
                'status' => '' !== $status ? $status : 'not attempted',
                'score' => $itemView instanceof CLpItemView ? (float) $itemView->getScore() : 0.0,
                'available' => $available,
                'isSection' => 'dir' === $itemType,
                'hasChildren' => ($childrenCountByParent[$itemId] ?? 0) > 0,
                'hasPrerequisite' => '' !== trim((string) $item->getPrerequisite()),
            ];
        }

        $totalItems = \count($contentIds);
        $completedItems = \count(array_intersect($contentIds, $completedItemIds));
        $progress = $totalItems > 0 ? (int) round(($completedItems * 100) / $totalItems) : 0;
        $requestedItemId = (int) ($context['runtime_item_id'] ?? 0);
        if ($requestedItemId <= 0) {
            $requestedItemId = $request->query->getInt('itemId');
        }
        if ($requestedItemId <= 0) {
            $requestedItemId = $request->query->getInt('item_id');
        }

        $currentItemId = $this->resolveCurrentItemId(
            $requestedItemId,
            $latestView,
            $itemsById,
            $availableContentIds,
            $itemViews,
            $bypassPrerequisites,
        );
        $currentItem = $itemsById[$currentItemId] ?? null;

        $runtime = new LearningPathRuntime();
        $runtime->lpId = (int) $lp->getIid();
        $runtime->title = $this->plainTitle($lp->getTitle());
        $runtime->lpType = $lp->getLpType();
        $displaySettings = $this->getDisplaySettings();
        $runtime->runtimeSupported = \in_array($lp->getLpType(), [CLp::LP_TYPE, CLp::SCORM_TYPE], true);
        $runtime->canManage = $canManage;
        $runtime->canEdit = $canEdit;
        $runtime->previewImageUrl = $lp->getResourceNode()?->hasResourceFile()
            ? $this->lpRepository->getResourceFileUrl($lp)
            : '/main/img/icons/128/unknown.png';
        $runtime->author = $this->plainTitle((string) $lp->getAuthor());
        $runtime->hideToc = $lp->getHideTocFrame();
        $runtime->displayMode = $lp->getDefaultViewMod();
        $runtime->returnLink = (int) $this->settingsCourseManager->getCourseSettingValue('lp_return_link');
        $runtime->homeUrl = $this->buildReturnUrl(
            $runtime->returnLink,
            $course,
            $session,
            $group,
            $request,
        );
        $runtime->showHome = $this->isTruthySetting(
            $this->settingsManager->getSetting('lp.allow_lp_return_link', true),
        );
        $runtime->reportingUrl = $this->buildReportingUrl($lp, $course, $session, $group, $request);
        $runtime->showReporting = $canEdit
            && $this->displaySettingEnabled($displaySettings, 'show_reporting_icon', true);
        $runtime->showToolbarByDefault = $this->displaySettingEnabled(
            $displaySettings,
            'show_toolbar_by_default',
        );
        $runtime->navigationInTheMiddle = $this->displaySettingEnabled(
            $displaySettings,
            'navigation_in_the_middle',
        );
        $runtime->hideArrowNavigation = $this->displaySettingEnabled(
            $displaySettings,
            'hide_lp_arrow_navigation',
        );
        $runtime->menuLocation = $this->getMenuLocation();
        $runtime->accordionToc = $this->isTruthySetting(
            $this->settingsManager->getSetting('lp.lp_view_accordion', true),
        );
        $runtime->progress = max(0, min(100, $progress));
        $runtime->completedItems = $completedItems;
        $runtime->totalItems = $totalItems;
        $runtime->totalTime = $totalTime;
        $runtime->attemptMode = $this->getAttemptMode($lp);
        $runtime->currentAttempt = max(0, (int) ($latestView?->getViewCount() ?? 0));
        $runtime->currentItemAttempt = max(0, (int) (($itemViews[$currentItemId] ?? null)?->getViewCount() ?? 0));
        $runtime->maxAttempts = max(0, $lp->getMaxAttempts());
        $runtime->canRestart = !$canManage
            && $latestView instanceof CLpView
            && (0 === $runtime->maxAttempts || $runtime->currentAttempt < $runtime->maxAttempts);
        $runtime->minimumTime = $minimumTime;
        $runtime->minimumTimeReached = $minimumTimeReached;
        $runtime->currentItemId = $currentItemId;
        [$runtime->previousItemId, $runtime->nextItemId] = $this->getAdjacentItemIds(
            $currentItemId,
            $contentIds,
            $availabilityById,
        );
        $runtime->contentUrl = $runtime->runtimeSupported && $currentItem instanceof CLpItem
            ? $this->buildItemUrl(
                $currentItem,
                $itemViews[$currentItemId] ?? null,
                $course,
                $session,
                $group,
                $request,
            )
            : '';
        [$runtime->audioUrl, $runtime->audioTitle] = $currentItem instanceof CLpItem
            ? $this->buildItemAudio($currentItem, $course, $session, $group, $request)
            : ['', ''];
        $runtime->audioAutoplay = '' !== $runtime->audioUrl
            && 'readout_text' !== strtolower(trim((string) $currentItem?->getItemType()));
        $runtime->scorm = $currentItem instanceof CLpItem
            ? $this->scormRuntimeManager->buildRuntimeConfiguration(
                $lp,
                $currentItem,
                $itemViews[$currentItemId] ?? null,
                $user,
            )
            : [];
        $runtime->listUrl = $this->buildListUrl($course, $session, $group, $request);
        [$runtime->nextLearningPathUrl, $runtime->nextLearningPathTitle] = $this->buildNextLearningPathInfo(
            $lp,
            $course,
            $session,
            $group,
            $request,
            $user,
            $canEdit,
        );
        $runtime->legacyFallbackUrl = CLp::AICC_TYPE === $lp->getLpType()
            ? $this->buildLegacyFallbackUrl($lp, $course, $session, $group, $request)
            : '';
        $runtime->csrfToken = $this->csrfTokenManager->getToken(self::ACTION_TOKEN_INTENTION)->getValue();
        $runtime->items = $rows;

        return $runtime;
    }

    private function getLearningPath(int $lpId): CLp
    {
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path identifier.');
        }

        $lp = $this->lpRepository->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        return $lp;
    }

    private function assertRuntimeAccess(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        bool $canManage,
    ): void {
        $resourceNode = $lp->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('The learning path is not available in this context.');
        }

        $resourceLink = $this->getContextResourceLink($lp, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path is not linked to this context.');
        }

        if ($canManage) {
            return;
        }

        if (ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()) {
            throw new AccessDeniedHttpException('The learning path is not visible.');
        }

        if (!$this->advancedAccessHelper->isAllowed($course, $lp, $session, $user)) {
            throw new AccessDeniedHttpException('The learning path is not available for this user.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $publishedOn = $lp->getPublishedOn();
        $expiredOn = $lp->getExpiredOn();
        if (($publishedOn instanceof DateTimeInterface && $publishedOn > $now)
            || ($expiredOn instanceof DateTimeInterface && $expiredOn < $now)
        ) {
            throw new AccessDeniedHttpException('The learning path is not currently available.');
        }

        $category = $lp->getCategory();
        if (null !== $category) {
            $categoryLink = $this->getContextResourceLink($category, $course, $session, $group);
            if (!$categoryLink instanceof ResourceLink
                || ResourceLink::VISIBILITY_PUBLISHED !== $categoryLink->getVisibility()
            ) {
                throw new AccessDeniedHttpException('The learning path category is not visible.');
            }
        }

        $prerequisiteLpId = $lp->getPrerequisite();
        if ($prerequisiteLpId <= 0) {
            return;
        }

        $prerequisiteLp = $this->lpRepository->find($prerequisiteLpId);
        if (!$prerequisiteLp instanceof CLp) {
            throw new AccessDeniedHttpException('The learning path prerequisite is not available.');
        }

        $prerequisiteView = $this->findLatestView($prerequisiteLp, $course, $session, $user);
        if (!$prerequisiteView instanceof CLpView || 100 > (int) $prerequisiteView->getProgress()) {
            throw new AccessDeniedHttpException('The learning path prerequisite is not completed.');
        }
    }

    /** @return array<int, CLpItem> */
    private function getLearningPathItems(CLp $lp): array
    {
        /** @var array<int, CLpItem> $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $this->sortItemsDepthFirst($items);
    }

    /**
     * @param array<int, CLpItem> $items
     *
     * @return array<int, CLpItem>
     */
    private function sortItemsDepthFirst(array $items): array
    {
        $childrenByParent = [];
        foreach ($items as $item) {
            $parent = $item->getParent();
            $parentId = $parent instanceof CLpItem && 'root' !== $parent->getItemType()
                ? (int) $parent->getIid()
                : 0;
            $childrenByParent[$parentId][] = $item;
        }

        foreach ($childrenByParent as &$siblings) {
            usort(
                $siblings,
                static fn (CLpItem $left, CLpItem $right): int => [
                    (int) $left->getDisplayOrder(),
                    (int) $left->getIid(),
                ] <=> [
                    (int) $right->getDisplayOrder(),
                    (int) $right->getIid(),
                ],
            );
        }
        unset($siblings);

        $ordered = [];
        $visited = [];
        $appendChildren = function (int $parentId) use (&$appendChildren, &$childrenByParent, &$ordered, &$visited): void {
            foreach ($childrenByParent[$parentId] ?? [] as $item) {
                $itemId = (int) $item->getIid();
                if (isset($visited[$itemId])) {
                    continue;
                }

                $visited[$itemId] = true;
                $ordered[] = $item;
                $appendChildren($itemId);
            }
        };

        $appendChildren(0);
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            if (isset($visited[$itemId])) {
                continue;
            }

            $visited[$itemId] = true;
            $ordered[] = $item;
            $appendChildren($itemId);
        }

        return $ordered;
    }

    private function getItemDepth(CLpItem $item): int
    {
        $depth = 0;
        $parent = $item->getParent();
        $visited = [];
        while ($parent instanceof CLpItem && 'root' !== $parent->getItemType()) {
            $parentId = (int) $parent->getIid();
            if (isset($visited[$parentId])) {
                break;
            }

            $visited[$parentId] = true;
            ++$depth;
            $parent = $parent->getParent();
        }

        return min($depth, 5);
    }

    private function findLatestView(CLp $lp, Course $course, ?Session $session, User $user): ?CLpView
    {
        /** @var CLpView|null $view */
        $view = $this->entityManager->getRepository(CLpView::class)->findOneBy(
            [
                'lp' => $lp,
                'course' => $course,
                'session' => $session,
                'user' => $user,
            ],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );

        return $view;
    }

    /** @return array<int, CLpItemView> */
    private function getItemViewRows(?CLpView $view): array
    {
        if (!$view instanceof CLpView) {
            return [];
        }

        /** @var array<int, CLpItemView> $rows */
        $rows = $this->entityManager->getRepository(CLpItemView::class)->findBy(
            ['view' => $view],
            ['viewCount' => 'DESC', 'iid' => 'DESC'],
        );

        return $rows;
    }

    /**
     * @param array<int, CLpItemView> $rows
     *
     * @return array<int, CLpItemView>
     */
    private function indexLatestItemViews(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $itemId = (int) $row->getItem()->getIid();
            if (!isset($result[$itemId])) {
                $result[$itemId] = $row;
            }
        }

        return $result;
    }

    /**
     * @param array<int, CLpItemView> $rows
     */
    private function calculateTotalTime(array $rows, ?CLpView $view): int
    {
        $total = 0;
        $latestByItem = $this->indexLatestItemViews($rows);
        foreach ($rows as $row) {
            $total += max(0, (int) $row->getTotalTime());
        }

        $lastItemId = (int) ($view?->getLastItem() ?? 0);
        $currentView = $latestByItem[$lastItemId] ?? null;
        if ($currentView instanceof CLpItemView && $currentView->getStartTime() > 0) {
            $currentItem = $currentView->getItem();
            $scormTracksItsOwnTime = CLp::SCORM_TYPE === $currentItem->getLp()->getLpType()
                && 'sco' === strtolower(trim($currentItem->getItemType()));
            if (!$scormTracksItsOwnTime) {
                $elapsed = time() - (int) $currentView->getStartTime();
                if ($elapsed > 0 && $elapsed <= 3600) {
                    $total += $elapsed;
                }
            }
        }

        return $total;
    }

    /**
     * @param array<int, CLpItemView> $itemViews
     *
     * @return array<int, int>
     */
    private function getCompletedItemIds(array $itemViews): array
    {
        $ids = [];
        foreach ($itemViews as $itemId => $itemView) {
            $status = strtolower(trim($itemView->getStatus()));
            if (\in_array($status, self::COMPLETED_STATUSES, true)) {
                $ids[] = (int) $itemId;
            }
        }

        return $ids;
    }

    private function minimumTimeEnabled(): bool
    {
        return $this->isTruthySetting($this->settingsManager->getSetting('lp.lp_minimum_time', true));
    }

    /**
     * @param array<int, CLpItem>     $itemsById
     * @param array<int, CLpItemView> $itemViews
     */
    private function isItemAvailable(
        CLpItem $item,
        array $itemsById,
        array $itemViews,
        bool $bypassPrerequisites,
    ): bool {
        if ($bypassPrerequisites) {
            return true;
        }

        $prerequisite = trim((string) $item->getPrerequisite());
        if ('' === $prerequisite || '0' === $prerequisite || '_true_' === $prerequisite) {
            return true;
        }

        if ('_false_' === $prerequisite) {
            return false;
        }

        $prerequisiteId = $this->resolvePrerequisiteItemId($prerequisite, $itemsById);
        if ($prerequisiteId <= 0) {
            return false;
        }
        $prerequisiteItem = $itemsById[$prerequisiteId] ?? null;
        $prerequisiteView = $itemViews[$prerequisiteId] ?? null;
        if (!$prerequisiteItem instanceof CLpItem || !$prerequisiteView instanceof CLpItemView) {
            return false;
        }

        $status = strtolower(trim($prerequisiteView->getStatus()));
        if (!\in_array($status, self::COMPLETED_STATUSES, true)) {
            return false;
        }

        if (!\in_array($prerequisiteItem->getItemType(), ['quiz', 'hotpotatoes'], true)) {
            return true;
        }

        $minScore = (float) $item->getPrerequisiteMinScore();
        $maxScore = (float) $item->getPrerequisiteMaxScore();
        $score = (float) $prerequisiteView->getScore();

        return $score >= $minScore && $score <= $maxScore;
    }

    /** @param array<int, CLpItem> $itemsById */
    private function resolvePrerequisiteItemId(string $prerequisite, array $itemsById): int
    {
        if (ctype_digit($prerequisite)) {
            return (int) $prerequisite;
        }

        if (1 !== preg_match('/^[A-Za-z0-9_.:-]+$/', $prerequisite)) {
            return 0;
        }

        foreach ($itemsById as $itemId => $candidate) {
            if ($prerequisite === trim((string) $candidate->getRef())) {
                return (int) $itemId;
            }
        }

        return 0;
    }

    /**
     * @param array<int, CLpItem>     $itemsById
     * @param array<int, int>         $availableContentIds
     * @param array<int, CLpItemView> $itemViews
     */
    private function resolveCurrentItemId(
        int $requestedItemId,
        ?CLpView $latestView,
        array $itemsById,
        array $availableContentIds,
        array $itemViews,
        bool $bypassPrerequisites,
    ): int {
        if ($requestedItemId > 0) {
            $requestedItem = $itemsById[$requestedItemId] ?? null;
            if (!$requestedItem instanceof CLpItem || 'dir' === $requestedItem->getItemType()) {
                throw new NotFoundHttpException('Learning path item not found.');
            }
            if (!$this->isItemAvailable($requestedItem, $itemsById, $itemViews, $bypassPrerequisites)) {
                throw new AccessDeniedHttpException('The learning path item prerequisite is not completed.');
            }

            return $requestedItemId;
        }

        $lastItemId = (int) ($latestView?->getLastItem() ?? 0);
        if ($lastItemId > 0 && \in_array($lastItemId, $availableContentIds, true)) {
            return $lastItemId;
        }

        return $availableContentIds[0] ?? 0;
    }

    /**
     * @param array<int, int>  $contentIds
     * @param array<int, bool> $availabilityById
     *
     * @return array{0: int, 1: int}
     */
    private function getAdjacentItemIds(
        int $currentItemId,
        array $contentIds,
        array $availabilityById,
    ): array {
        $index = array_search($currentItemId, $contentIds, true);
        if (false === $index) {
            return [0, 0];
        }

        $previousId = 0;
        for ($position = $index - 1; $position >= 0; --$position) {
            $candidateId = $contentIds[$position] ?? 0;
            if ($availabilityById[$candidateId] ?? false) {
                $previousId = $candidateId;
                break;
            }
        }

        $nextId = 0;
        $count = \count($contentIds);
        for ($position = $index + 1; $position < $count; ++$position) {
            $candidateId = $contentIds[$position] ?? 0;
            if ($availabilityById[$candidateId] ?? false) {
                $nextId = $candidateId;
                break;
            }
        }

        return [$previousId, $nextId];
    }

    private function buildItemUrl(
        CLpItem $item,
        ?CLpItemView $itemView,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): string {
        $params = $this->buildContextParams($course, $session, $group, $request);
        $learningPathId = (int) $item->getLp()->getIid();
        $learningPathItemId = (int) $item->getIid();
        $params['lp_id'] = $learningPathId;
        $params['item_id'] = $learningPathItemId;
        $params['returnToLp'] = 1;
        $params['embedded'] = 1;
        $params['type'] = 'step';
        $type = strtolower(trim($item->getItemType()));

        if (CLp::SCORM_TYPE === $item->getLp()->getLpType()) {
            if ('sco' === $type && !$itemView instanceof CLpItemView) {
                return '';
            }
            if (!\in_array($type, ['sco', 'asset'], true)) {
                return '';
            }

            return $this->scormRuntimeManager->buildLaunchUrl(
                $item->getLp(),
                $item,
                $course,
                $session,
                $group,
                $params,
            );
        }

        $resourceId = ctype_digit((string) $item->getPath()) ? (int) $item->getPath() : 0;
        if ($resourceId <= 0) {
            return '';
        }

        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($courseNodeId <= 0) {
            return '';
        }

        if (\in_array($type, ['document', 'video', 'readout_text'], true)) {
            $document = $this->findContextResource(CDocument::class, $resourceId, $course, $session, $group);
            if (!$document instanceof CDocument) {
                return '';
            }

            $documentParams = $params;
            unset($documentParams['type']);

            return $this->resourceNodeRepository->getResourceFileUrl($document->getResourceNode(), $documentParams);
        }

        if ('final_item' === $type) {
            $document = $this->findContextResource(CDocument::class, $resourceId, $course, $session, $group);
            if (!$document instanceof CDocument) {
                return '';
            }

            $params['id'] = $learningPathItemId;

            return $this->appendQuery('/main/lp/lp_final_item.php', $params);
        }

        if ('quiz' === $type) {
            $quiz = $this->findContextResource(CQuiz::class, $resourceId, $course, $session, $group);
            if (!$quiz instanceof CQuiz) {
                return '';
            }
            $params['origin'] = 'learnpath';
            $params['lp_init'] = 1;
            $params['learnpath_id'] = $learningPathId;
            $params['learnpath_item_id'] = $learningPathItemId;
            $params['learnpath_item_view_id'] = (int) ($itemView?->getIid() ?? 0);
            $params['exerciseId'] = $resourceId;

            return $this->appendQuery('/main/exercise/overview.php', $params);
        }

        if ('link' === $type) {
            $link = $this->findContextResource(CLink::class, $resourceId, $course, $session, $group);
            if (!$link instanceof CLink) {
                return '';
            }
            $params['link_id'] = $resourceId;

            return $this->appendQuery('/main/link/link_goto.php', $params);
        }

        if (\in_array($type, ['student_publication', 'assignments'], true)) {
            $assignment = $this->findContextResource(
                CStudentPublication::class,
                $resourceId,
                $course,
                $session,
                $group,
            );
            if (!$assignment instanceof CStudentPublication) {
                return '';
            }

            $assignmentNodeId = (int) ($assignment->getResourceNode()?->getId() ?? 0);
            if ($assignmentNodeId <= 0) {
                return '';
            }

            return $this->appendQuery('/resources/assignment/'.$assignmentNodeId.'/submission/'.$resourceId, $params);
        }

        if ('forum' === $type) {
            $forum = $this->findContextResource(CForum::class, $resourceId, $course, $session, $group);
            if (!$forum instanceof CForum) {
                return '';
            }

            $params['lp_item_id'] = $learningPathItemId;

            return $this->appendQuery('/resources/forum/'.$courseNodeId.'/forum/'.$resourceId, $params);
        }

        if ('thread' === $type) {
            $thread = $this->findContextResource(CForumThread::class, $resourceId, $course, $session, $group);
            $forumId = (int) ($thread instanceof CForumThread ? $thread->getForum()?->getIid() : 0);
            if (!$thread instanceof CForumThread || $forumId <= 0) {
                return '';
            }

            $params['lp_item_id'] = $learningPathItemId;

            return $this->appendQuery(
                '/resources/forum/'.$courseNodeId.'/forum/'.$forumId.'/thread/'.$resourceId,
                $params,
            );
        }

        if ('survey' === $type) {
            $survey = $this->findContextResource(CSurvey::class, $resourceId, $course, $session, $group);
            if (!$survey instanceof CSurvey) {
                return '';
            }

            $params['lpItemId'] = $learningPathItemId;
            $params['invitationCode'] = 'auto';
            $params['isStudentView'] = 'true';

            return $this->appendQuery('/resources/survey/'.$courseNodeId.'/'.$resourceId.'/answer', $params);
        }

        return '';
    }

    /** @return array{0:string, 1:string} */
    private function buildItemAudio(
        CLpItem $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): array {
        $audioPath = trim((string) $item->getAudio());
        if ('' === $audioPath) {
            return ['', ''];
        }

        $normalizedAudioPath = $this->normalizeAudioReference($audioPath);
        $audioBasename = strtolower(basename($normalizedAudioPath));
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->andWhere(
                'node.path = :audioPath OR LOWER(files.originalName) = :audioBasename OR LOWER(node.title) = :audioBasename'
            )
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->setParameter('audioPath', $audioPath)
            ->setParameter('audioBasename', $audioBasename)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if ($group instanceof CGroup) {
            $queryBuilder
                ->andWhere('IDENTITY(links.group) = :groupId')
                ->setParameter('groupId', (int) $group->getIid(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.group IS NULL');
        }

        /** @var CDocument[] $documents */
        $documents = $queryBuilder->getQuery()->getResult();
        foreach ($documents as $document) {
            if (!$this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink
                || !$this->isAudioDocument($document)
                || !\in_array($normalizedAudioPath, $this->getAudioDocumentReferences($document), true)
            ) {
                continue;
            }

            try {
                $url = $this->resourceNodeRepository->getResourceFileUrl(
                    $document->getResourceNode(),
                    $this->buildContextParams($course, $session, $group, $request),
                );
            } catch (FileNotFoundException) {
                continue;
            }

            if ('' !== $url) {
                return [$url, $this->plainTitle($document->getTitle())];
            }
        }

        return ['', ''];
    }

    /** @return string[] */
    private function getAudioDocumentReferences(CDocument $document): array
    {
        $resourceNode = $document->getResourceNode();
        $resourceFile = $resourceNode?->getFirstResourceFile();
        $references = [];

        $nodePath = trim((string) $resourceNode?->getPath());
        if ('' !== $nodePath) {
            $references[] = $nodePath;
        }

        $fullPath = str_replace('\\', '/', trim($document->getFullPath()));
        $segments = array_values(array_filter(
            explode('/', trim($fullPath, '/')),
            static fn (string $part): bool => '' !== $part,
        ));
        foreach (array_keys($segments) as $index) {
            $relativePath = implode('/', array_slice($segments, $index));
            $references[] = $relativePath;
            $references[] = '/'.$relativePath;
            $references[] = 'document/'.$relativePath;
        }

        $originalName = trim((string) $resourceFile?->getOriginalName());
        if ('' !== $originalName) {
            $references[] = $originalName;
            $references[] = '/audio/'.$originalName;
            $references[] = 'audio/'.$originalName;
        }

        return array_values(array_unique(array_filter(
            array_map($this->normalizeAudioReference(...), $references),
            static fn (string $reference): bool => '' !== $reference,
        )));
    }

    private function normalizeAudioReference(string $reference): string
    {
        return strtolower(trim(str_replace('\\', '/', $reference)));
    }

    private function isAudioDocument(CDocument $document): bool
    {
        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();
        if (null === $resourceFile) {
            return false;
        }

        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
        if (str_starts_with($mimeType, 'audio/')) {
            return true;
        }

        $originalName = strtolower(trim((string) $resourceFile->getOriginalName()));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return \in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    /** @param class-string<AbstractResource> $entityClass */
    private function findContextResource(
        string $entityClass,
        int $resourceId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?AbstractResource {
        $resource = $this->entityManager->getRepository($entityClass)->find($resourceId);
        if (!$resource instanceof AbstractResource) {
            return null;
        }

        return $this->getContextResourceLink($resource, $course, $session, $group) instanceof ResourceLink
            ? $resource
            : null;
    }

    private function buildListUrl(Course $course, ?Session $session, ?CGroup $group, Request $request): string
    {
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);

        return $this->appendQuery(
            '/resources/lp/'.$courseNodeId,
            $this->buildContextParams($course, $session, $group, $request),
        );
    }

    private function buildLegacyFallbackUrl(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): string {
        $params = $this->buildContextParams($course, $session, $group, $request);
        $params['lp_id'] = (int) $lp->getIid();
        $params['action'] = 'view';

        return $this->appendQuery('/main/lp/lp_controller.php', $params);
    }

    /** @return array<string, int|string> */
    private function buildContextParams(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): array {
        return [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => (int) ($group?->getIid() ?? 0),
            'origin' => 'learnpath',
            'isStudentView' => $this->isStudentViewRequest($this->requestStack) ? 'true' : 'false',
            'gradebook' => $request->query->getInt('gradebook'),
        ];
    }

    /** @param array<string, int|string> $params */
    private function appendQuery(string $path, array $params): string
    {
        return $path.'?'.http_build_query($params);
    }

    /** @return array<string, mixed> */
    private function getDisplaySettings(): array
    {
        $settings = $this->settingsManager->getSetting('lp.lp_view_settings', true);
        if (!\is_array($settings)) {
            return [];
        }

        $display = $settings['display'] ?? [];

        return \is_array($display) ? $display : [];
    }

    /** @param array<string, mixed> $displaySettings */
    private function displaySettingEnabled(
        array $displaySettings,
        string $name,
        bool $default = false,
    ): bool {
        if (!array_key_exists($name, $displaySettings)) {
            return $default;
        }

        return $this->isTruthySetting($displaySettings[$name]);
    }

    private function getAttemptMode(CLp $lp): string
    {
        if ($lp->getSeriousgameMode() && $lp->getPreventReinit()) {
            return 'seriousgame';
        }

        return $lp->getPreventReinit() ? 'single' : 'multiple';
    }

    private function getMenuLocation(): string
    {
        $location = strtolower(trim((string) $this->settingsManager->getSetting('lp.lp_menu_location')));

        return \in_array($location, ['left', 'right'], true) ? $location : 'left';
    }

    private function buildReturnUrl(
        int $returnLink,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): string {
        return match ($returnLink) {
            1 => $this->buildListUrl($course, $session, $group, $request),
            2 => '/courses',
            3 => '/',
            4 => '/sessions',
            default => $this->appendQuery(
                '/course/'.(int) $course->getId().'/home',
                [
                    'sid' => (int) ($session?->getId() ?? 0),
                    'gid' => (int) ($group?->getIid() ?? 0),
                ],
            ),
        };
    }

    /** @return array{0: string, 1: string} */
    private function buildNextLearningPathInfo(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
        User $user,
        bool $canEdit,
    ): array {
        if (!$this->isTruthySetting($this->settingsManager->getSetting('lp.lp_enable_flow', true))) {
            return ['', ''];
        }

        $nextLpId = $lp->getNextLpId();
        if ($nextLpId <= 0 || $nextLpId === (int) $lp->getIid()) {
            return ['', ''];
        }

        $nextLp = $this->lpRepository->find($nextLpId);
        if (!$nextLp instanceof CLp) {
            return ['', ''];
        }

        $nextLink = $this->getContextResourceLink($nextLp, $course, $session, $group);
        if (!$nextLink instanceof ResourceLink) {
            return ['', ''];
        }

        if (!$canEdit) {
            if (ResourceLink::VISIBILITY_PUBLISHED !== $nextLink->getVisibility()
                || !$this->advancedAccessHelper->isAllowed($course, $nextLp, $session, $user)
            ) {
                return ['', ''];
            }

            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $publishedOn = $nextLp->getPublishedOn();
            $expiredOn = $nextLp->getExpiredOn();
            if (($publishedOn instanceof DateTimeInterface && $publishedOn > $now)
                || ($expiredOn instanceof DateTimeInterface && $expiredOn < $now)
            ) {
                return ['', ''];
            }
        }

        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($courseNodeId <= 0) {
            return ['', ''];
        }

        $params = $this->buildContextParams($course, $session, $group, $request);
        unset($params['item_id']);

        return [
            $this->appendQuery('/resources/lp/'.$courseNodeId.'/'.$nextLpId.'/runtime', $params),
            $this->plainTitle($nextLp->getTitle()),
        ];
    }

    private function buildReportingUrl(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
    ): string {
        $params = $this->buildContextParams($course, $session, $group, $request);
        $params['isStudentView'] = 'false';
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);

        return $this->appendQuery(
            '/resources/lp/'.$courseNodeId.'/'.(int) $lp->getIid().'/reporting',
            $params,
        );
    }

    private function plainTitle(string $title): string
    {
        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function isTruthySetting(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
