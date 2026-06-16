<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumSearchResult;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ForumSearchResult>
 */
final class ForumSearchStateProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    private const MIN_SEARCH_LENGTH = 3;
    private const MAX_TERMS = 8;
    private const MAX_RESULTS_PER_TYPE = 40;
    private const SNIPPET_LENGTH = 180;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumSearchResult
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return ForumSearchResult::fromArray([
                'query' => '',
                'items' => [],
                'totalItems' => 0,
                'minLength' => self::MIN_SEARCH_LENGTH,
            ]);
        }

        return ForumSearchResult::fromArray($this->search($request));
    }

    /**
     * @return array<string, mixed>
     */
    public function search(Request $request): array
    {
        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            && !$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            && !$this->security->isGranted('ROLE_ADMIN')
        ) {
            throw new AccessDeniedHttpException('You are not allowed to search forums.');
        }

        $query = trim((string) $request->query->get('q', $request->query->get('search', '')));
        if (mb_strlen($query) < self::MIN_SEARCH_LENGTH) {
            return [
                'query' => $query,
                'items' => [],
                'totalItems' => 0,
                'minLength' => self::MIN_SEARCH_LENGTH,
            ];
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $showHidden = $this->canManageForumsInCurrentView($this->security, $request);
        $displayGroupForums = $this->shouldDisplayGroupForumsInGeneralTool($request);
        $terms = $this->getSearchTerms($query);
        if ([] === $terms) {
            throw new BadRequestHttpException('Invalid search query.');
        }

        $items = [
            ...$this->searchForums($course, $session, $terms, $showHidden, $displayGroupForums, $request),
            ...$this->searchThreads($course, $session, $terms, $showHidden, $displayGroupForums, $request),
            ...$this->searchPosts($course, $session, $terms, $showHidden, $displayGroupForums, $request),
        ];

        usort(
            $items,
            static fn (array $left, array $right): int => strcmp((string) $right['date'], (string) $left['date']),
        );

        return [
            'query' => $query,
            'items' => $items,
            'totalItems' => \count($items),
            'minLength' => self::MIN_SEARCH_LENGTH,
        ];
    }

    private function shouldDisplayGroupForumsInGeneralTool(Request $request): bool
    {
        if ($request->query->getInt('gid') > 0) {
            return true;
        }

        return 'false' !== (string) $this->settingsManager->getSetting('forum.display_groups_forum_in_general_tool', true);
    }

    private function canListForumWithCurrentSettings(CForum $forum, Request $request, bool $displayGroupForums): bool
    {
        if ($displayGroupForums || $request->query->getInt('gid') > 0) {
            return true;
        }

        return 0 === (int) $forum->getForumOfGroup();
    }

    /**
     * @return string[]
     */
    private function getSearchTerms(string $query): array
    {
        $terms = preg_split('/\s+/', $query) ?: [];
        $terms = array_values(array_filter(array_map('trim', $terms), static fn (string $term): bool => '' !== $term));

        return \array_slice($terms, 0, self::MAX_TERMS);
    }

    /**
     * @param string[] $terms
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchForums(
        Course $course,
        ?Session $session,
        array $terms,
        bool $showHidden,
        bool $displayGroupForums,
        Request $request,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(CForum::class, 'f')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
        ;

        foreach ($terms as $index => $term) {
            $parameterName = 'forumTerm'.$index;
            $queryBuilder
                ->andWhere('(LOWER(f.title) LIKE :'.$parameterName.' OR LOWER(f.forumComment) LIKE :'.$parameterName.')')
                ->setParameter($parameterName, '%'.mb_strtolower($term).'%', Types::STRING)
            ;
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $forum) {
            if (!$forum instanceof CForum
                || !$this->canListForumWithCurrentSettings($forum, $request, $displayGroupForums)
                || !$this->canReadForum($forum, $course, $session, $showHidden)
            ) {
                continue;
            }

            $items[] = [
                'type' => 'forum',
                'typeLabel' => 'Forum',
                'forumId' => $forum->getIid(),
                'threadId' => null,
                'postId' => null,
                'title' => $forum->getTitle(),
                'forumTitle' => $forum->getTitle(),
                'threadTitle' => null,
                'author' => null,
                'date' => null,
                'snippet' => $this->buildSnippet($forum->getForumComment()),
                'hidden' => !$this->isForumResourceVisible($forum, $course, $session),
                'pending' => false,
            ];
        }

        return $items;
    }

    /**
     * @param string[] $terms
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchThreads(
        Course $course,
        ?Session $session,
        array $terms,
        bool $showHidden,
        bool $displayGroupForums,
        Request $request,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(CForumThread::class, 't')
            ->orderBy('t.threadDate', 'DESC')
            ->addOrderBy('t.iid', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
        ;

        foreach ($terms as $index => $term) {
            $parameterName = 'threadTerm'.$index;
            $queryBuilder
                ->andWhere('LOWER(t.title) LIKE :'.$parameterName)
                ->setParameter($parameterName, '%'.mb_strtolower($term).'%', Types::STRING)
            ;
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $thread) {
            if (!$thread instanceof CForumThread || !$this->canReadThread($thread, $course, $session, $showHidden)) {
                continue;
            }

            $forum = $thread->getForum();
            if (!$forum instanceof CForum || !$this->canListForumWithCurrentSettings($forum, $request, $displayGroupForums)) {
                continue;
            }

            $items[] = [
                'type' => 'thread',
                'typeLabel' => 'Thread',
                'forumId' => $forum->getIid(),
                'threadId' => $thread->getIid(),
                'postId' => null,
                'title' => $thread->getTitle(),
                'forumTitle' => $forum->getTitle(),
                'threadTitle' => $thread->getTitle(),
                'author' => $thread->getPosterFullName(),
                'date' => $this->formatDate($thread->getThreadDate()),
                'snippet' => '',
                'hidden' => !$this->isForumResourceVisible($thread, $course, $session),
                'pending' => !$showHidden ? false : 0 === $this->countVisiblePosts($thread),
            ];
        }

        return $items;
    }

    /**
     * @param string[] $terms
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchPosts(
        Course $course,
        ?Session $session,
        array $terms,
        bool $showHidden,
        bool $displayGroupForums,
        Request $request,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CForumPost::class, 'p')
            ->orderBy('p.postDate', 'DESC')
            ->addOrderBy('p.iid', 'DESC')
            ->setMaxResults(self::MAX_RESULTS_PER_TYPE)
        ;

        foreach ($terms as $index => $term) {
            $parameterName = 'postTerm'.$index;
            $queryBuilder
                ->andWhere('(LOWER(p.title) LIKE :'.$parameterName.' OR LOWER(p.postText) LIKE :'.$parameterName.')')
                ->setParameter($parameterName, '%'.mb_strtolower($term).'%', Types::STRING)
            ;
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $post) {
            if (!$post instanceof CForumPost || !$this->canReadPost($post, $course, $session, $showHidden)) {
                continue;
            }

            $forum = $post->getForum();
            $thread = $post->getThread();
            if (!$forum instanceof CForum
                || !$thread instanceof CForumThread
                || !$this->canListForumWithCurrentSettings($forum, $request, $displayGroupForums)
            ) {
                continue;
            }

            $status = $post->getStatus() ?? ($post->getVisible() ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION);

            $items[] = [
                'type' => 'post',
                'typeLabel' => 'Post',
                'forumId' => $forum->getIid(),
                'threadId' => $thread->getIid(),
                'postId' => $post->getIid(),
                'title' => $post->getTitle(),
                'forumTitle' => $forum->getTitle(),
                'threadTitle' => $thread->getTitle(),
                'author' => $post->getPosterFullName(),
                'date' => $this->formatDate($post->getPostDate()),
                'snippet' => $this->buildSnippet($post->getPostText()),
                'hidden' => !$post->getVisible(),
                'pending' => CForumPost::STATUS_VALIDATED !== $status,
            ];
        }

        return $items;
    }

    private function canReadForum(CForum $forum, Course $course, ?Session $session, bool $showHidden): bool
    {
        if (!$this->resourceBelongsToContext($forum, $course, $session)) {
            return false;
        }

        $resourceNode = $forum->getResourceNode();
        if (!$showHidden && (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode))) {
            return false;
        }

        $category = $forum->getForumCategory();
        if ($category instanceof CForumCategory) {
            if (!$this->resourceBelongsToContext($category, $course, $session)) {
                return false;
            }

            $categoryNode = $category->getResourceNode();
            if (!$showHidden && null !== $categoryNode && !$this->security->isGranted('VIEW', $categoryNode)) {
                return false;
            }

            if (!$showHidden && !$this->isForumResourceVisible($category, $course, $session)) {
                return false;
            }
        }

        return $showHidden || $this->isForumResourceVisible($forum, $course, $session);
    }

    private function canReadThread(
        CForumThread $thread,
        Course $course,
        ?Session $session,
        bool $showHidden,
    ): bool {
        $forum = $thread->getForum();
        if (!$forum instanceof CForum || !$this->canReadForum($forum, $course, $session, $showHidden)) {
            return false;
        }

        if (!$this->resourceBelongsToContext($thread, $course, $session)) {
            return false;
        }

        $resourceNode = $thread->getResourceNode();
        if (!$showHidden && (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode))) {
            return false;
        }

        if (!$showHidden && !$this->isForumResourceVisible($thread, $course, $session)) {
            return false;
        }

        return $showHidden || $this->countVisiblePosts($thread) > 0;
    }

    private function canReadPost(
        CForumPost $post,
        Course $course,
        ?Session $session,
        bool $showHidden,
    ): bool {
        $thread = $post->getThread();
        if (!$thread instanceof CForumThread || !$this->canReadThread($thread, $course, $session, $showHidden)) {
            return false;
        }

        if ($showHidden) {
            return true;
        }

        $status = $post->getStatus() ?? ($post->getVisible() ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION);

        return $post->getVisible() && CForumPost::STATUS_VALIDATED === $status;
    }

    private function resourceBelongsToContext(AbstractResource $resource, Course $course, ?Session $session): bool
    {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        if (null !== $resourceNode->getResourceLinkByContext($course, $session)) {
            return true;
        }

        if (null !== $session && null !== $resourceNode->getResourceLinkByContext($course)) {
            return true;
        }

        return false;
    }

    private function countVisiblePosts(CForumThread $thread): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('p.visible = :visible')
            ->andWhere('(p.status = :validatedStatus OR p.status IS NULL)')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('visible', true, Types::BOOLEAN)
            ->setParameter('validatedStatus', CForumPost::STATUS_VALIDATED, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function buildSnippet(?string $value): string
    {
        $snippet = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)) ?? '');
        if (mb_strlen($snippet) <= self::SNIPPET_LENGTH) {
            return $snippet;
        }

        return mb_substr($snippet, 0, self::SNIPPET_LENGTH).'...';
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format(DateTimeInterface::ATOM);
    }
}
