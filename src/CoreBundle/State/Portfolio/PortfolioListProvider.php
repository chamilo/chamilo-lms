<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<PortfolioList>
 */
final readonly class PortfolioListProvider implements ProviderInterface
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private PortfolioCommentRepository $commentRepository,
        private TagRepository $tagRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PortfolioList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);

        if ($course instanceof Course
            && !$this->canReadPortfolioCourse(
                $this->security,
                $this->userHelper,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            throw new AccessDeniedHttpException('You are not allowed to view Portfolio in this context.');
        }

        $requestedUser = $this->getPortfolioRequestedUser($this->entityManager, $request, $currentUser);
        if ($course instanceof Course
            && $requestedUser->getId() !== $currentUser->getId()
            && !$this->isPortfolioCourseUser($requestedUser, $course, $session)
        ) {
            throw new AccessDeniedHttpException('The requested Portfolio owner is outside the current course context.');
        }
        $listByUser = $request->query->getInt('user') > 0 || !$course instanceof Course;
        $advancedSharingEnabled = $course instanceof Course
            && $this->portfolioBoolean(
                $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
            );
        $showBaseCoursePosts = $session instanceof Session
            && $this->portfolioBoolean(
                $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
            );
        $canManageCourse = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);

        $filters = $this->resolveFilters($request);
        $categoryIds = $this->resolveCategoryIds($filters['categoryId'], $filters['subCategoryIds']);

        $items = $this->loadItems(
            $currentUser,
            $requestedUser,
            $course,
            $session,
            $listByUser,
            $advancedSharingEnabled,
            $showBaseCoursePosts,
            $canManageCourse,
            $filters,
            $categoryIds,
        );

        $authors = [];
        if ($course instanceof Course) {
            $authorItems = $this->loadItems(
                $currentUser,
                $requestedUser,
                $course,
                $session,
                false,
                $advancedSharingEnabled,
                $showBaseCoursePosts,
                $canManageCourse,
                $filters,
                $categoryIds,
            );
            $authors = $this->normalizeAuthors($authorItems);
        }

        $commentSearchItems = $items;
        if ('' !== $filters['date'] || '' !== $filters['text']) {
            $commentFilters = $filters;
            $commentFilters['date'] = '';
            $commentFilters['text'] = '';
            $commentSearchItems = $this->loadItems(
                $currentUser,
                $requestedUser,
                $course,
                $session,
                $listByUser,
                $advancedSharingEnabled,
                $showBaseCoursePosts,
                $canManageCourse,
                $commentFilters,
                $categoryIds,
            );
        }

        $itemIds = \array_values(\array_filter(\array_map(
            static fn (Portfolio $item): int => (int) ($item->getId() ?? 0),
            $items,
        )));
        $commentSearchItemIds = \array_values(\array_filter(\array_map(
            static fn (Portfolio $item): int => (int) ($item->getId() ?? 0),
            $commentSearchItems,
        )));
        $allCommentItemIds = \array_values(\array_unique(\array_merge($itemIds, $commentSearchItemIds)));
        $tagsByItem = $this->loadTagsByItem($itemIds);
        $commentsByItem = $this->loadVisibleCommentsByItem(
            $allCommentItemIds,
            $currentUser,
            $course,
            $session,
            $advancedSharingEnabled,
            $showBaseCoursePosts,
        );

        $result = new PortfolioList();
        $result->mode = $course instanceof Course ? 'course' : 'personal';
        $result->courseId = $course instanceof Course ? (int) $course->getId() : null;
        $result->sessionId = $session instanceof Session ? (int) $session->getId() : null;
        $result->currentUserId = (int) $currentUser->getId();
        $result->selectedUserId = $listByUser ? (int) $requestedUser->getId() : null;
        $result->selectedUser = $listByUser ? $this->normalizePortfolioUser($requestedUser) : null;
        $result->advancedSharingEnabled = $advancedSharingEnabled;
        $result->showBaseCoursePostsInSessions = $showBaseCoursePosts;
        $result->canCreate = $this->canCreatePortfolioItem(
            $this->security,
            $currentUser,
            $requestedUser,
            $course,
            $session,
        );
        $result->canViewDetails = $currentUser->getId() === $requestedUser->getId()
            || $canManageCourse
            || $this->security->isGranted('ROLE_ADMIN');
        $result->canManageCategories = $this->security->isGranted('ROLE_ADMIN');
        $result->canManageTags = $canManageCourse;
        $result->categories = $this->loadCategories();
        $result->tags = $this->loadAvailableTags($course, $session);
        $result->authors = $authors;
        $result->filters = $filters;
        $result->csrfToken = $this->csrfTokenManager->getToken('portfolio_action')->getValue();
        $result->maxScore = $course instanceof Course ? (float) $this->getCourseSettingInt('portfolio_max_score', $course) : 0.0;
        $result->canQualifyItems = $canManageCourse
            && $course instanceof Course
            && 1 === $this->getCourseSettingInt('qualify_portfolio_item', $course);
        $result->canQualifyComments = $canManageCourse
            && $course instanceof Course
            && 1 === $this->getCourseSettingInt('qualify_portfolio_comment', $course);

        foreach ($items as $item) {
            $itemId = (int) $item->getId();
            $result->items[] = $this->normalizeListItem(
                $item,
                $currentUser,
                $requestedUser,
                $course,
                $session,
                $canManageCourse,
                $advancedSharingEnabled,
                $tagsByItem[$itemId] ?? [],
                $commentsByItem[$itemId] ?? [],
            );
        }

        $result->commentMatches = $this->normalizeCommentMatches(
            $commentSearchItems,
            $commentsByItem,
            $filters,
        );
        $result->totalItems = \count($result->items);

        $this->registerPortfolioToolAccess();

        return $result;
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, int>      $categoryIds
     *
     * @return array<int, Portfolio>
     */
    private function loadItems(
        User $currentUser,
        User $requestedUser,
        ?Course $course,
        ?Session $session,
        bool $listByUser,
        bool $advancedSharingEnabled,
        bool $showBaseCoursePosts,
        bool $canManageCourse,
        array $filters,
        array $categoryIds,
    ): array {
        $queryBuilder = $this->createItemsQuery(
            $requestedUser,
            $course,
            $session,
            $listByUser,
            $filters,
            $categoryIds,
        );

        /** @var array<int, Portfolio> $candidates */
        $candidates = $queryBuilder->getQuery()->getResult();

        return \array_values(\array_filter(
            $candidates,
            fn (Portfolio $item): bool => $this->canViewPortfolioItem(
                $item,
                $currentUser,
                $course,
                $session,
                $showBaseCoursePosts,
                $advancedSharingEnabled,
                $canManageCourse,
            ),
        ));
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<int, int>      $categoryIds
     */
    private function createItemsQuery(
        User $requestedUser,
        ?Course $course,
        ?Session $session,
        bool $listByUser,
        array $filters,
        array $categoryIds,
    ): QueryBuilder {
        $queryBuilder = $this->portfolioRepository->createQueryBuilder('resource')
            ->select('DISTINCT resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->leftJoin('node.creator', 'creator')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->leftJoin('resource.category', 'category')
            ->addSelect('node', 'creator', 'links', 'files', 'category')
        ;

        if ($course instanceof Course) {
            $queryBuilder
                ->andWhere('links.course = :course')
                ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ;

            if ($session instanceof Session) {
                $queryBuilder
                    ->andWhere('(links.session = :session OR links.session IS NULL)')
                    ->setParameter('session', (int) $session->getId(), Types::INTEGER)
                ;
            } else {
                $queryBuilder->andWhere('links.session IS NULL');
            }

            if ($listByUser) {
                $queryBuilder
                    ->andWhere('node.creator = :requestedUser')
                    ->setParameter('requestedUser', (int) $requestedUser->getId(), Types::INTEGER)
                ;
            }
        } else {
            $queryBuilder
                ->andWhere('node.creator = :requestedUser')
                ->andWhere('resource.category IS NULL')
                ->setParameter('requestedUser', (int) $requestedUser->getId(), Types::INTEGER)
            ;
        }

        if ('' !== $filters['date']) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date'], new DateTimeZone('UTC'));
            if ($date instanceof DateTimeImmutable) {
                $queryBuilder
                    ->andWhere('node.createdAt >= :createdAfter')
                    ->setParameter('createdAfter', $date, Types::DATETIME_IMMUTABLE)
                ;
            }
        }

        if ('' !== $filters['text']) {
            $queryBuilder
                ->andWhere('(resource.title LIKE :searchText OR resource.content LIKE :searchText)')
                ->setParameter('searchText', '%'.$filters['text'].'%', Types::STRING)
            ;
        }

        if ([] !== $categoryIds) {
            $queryBuilder
                ->andWhere('category.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds, ArrayParameterType::INTEGER)
            ;
        }

        if (true === $filters['highlighted']) {
            $queryBuilder->andWhere('resource.isHighlighted = true');
        }

        if ([] !== $filters['tags']) {
            $queryBuilder
                ->innerJoin(ExtraFieldRelTag::class, 'tagRelation', Join::WITH, 'tagRelation.itemId = resource.id')
                ->innerJoin('tagRelation.field', 'tagField')
                ->innerJoin('tagRelation.tag', 'tagEntity')
                ->andWhere('tagField.itemType = :portfolioFieldType')
                ->andWhere('tagField.variable = :portfolioTagVariable')
                ->andWhere('tagEntity.id IN (:tagIds)')
                ->setParameter('portfolioFieldType', ExtraField::PORTFOLIO_TYPE, Types::INTEGER)
                ->setParameter('portfolioTagVariable', 'tags', Types::STRING)
                ->setParameter('tagIds', $filters['tags'], ArrayParameterType::INTEGER)
            ;
        }

        if ('alphabetical' === $filters['order']) {
            $queryBuilder
                ->orderBy('resource.title', 'ASC')
                ->addOrderBy('node.createdAt', 'DESC')
            ;
        } else {
            $queryBuilder->orderBy('node.createdAt', 'DESC');
        }

        return $queryBuilder;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $tags = [];
        foreach ($request->query->all('tags') as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId > 0) {
                $tags[$tagId] = $tagId;
            }
        }

        $order = $request->query->getString('order');
        if ($request->query->has('list_alphabetical')) {
            $order = 'alphabetical';
        }
        if (!\in_array($order, ['chronological', 'alphabetical'], true)) {
            $order = 'chronological';
        }

        $date = \trim($request->query->getString('date'));
        if ('' !== $date && !DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'))) {
            $date = '';
        }

        return [
            'date' => $date,
            'text' => \mb_substr(\trim($request->query->getString('text')), 0, 255),
            'tags' => \array_values($tags),
            'categoryId' => \max(0, $request->query->getInt('categoryId')),
            'subCategoryIds' => \mb_substr(\trim($request->query->getString('subCategoryIds')), 0, 1000),
            'order' => $order,
            'highlighted' => $request->query->getBoolean('highlighted')
                || $request->query->has('list_highlighted'),
            'user' => \max(0, $request->query->getInt('user')),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveCategoryIds(int $categoryId, string $subCategoryIds): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        /** @var PortfolioCategory|null $category */
        $category = $this->entityManager->getRepository(PortfolioCategory::class)->find($categoryId);
        if (!$category instanceof PortfolioCategory) {
            return [];
        }

        $childIds = [];
        foreach ($category->getChildren() as $child) {
            if ($child instanceof PortfolioCategory && null !== $child->getId()) {
                $childIds[] = (int) $child->getId();
            }
        }

        if ('all' === $subCategoryIds) {
            return \array_merge([$categoryId], $childIds);
        }

        if ('' === $subCategoryIds) {
            return [$categoryId];
        }

        $requestedChildIds = \array_values(\array_unique(\array_filter(\array_map(
            static fn (string $value): int => (int) \trim($value),
            \explode(',', $subCategoryIds),
        ), static fn (int $value): bool => $value > 0)));

        return \array_merge([$categoryId], \array_values(\array_intersect($childIds, $requestedChildIds)));
    }

    /**
     * @param array<int, int> $itemIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadTagsByItem(array $itemIds): array
    {
        if ([] === $itemIds) {
            return [];
        }

        /** @var array<int, ExtraFieldRelTag> $relations */
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'tag', 'field')
            ->from(ExtraFieldRelTag::class, 'relation')
            ->innerJoin('relation.tag', 'tag')
            ->innerJoin('relation.field', 'field')
            ->andWhere('relation.itemId IN (:itemIds)')
            ->andWhere('field.itemType = :portfolioFieldType')
            ->andWhere('field.variable = :portfolioTagVariable')
            ->setParameter('itemIds', $itemIds, ArrayParameterType::INTEGER)
            ->setParameter('portfolioFieldType', ExtraField::PORTFOLIO_TYPE, Types::INTEGER)
            ->setParameter('portfolioTagVariable', 'tags', Types::STRING)
            ->orderBy('tag.tag', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($relations as $relation) {
            $tag = $relation->getTag();
            if (!$tag instanceof Tag || null === $tag->getId()) {
                continue;
            }

            $result[(int) $relation->getItemId()][] = [
                'id' => (int) $tag->getId(),
                'label' => $tag->getTag(),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, int> $itemIds
     *
     * @return array<int, array<int, PortfolioComment>>
     */
    private function loadVisibleCommentsByItem(
        array $itemIds,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $advancedSharingEnabled,
        bool $showBaseCoursePosts,
    ): array {
        if ([] === $itemIds) {
            return [];
        }

        /** @var array<int, PortfolioComment> $comments */
        $comments = $this->commentRepository->createQueryBuilder('comment')
            ->select('DISTINCT comment')
            ->innerJoin('comment.resourceNode', 'node')
            ->leftJoin('node.creator', 'creator')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->innerJoin('comment.item', 'item')
            ->addSelect('node', 'creator', 'links', 'files', 'item')
            ->andWhere('item.id IN (:itemIds)')
            ->setParameter('itemIds', $itemIds, ArrayParameterType::INTEGER)
            ->orderBy('comment.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $result = [];
        foreach ($comments as $comment) {
            if (!$this->canViewPortfolioComment(
                $comment,
                $currentUser,
                $course,
                $session,
                $advancedSharingEnabled,
                $showBaseCoursePosts,
            )) {
                continue;
            }

            $result[(int) $comment->getItem()->getId()][] = $comment;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $tags
     * @param array<int, PortfolioComment>     $comments
     *
     * @return array<string, mixed>
     */
    private function normalizeListItem(
        Portfolio $item,
        User $currentUser,
        User $requestedUser,
        ?Course $course,
        ?Session $session,
        bool $canManageCourse,
        bool $advancedSharingEnabled,
        array $tags,
        array $comments,
    ): array {
        $node = $item->getResourceNode();
        $creator = $node->getCreator();
        $isOwner = $creator instanceof User && $creator->getId() === $currentUser->getId();
        $itemId = (int) $item->getId();
        $latestComments = \array_slice(\array_reverse($comments), 0, 3);

        return [
            'id' => $itemId,
            'title' => \trim(\strip_tags($item->getTitle())),
            'content' => $this->sanitizePortfolioHtml($item->getContent()),
            'excerpt' => $this->portfolioExcerpt($item->getContent()),
            'createdAt' => $this->formatPortfolioDate($node->getCreatedAt()),
            'updatedAt' => $node->getUpdatedAt() > $node->getCreatedAt()
                ? $this->formatPortfolioDate($node->getUpdatedAt())
                : null,
            'author' => $this->normalizePortfolioUser($creator),
            'category' => $this->normalizeItemCategory($item->getCategory()),
            'tags' => $tags,
            'visibility' => $item->getVisibility(),
            'recipientIds' => $this->getPortfolioRecipientIds($item, $course, $session),
            'isHighlighted' => $item->isHighlighted(),
            'score' => $item->getScore(),
            'commentsCount' => \count($comments),
            'lastComments' => \array_map(
                fn (PortfolioComment $comment): array => $this->normalizeCommentSummary($comment),
                $latestComments,
            ),
            'attachments' => $this->normalizePortfolioAttachments($item, $this->resourceNodeRepository, $isOwner),
            'context' => $this->getPortfolioResourceContext($item),
            'canEdit' => $isOwner,
            'canDelete' => $isOwner,
            'canChangeVisibility' => $isOwner,
            'canHighlight' => $canManageCourse,
            'canCopyToOwn' => !$isOwner,
            'canCopyToStudent' => $canManageCourse,
            'canQualify' => $canManageCourse
                && $course instanceof Course
                && 1 === $this->getCourseSettingInt('qualify_portfolio_item', $course),
            'canUseAsTemplate' => $isOwner,
            'advancedSharingEnabled' => $advancedSharingEnabled,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeItemCategory(?PortfolioCategory $category): ?array
    {
        if (!$category instanceof PortfolioCategory || null === $category->getId()) {
            return null;
        }

        return [
            'id' => (int) $category->getId(),
            'label' => $category->getTitle(),
            'parentId' => null !== $category->getParent()?->getId()
                ? (int) $category->getParent()->getId()
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCommentSummary(PortfolioComment $comment): array
    {
        return [
            'id' => (int) $comment->getId(),
            'author' => $this->normalizePortfolioUser($comment->getResourceNode()->getCreator()),
            'date' => $this->formatPortfolioDate($comment->getDate()),
            'excerpt' => $this->portfolioExcerpt($comment->getContent(), 190),
            'isImportant' => $comment->isImportant(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCategories(): array
    {
        $criteria = [];
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $criteria['isVisible'] = true;
        }

        /** @var array<int, PortfolioCategory> $categories */
        $categories = $this->entityManager->getRepository(PortfolioCategory::class)->findBy(
            $criteria,
            ['title' => 'ASC'],
        );

        return \array_values(\array_map(
            fn (PortfolioCategory $category): array => [
                'id' => (int) $category->getId(),
                'label' => $category->getTitle(),
                'description' => $this->portfolioExcerpt((string) $category->getDescription(), 250),
                'parentId' => null !== $category->getParent()?->getId()
                    ? (int) $category->getParent()->getId()
                    : null,
            ],
            $categories,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadAvailableTags(?Course $course, ?Session $session): array
    {
        if (!$course instanceof Course) {
            return [];
        }

        /** @var array<int, Tag> $tags */
        $tags = $this->tagRepository
            ->findForPortfolioInCourseQuery($course, $session)
            ->select('DISTINCT t')
            ->orderBy('t.tag', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return \array_values(\array_map(
            static fn (Tag $tag): array => [
                'id' => (int) $tag->getId(),
                'label' => $tag->getTag(),
            ],
            $tags,
        ));
    }

    /**
     * @param array<int, Portfolio> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAuthors(array $items): array
    {
        $authors = [];
        foreach ($items as $item) {
            $creator = $item->getResourceNode()->getCreator();
            if (!$creator instanceof User || null === $creator->getId()) {
                continue;
            }

            $authors[(int) $creator->getId()] = $this->normalizePortfolioUser($creator);
        }

        \uasort($authors, static fn (array $left, array $right): int => \strcasecmp(
            (string) ($left['fullName'] ?? ''),
            (string) ($right['fullName'] ?? ''),
        ));

        return \array_values($authors);
    }

    /**
     * @param array<int, Portfolio>                         $items
     * @param array<int, array<int, PortfolioComment>>     $commentsByItem
     * @param array<string, mixed>                         $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCommentMatches(array $items, array $commentsByItem, array $filters): array
    {
        if ('' === $filters['date'] && '' === $filters['text']) {
            return [];
        }

        $minimumDate = '' !== $filters['date']
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date'], new DateTimeZone('UTC'))
            : null;
        $searchText = \mb_strtolower($filters['text']);
        $matches = [];

        foreach ($items as $item) {
            foreach ($commentsByItem[(int) $item->getId()] ?? [] as $comment) {
                if ($minimumDate instanceof DateTimeImmutable && $comment->getDate() < $minimumDate) {
                    continue;
                }

                if ('' !== $searchText
                    && !\str_contains(\mb_strtolower(\strip_tags($comment->getContent())), $searchText)
                ) {
                    continue;
                }

                $matches[] = [
                    'id' => (int) $comment->getId(),
                    'itemId' => (int) $item->getId(),
                    'itemTitle' => \trim(\strip_tags($item->getTitle())),
                    'author' => $this->normalizePortfolioUser($comment->getResourceNode()->getCreator()),
                    'date' => $this->formatPortfolioDate($comment->getDate()),
                    'excerpt' => $this->portfolioExcerpt($comment->getContent(), 290),
                ];
            }
        }

        \usort($matches, static fn (array $left, array $right): int => \strcmp(
            (string) ($right['date'] ?? ''),
            (string) ($left['date'] ?? ''),
        ));

        return $matches;
    }

    private function getCourseSettingInt(string $variable, Course $course): int
    {
        if (!\function_exists('api_get_course_setting') || !\function_exists('api_get_course_info')) {
            return 0;
        }

        return \max(0, (int) \api_get_course_setting($variable, \api_get_course_info($course->getCode())));
    }
}
