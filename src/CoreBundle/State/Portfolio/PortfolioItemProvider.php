<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioItem as PortfolioItemResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemViewedEvent;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @implements ProviderInterface<PortfolioItemResource>
 */
final readonly class PortfolioItemProvider implements ProviderInterface
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private PortfolioCommentRepository $commentRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PortfolioItemResource
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $itemId = (int) ($uriVariables['id'] ?? 0);
        if ($itemId <= 0) {
            throw new BadRequestHttpException('A valid portfolio item identifier is required.');
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

        /** @var Portfolio|null $item */
        $item = $this->portfolioRepository->createQueryBuilder('resource')
            ->select('resource', 'node', 'creator', 'links', 'files', 'category')
            ->innerJoin('resource.resourceNode', 'node')
            ->leftJoin('node.creator', 'creator')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->leftJoin('resource.category', 'category')
            ->andWhere('resource.id = :itemId')
            ->setParameter('itemId', $itemId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$item instanceof Portfolio) {
            throw new NotFoundHttpException('The requested portfolio item was not found.');
        }

        if (!$this->canViewPortfolioItem(
            $item,
            $currentUser,
            $course,
            $session,
            $showBaseCoursePosts,
            $advancedSharingEnabled,
            $canManageCourse,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view this portfolio item.');
        }

        if (!$course instanceof Course) {
            $creator = $item->getResourceNode()->getCreator();
            if (!$creator instanceof User) {
                throw new AccessDeniedHttpException('The portfolio item has no valid owner.');
            }

            if ($request->query->getInt('user') > 0 && $creator->getId() !== $requestedUser->getId()) {
                throw new AccessDeniedHttpException('The portfolio item does not belong to the requested owner.');
            }

            $requestedUser = $creator;
        }

        $comments = $this->loadVisibleComments(
            $item,
            $currentUser,
            $course,
            $session,
            $advancedSharingEnabled,
            $showBaseCoursePosts,
        );
        $tags = $this->loadItemTags($itemId);
        $node = $item->getResourceNode();
        $creator = $node->getCreator();
        $isOwner = $creator instanceof User && $creator->getId() === $currentUser->getId();

        $result = new PortfolioItemResource();
        $result->id = $itemId;
        $result->mode = $course instanceof Course ? 'course' : 'personal';
        $result->courseId = $course instanceof Course ? (int) $course->getId() : null;
        $result->sessionId = $session instanceof Session ? (int) $session->getId() : null;
        $result->advancedSharingEnabled = $advancedSharingEnabled;
        $result->csrfToken = $this->csrfTokenManager->getToken('portfolio_action')->getValue();
        $result->maxScore = $course instanceof Course ? (float) $this->getCourseSettingInt('portfolio_max_score', $course) : 0.0;
        $result->canQualifyItems = $canManageCourse
            && $course instanceof Course
            && 1 === $this->getCourseSettingInt('qualify_portfolio_item', $course);
        $result->canQualifyComments = $canManageCourse
            && $course instanceof Course
            && 1 === $this->getCourseSettingInt('qualify_portfolio_comment', $course);
        $result->canComment = !$session instanceof Session || Session::READ_ONLY !== $session->getVisibility();
        $result->commentTemplates = $this->loadCommentTemplates($currentUser);
        $result->recipientOptions = $course instanceof Course ? $this->loadCourseUsers($course, $session) : [];
        $result->item = [
            'id' => $itemId,
            'title' => \trim(\strip_tags($item->getTitle())),
            'content' => $this->sanitizePortfolioHtml($item->getContent()),
            'createdAt' => $this->formatPortfolioDate($node->getCreatedAt()),
            'updatedAt' => $node->getUpdatedAt() > $node->getCreatedAt()
                ? $this->formatPortfolioDate($node->getUpdatedAt())
                : null,
            'author' => $this->normalizePortfolioUser($creator),
            'category' => $this->normalizeCategory($item->getCategory()),
            'tags' => $tags,
            'visibility' => $item->getVisibility(),
            'recipientIds' => $this->getPortfolioRecipientIds($item, $course, $session),
            'isHighlighted' => $item->isHighlighted(),
            'score' => $item->getScore(),
            'commentsCount' => \count($comments),
            'attachments' => $this->normalizePortfolioAttachments($item, $this->resourceNodeRepository, $isOwner),
            'context' => $this->getPortfolioResourceContext($item),
            'canEdit' => $isOwner,
            'canDelete' => $isOwner,
            'canChangeVisibility' => $isOwner,
            'canHighlight' => $canManageCourse,
            'canCopyToOwn' => !$isOwner,
            'canCopyToStudent' => $canManageCourse,
            'canQualify' => $result->canQualifyItems,
            'canUseAsTemplate' => $isOwner,
        ];
        $result->comments = $this->buildCommentTree(
            $comments,
            $course,
            $session,
            $currentUser,
            $canManageCourse,
            $result->canQualifyComments,
            $result->canComment,
        );

        $this->eventDispatcher->dispatch(
            new PortfolioItemViewedEvent(['portfolio' => $item]),
            Events::PORTFOLIO_ITEM_VIEWED,
        );
        $this->registerPortfolioToolAccess();

        return $result;
    }

    /**
     * @return array<int, PortfolioComment>
     */
    private function loadVisibleComments(
        Portfolio $item,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $advancedSharingEnabled,
        bool $showBaseCoursePosts,
    ): array {
        $itemContext = $this->getPortfolioResourceContext($item);
        if ($session instanceof Session
            && $showBaseCoursePosts
            && null === $itemContext['sessionId']
            && !$item->isDuplicatedInSession($session)
        ) {
            return [];
        }

        /** @var array<int, PortfolioComment> $comments */
        $comments = $this->commentRepository->createQueryBuilder('comment')
            ->select('DISTINCT comment', 'node', 'creator', 'links', 'files')
            ->innerJoin('comment.resourceNode', 'node')
            ->leftJoin('node.creator', 'creator')
            ->leftJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFiles', 'files')
            ->andWhere('comment.item = :itemId')
            ->setParameter('itemId', (int) $item->getId(), Types::INTEGER)
            ->orderBy('node.level', 'ASC')
            ->addOrderBy('node.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return \array_values(\array_filter(
            $comments,
            fn (PortfolioComment $comment): bool => $this->canViewPortfolioComment(
                $comment,
                $currentUser,
                $course,
                $session,
                $advancedSharingEnabled,
                $showBaseCoursePosts,
            ),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadItemTags(int $itemId): array
    {
        /** @var array<int, ExtraFieldRelTag> $relations */
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'tag', 'field')
            ->from(ExtraFieldRelTag::class, 'relation')
            ->innerJoin('relation.tag', 'tag')
            ->innerJoin('relation.field', 'field')
            ->andWhere('relation.itemId = :itemId')
            ->andWhere('field.itemType = :portfolioFieldType')
            ->andWhere('field.variable = :portfolioTagVariable')
            ->setParameter('itemId', $itemId, Types::INTEGER)
            ->setParameter('portfolioFieldType', ExtraField::PORTFOLIO_TYPE, Types::INTEGER)
            ->setParameter('portfolioTagVariable', 'tags', Types::STRING)
            ->orderBy('tag.tag', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $tags = [];
        foreach ($relations as $relation) {
            $tag = $relation->getTag();
            if (!$tag instanceof Tag || null === $tag->getId()) {
                continue;
            }

            $tags[] = [
                'id' => (int) $tag->getId(),
                'label' => $tag->getTag(),
            ];
        }

        return $tags;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeCategory(?PortfolioCategory $category): ?array
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
     * @param array<int, PortfolioComment> $comments
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCommentTree(
        array $comments,
        ?Course $course,
        ?Session $session,
        User $currentUser,
        bool $canManageCourse,
        bool $canQualifyComments,
        bool $canReply,
    ): array {
        $rowsByNodeId = [];
        $parentIds = [];

        foreach ($comments as $comment) {
            $node = $comment->getResourceNode();
            $nodeId = (int) $node->getId();
            $creator = $node->getCreator();
            $isOwner = $creator instanceof User && $creator->getId() === $currentUser->getId();

            $rowsByNodeId[$nodeId] = [
                'id' => (int) $comment->getId(),
                'resourceNodeId' => $nodeId,
                'content' => $this->sanitizePortfolioHtml($comment->getContent()),
                'excerpt' => $this->portfolioExcerpt($comment->getContent(), 280),
                'date' => $this->formatPortfolioDate($comment->getDate()),
                'author' => $this->normalizePortfolioUser($creator),
                'isImportant' => $comment->isImportant(),
                'score' => $comment->getScore(),
                'visibility' => $comment->getVisibility(),
                'recipientIds' => $this->getPortfolioRecipientIds($comment, $course, $session),
                'attachments' => $this->normalizePortfolioAttachments($comment, $this->resourceNodeRepository, $isOwner),
                'canEdit' => $isOwner,
                'canDelete' => $isOwner,
                'canMarkImportant' => $canManageCourse,
                'canCopyToOwn' => !$isOwner,
                'canCopyToStudent' => $canManageCourse,
                'canQualify' => $canQualifyComments,
                'canUseAsTemplate' => $isOwner,
                'canReply' => $canReply,
                'children' => [],
            ];
            $parentIds[$nodeId] = (int) ($node->getParent()?->getId() ?? 0);
        }

        $roots = [];
        foreach ($rowsByNodeId as $nodeId => &$row) {
            $parentId = $parentIds[$nodeId] ?? 0;
            if ($parentId > 0 && isset($rowsByNodeId[$parentId])) {
                $rowsByNodeId[$parentId]['children'][] = &$row;
            } else {
                $roots[] = &$row;
            }
        }
        unset($row);

        return $roots;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCommentTemplates(User $user): array
    {
        return \array_values(\array_map(
            static fn (PortfolioComment $comment): array => [
                'id' => (int) $comment->getId(),
                'content' => $comment->getContent(),
            ],
            $this->commentRepository->findTemplatesByUser($user),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCourseUsers(Course $course, ?Session $session): array
    {
        if (!\class_exists(CourseManager::class)) {
            return [];
        }

        $rows = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
            null,
            null,
            null,
            false,
            false,
            false,
            [],
            [],
            [],
            true,
        );
        $users = [];
        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? $row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $user = $this->entityManager->getRepository(User::class)->find($id);
            if ($user instanceof User) {
                $users[] = $this->normalizePortfolioUser($user);
            }
        }
        \usort($users, static fn (array $left, array $right): int => \strcasecmp(
            (string) ($left['fullName'] ?? ''),
            (string) ($right['fullName'] ?? ''),
        ));

        return $users;
    }

    private function getCourseSettingInt(string $variable, Course $course): int
    {
        if (!\function_exists('api_get_course_setting') || !\function_exists('api_get_course_info')) {
            return 0;
        }

        return \max(0, (int) \api_get_course_setting($variable, \api_get_course_info($course->getCode())));
    }
}
