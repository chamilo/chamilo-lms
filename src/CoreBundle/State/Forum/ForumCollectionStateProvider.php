<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<array<string, mixed>>
 */
final class ForumCollectionStateProvider implements ProviderInterface
{
    use ForumCourseSettingHelperTrait;
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumRepository $forumRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getForums($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getForumsFromCurrentRequest(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getForums($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getForums(Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access forums.');

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $parentNode = $this->getParentNode($this->entityManager, $request);
        $showHidden = $this->canManageForumsInCurrentView($this->security, $request);
        $user = $this->getCurrentUser();
        $displayGroupForums = $this->shouldDisplayGroupForumsInGeneralTool($request);

        $queryBuilder = $this->forumRepository->getResourcesByCourse(
            $course,
            $session,
            $group,
            $parentNode,
            !$showHidden,
            true,
        );

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $forum) {
            if (!$forum instanceof CForum || !$this->canListForumWithCurrentSettings($forum, $request, $displayGroupForums)) {
                continue;
            }

            $items[] = $this->normalizeForum($forum, $course, $session, $user);
        }

        return $items;
    }

    private function shouldDisplayGroupForumsInGeneralTool(Request $request): bool
    {
        if ($request->query->getInt('gid') > 0) {
            return true;
        }

        return 'false' !== (string) $this->settingsManager->getSetting('forum.display_groups_forum_in_general_tool', true);
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function areForumPostNotificationsHidden(Course $course): bool
    {
        return $this->isCourseSettingEnabled($this->entityManager, $course, 'hide_forum_notifications');
    }

    private function isSubscribedToForum(Course $course, User $user, int $forumId): bool
    {
        return null !== $this->entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'forumId' => $forumId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeForum(CForum $forum, Course $course, ?Session $session, User $user): array
    {
        $category = $forum->getForumCategory();
        $canSubscribe = !$this->areForumPostNotificationsHidden($course);

        return [
            '@id' => '/api/forums/'.$forum->getIid(),
            '@type' => 'Forum',
            'iid' => $forum->getIid(),
            'title' => $forum->getTitle(),
            'forumComment' => $forum->getForumComment(),
            'forumImage' => $this->getForumImageUrl($forum),
            'forumThreads' => $forum->getForumThreads(),
            'forumPosts' => $forum->getForumPosts(),
            'forumCategory' => null === $category ? null : '/api/forum_categories/'.$category->getIid(),
            'allowAnonymous' => $forum->getAllowAnonymous(),
            'allowEdit' => $forum->getAllowEdit(),
            'approvalDirectPost' => $forum->getApprovalDirectPost(),
            'allowAttachments' => $forum->getAllowAttachments(),
            'allowNewThreads' => $forum->getAllowNewThreads(),
            'defaultView' => $forum->getDefaultView(),
            'startTime' => $this->formatDate($forum->getStartTime()),
            'endTime' => $this->formatDate($forum->getEndTime()),
            'availabilityStatus' => $this->getForumAvailabilityStatus($forum),
            'forumOfGroup' => $forum->getForumOfGroup(),
            'forumGroupPublicPrivate' => $forum->getForumGroupPublicPrivate(),
            'locked' => $forum->getLocked(),
            'moderated' => $forum->isModerated(),
            'forumVisible' => $forum->isVisible($course, $session),
            'position' => $forum->getResourceNode()?->getResourceLinkByContext($course, $session)?->getDisplayOrder()
                ?? $forum->getResourceNode()?->getResourceLinkByContext($course)?->getDisplayOrder()
                ?? 0,
            'subscribed' => $canSubscribe && $this->isSubscribedToForum($course, $user, (int) $forum->getIid()),
            'canSubscribe' => $canSubscribe,
        ];
    }

    private function getForumImageUrl(CForum $forum): string
    {
        return trim($forum->getForumImage());
    }
}
