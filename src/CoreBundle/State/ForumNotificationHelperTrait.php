<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait ForumNotificationHelperTrait
{
    private function getCurrentForumUser(Security $security): User
    {
        $user = $security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getRequestedSubscriptionState(array $data, bool $currentState): bool
    {
        if (\array_key_exists('subscribed', $data)) {
            return filter_var($data['subscribed'], FILTER_VALIDATE_BOOLEAN);
        }

        return !$currentState;
    }

    private function isSubscribedToForum(EntityManagerInterface $entityManager, Course $course, User $user, int $forumId): bool
    {
        return null !== $entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'forumId' => $forumId,
        ]);
    }

    private function isSubscribedToThread(EntityManagerInterface $entityManager, Course $course, User $user, int $threadId): bool
    {
        return null !== $entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'threadId' => $threadId,
        ]);
    }

    private function setForumSubscription(
        EntityManagerInterface $entityManager,
        Course $course,
        User $user,
        int $forumId,
        bool $subscribed,
    ): bool {
        $notification = $entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'forumId' => $forumId,
        ]);

        if ($subscribed) {
            if (!$notification instanceof CForumNotification) {
                $notification = (new CForumNotification())
                    ->setCId((int) $course->getId())
                    ->setUserId((int) $user->getId())
                    ->setForumId($forumId)
                ;
                $entityManager->persist($notification);
            }

            return true;
        }

        if ($notification instanceof CForumNotification) {
            $entityManager->remove($notification);
        }

        return false;
    }

    private function setThreadSubscription(
        EntityManagerInterface $entityManager,
        Course $course,
        User $user,
        int $threadId,
        bool $subscribed,
    ): bool {
        $notification = $entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'threadId' => $threadId,
        ]);

        if ($subscribed) {
            if (!$notification instanceof CForumNotification) {
                $notification = (new CForumNotification())
                    ->setCId((int) $course->getId())
                    ->setUserId((int) $user->getId())
                    ->setThreadId($threadId)
                ;
                $entityManager->persist($notification);
            }

            return true;
        }

        if ($notification instanceof CForumNotification) {
            $entityManager->remove($notification);
        }

        return false;
    }


    private function areForumPostNotificationsHidden(Course $course): bool
    {
        if (!\function_exists('api_get_course_setting')) {
            return false;
        }

        return 1 === (int) \api_get_course_setting('hide_forum_notifications', $course);
    }

    private function shouldStorePostNotification(Course $course, bool $requestedNotification): bool
    {
        return !$this->areForumPostNotificationsHidden($course) && $requestedNotification;
    }

    private function sendForumSubscriptionNotifications(
        EntityManagerInterface $entityManager,
        Request $request,
        Course $course,
        ?Session $session,
        CForum $forum,
        CForumThread $thread,
        CForumPost $post,
        User $author,
    ): int {
        if (!$this->canSendForumNotification($course, $session, $forum, $thread, $post)) {
            return 0;
        }

        $recipientIds = $this->getForumNotificationRecipientIds($entityManager, $course, $forum, $thread, $author);
        if ([] === $recipientIds || !class_exists(MessageManager::class)) {
            return 0;
        }

        $subject = 'New forum post: '.$forum->getTitle().' - '.$thread->getTitle();
        $content = $this->buildForumNotificationContent($request, $course, $session, $forum, $thread, $post, $author);
        $sentCount = 0;

        foreach ($recipientIds as $recipientId) {
            MessageManager::send_message_simple($recipientId, $subject, $content);
            ++$sentCount;
        }

        return $sentCount;
    }

    private function canSendForumNotification(
        Course $course,
        ?Session $session,
        CForum $forum,
        CForumThread $thread,
        CForumPost $post,
    ): bool {
        if (!$post->getVisible() || CForumPost::STATUS_VALIDATED !== ($post->getStatus() ?? CForumPost::STATUS_VALIDATED)) {
            return false;
        }

        if (!$forum->isVisible($course, $session) || !$thread->isVisible($course, $session)) {
            return false;
        }

        $category = $forum->getForumCategory();
        if ($category instanceof CForumCategory && !$category->isVisible($course, $session)) {
            return false;
        }

        return true;
    }

    /**
     * @return int[]
     */
    private function getForumNotificationRecipientIds(
        EntityManagerInterface $entityManager,
        Course $course,
        CForum $forum,
        CForumThread $thread,
        User $author,
    ): array {
        $recipientIds = [];
        $repository = $entityManager->getRepository(CForumNotification::class);
        $courseId = (int) $course->getId();

        foreach ($repository->findBy(['cId' => $courseId, 'forumId' => $forum->getIid()]) as $notification) {
            if ($notification instanceof CForumNotification) {
                $recipientIds[] = $notification->getUserId();
            }
        }

        foreach ($repository->findBy(['cId' => $courseId, 'threadId' => $thread->getIid()]) as $notification) {
            if ($notification instanceof CForumNotification) {
                $recipientIds[] = $notification->getUserId();
            }
        }

        $authorId = (int) $author->getId();
        $recipientIds = array_values(array_unique(array_map('intval', $recipientIds)));

        return array_values(array_filter(
            $recipientIds,
            static fn (int $recipientId): bool => 0 < $recipientId && $recipientId !== $authorId,
        ));
    }

    private function buildForumNotificationContent(
        Request $request,
        Course $course,
        ?Session $session,
        CForum $forum,
        CForumThread $thread,
        CForumPost $post,
        User $author,
    ): string {
        $postText = trim(strip_tags((string) $post->getPostText()));
        if (100 < mb_strlen($postText)) {
            $postText = mb_substr($postText, 0, 100).'...';
        }

        $threadUrl = $this->buildForumThreadUrl($request, $course, $session, $forum, $thread);
        $authorName = htmlspecialchars($author->getFullName(), ENT_QUOTES, 'UTF-8');
        $forumTitle = htmlspecialchars($forum->getTitle(), ENT_QUOTES, 'UTF-8');
        $threadTitle = htmlspecialchars($thread->getTitle(), ENT_QUOTES, 'UTF-8');
        $safeText = htmlspecialchars($postText, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($threadUrl, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '%s posted a new message in forum "%s", thread "%s".<br><br>%s<br><br><a href="%s">%s</a>',
            $authorName,
            $forumTitle,
            $threadTitle,
            $safeText,
            $safeUrl,
            $safeUrl,
        );
    }

    private function buildForumThreadUrl(
        Request $request,
        Course $course,
        ?Session $session,
        CForum $forum,
        CForumThread $thread,
    ): string {
        $parentNodeId = $forum->getResourceNode()?->getParent()?->getId() ?? $forum->getResourceNode()?->getId() ?? 0;
        $query = http_build_query([
            'cid' => $course->getId(),
            'sid' => $session?->getId() ?? 0,
            'gid' => $request->query->getInt('gid'),
        ]);

        return $request->getSchemeAndHttpHost().'/resources/forum/'.$parentNodeId.'/forum/'.$forum->getIid().'/thread/'.$thread->getIid().'?'.$query;
    }
}
