<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiDiscussion;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\WikiHelper;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiDiscuss;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<WikiDiscussion>
 */
final readonly class WikiDiscussionProvider implements ProviderInterface
{
    private const int STUDENT_STATUS = 5;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private StudentViewHelper $studentViewHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private WikiPageRenderer $renderer,
        private WikiDiscussionScoreCalculator $scoreCalculator,
        private WikiHelper $wikiHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiDiscussion
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->wikiHelper->assertToolEnabled($course);
        $nodeId = $this->wikiHelper->assertRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->wikiHelper->assertSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->wikiHelper->assertGroupBelongsToContext($group, $course, $session);

        if (!$this->wikiHelper->canRead($course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to view Wiki discussions in this context.');
        }

        $pageId = isset($uriVariables['pageId']) ? (int) $uriVariables['pageId'] : 0;
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $latest = $this->wikiRepository->findLatestVersionInContext($courseId, $pageId, $groupId, $sessionId);

        if (!$latest instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki discussion was not found in the current context.');
        }

        $studentView = $this->studentViewHelper->isActive();
        $canManage = !$studentView && $this->wikiHelper->canManage(
            $course,
            $session,
            $group,
        );

        if (null !== $session && !$canManage) {
            throw new AccessDeniedHttpException('Wiki discussions in sessions are available to session editors only.');
        }

        $this->wikiHelper->assertPageVisible($latest, $canManage);

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $isWorkOwner = 2 === $latest->getAssignment()
            && $latest->getUserId() === (int) $currentUser->getId();
        $canSeeDiscussion = 1 === $latest->getVisibilityDisc() || $canManage || $isWorkOwner;

        if (!$canSeeDiscussion) {
            throw new AccessDeniedHttpException('This Wiki discussion is not visible in the current context.');
        }

        $comments = $this->entityManager->getRepository(CWikiDiscuss::class)->createQueryBuilder('d')
            ->andWhere('d.cId = :courseId')
            ->andWhere('d.publicationId = :pageId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('pageId', $pageId, Types::INTEGER)
            ->orderBy('d.iid', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        $users = $this->loadUsers($comments);
        $ratings = [];
        $items = [];

        foreach ($comments as $comment) {
            if (!$comment instanceof CWikiDiscuss) {
                continue;
            }

            $rating = $this->scoreCalculator->normalize($comment->getPScore());
            if (null !== $rating) {
                $ratings[] = $rating;
            }

            $author = $users[$comment->getUsercId()] ?? null;
            $items[] = [
                'iid' => $comment->getIid(),
                'authorId' => $comment->getUsercId() > 0 ? $comment->getUsercId() : null,
                'authorName' => $author instanceof User ? $author->getFullName() : 'Anonymous',
                'authorRole' => $author instanceof User && self::STUDENT_STATUS === $author->getStatus()
                    ? 'Learner'
                    : 'Teacher',
                'comment' => $comment->getComment(),
                'rating' => $rating,
                'createdAt' => $comment->getDtime()->format(DATE_ATOM),
            ];
        }

        $subscription = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.userId = :userId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('userId', (int) $currentUser->getId(), Types::INTEGER)
            ->setParameter('type', 'watchdisc:'.$latest->getReflink(), Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        $latestAuthor = $this->entityManager->getRepository(User::class)->find($latest->getUserId());
        $canComment = !$studentView && (1 === $latest->getAddlockDisc() || $canManage);
        $canRate = !$studentView && (1 === $latest->getRatinglockDisc() || $canManage);
        $canSubscribe = !$studentView;

        $discussion = new WikiDiscussion();
        $discussion->pageId = $pageId;
        $discussion->courseId = $courseId;
        $discussion->sessionId = $sessionId > 0 ? $sessionId : null;
        $discussion->groupId = $groupId > 0 ? $groupId : null;
        $discussion->nodeId = $nodeId;
        $discussion->reflink = $latest->getReflink();
        $discussion->title = $this->renderer->displayTitle($latest->getReflink(), $latest->getTitle());
        $discussion->latestAuthorName = $latestAuthor instanceof User ? $latestAuthor->getFullName() : '';
        $discussion->latestUpdatedAt = $latest->getDtime()?->format(DATE_ATOM);
        $discussion->visible = 1 === $latest->getVisibilityDisc();
        $discussion->commentsOpen = 1 === $latest->getAddlockDisc();
        $discussion->ratingsOpen = 1 === $latest->getRatinglockDisc();
        $discussion->subscribed = $subscription instanceof CWikiMailcue;
        $discussion->canManage = $canManage;
        $discussion->canComment = $canComment;
        $discussion->canRate = $canRate;
        $discussion->canSubscribe = $canSubscribe;
        $discussion->commentCount = \count($items);
        $discussion->scoredCommentCount = \count($ratings);
        $discussion->averageRating = $this->scoreCalculator->average($ratings);
        $discussion->comments = $items;

        return $discussion;
    }

    /**
     * @param array<int, mixed> $comments
     *
     * @return array<int, User>
     */
    private function loadUsers(array $comments): array
    {
        $userIds = [];

        foreach ($comments as $comment) {
            if ($comment instanceof CWikiDiscuss && $comment->getUsercId() > 0) {
                $userIds[$comment->getUsercId()] = $comment->getUsercId();
            }
        }

        if ([] === $userIds) {
            return [];
        }

        $users = $this->entityManager->getRepository(User::class)->findBy(['id' => array_values($userIds)]);
        $indexed = [];

        foreach ($users as $user) {
            if ($user instanceof User && null !== $user->getId()) {
                $indexed[(int) $user->getId()] = $user;
            }
        }

        return $indexed;
    }
}
