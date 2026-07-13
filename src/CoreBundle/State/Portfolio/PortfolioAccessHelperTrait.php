<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Security as LegacySecurity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;
use UserManager;

use const COURSEMANAGERLOWSECURITY;
use const DATE_ATOM;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

trait PortfolioAccessHelperTrait
{
    private function getPortfolioCurrentUser(UserHelper $userHelper): User
    {
        $user = $userHelper->getCurrent();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $user;
    }

    private function getPortfolioCourse(EntityManagerInterface $entityManager, Request $request): ?Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            return null;
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getPortfolioSession(
        EntityManagerInterface $entityManager,
        Request $request,
        ?Course $course,
    ): ?Session {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('A session requires a course context.');
        }

        $session = $entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        if (!$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not contain the current course.');
        }

        return $session;
    }

    private function getPortfolioRequestedUser(
        EntityManagerInterface $entityManager,
        Request $request,
        User $currentUser,
    ): User {
        $userId = $request->query->getInt('user');
        if ($userId <= 0 || $userId === $currentUser->getId()) {
            return $currentUser;
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new BadRequestHttpException('The requested portfolio owner was not found.');
        }

        return $user;
    }

    private function canReadPortfolioCourse(
        Security $security,
        UserHelper $userHelper,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN') || $security->isGranted('ROLE_HR')) {
            return true;
        }

        $user = $userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isSessionAdmin()) {
            return $this->portfolioBoolean(
                $settingsManager->getSetting('session.session_admins_access_all_content', true),
            );
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($session instanceof Session) {
            return $isCourseTeacher
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserInCourse($user, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
        }

        return $isCourseTeacher
            || $security->isGranted(CourseVoter::VIEW, $course)
            || $security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    private function canManagePortfolioCourse(
        Security $security,
        User $currentUser,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($course->hasUserAsTeacher($currentUser)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
        ) {
            return true;
        }

        return $session instanceof Session
            && ($session->hasUserAsGeneralCoach($currentUser)
                || $session->hasCourseCoachInCourse($currentUser, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER'));
    }

    private function canCreatePortfolioItem(
        Security $security,
        User $currentUser,
        User $requestedUser,
        ?Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($currentUser->getId() !== $requestedUser->getId()) {
            return false;
        }

        if (!$course instanceof Course) {
            return true;
        }

        if ($session instanceof Session && Session::READ_ONLY === $session->getVisibility()) {
            return false;
        }

        return true;
    }

    private function isPortfolioCourseUser(User $user, Course $course, ?Session $session): bool
    {
        if ($session instanceof Session) {
            return $session->hasUserInCourse($user, $course)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserAsGeneralCoach($user);
        }

        return $course->hasSubscriptionByUser($user) || $course->hasUserAsTeacher($user);
    }

    private function itemBelongsToCourseContext(
        Portfolio $item,
        Course $course,
        ?Session $session,
        bool $showBaseCoursePosts,
    ): bool {
        $hasBaseLink = false;

        foreach ($item->getResourceNode()->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $linkCourse = $link->getCourse();
            if (!$linkCourse instanceof Course || $linkCourse->getId() !== $course->getId()) {
                continue;
            }

            $linkSession = $link->getSession();
            if (!$session instanceof Session) {
                return null === $linkSession;
            }

            if ($linkSession instanceof Session && $linkSession->getId() === $session->getId()) {
                return true;
            }

            if (null === $linkSession) {
                $hasBaseLink = true;
            }
        }

        if (!$session instanceof Session || !$showBaseCoursePosts || !$hasBaseLink) {
            return false;
        }

        return !$item->isDuplicatedInSession($session);
    }

    private function canViewPortfolioItem(
        Portfolio $item,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $showBaseCoursePosts,
        bool $advancedSharingEnabled,
        bool $canManageCourse,
    ): bool {
        if ($course instanceof Course
            && !$this->itemBelongsToCourseContext($item, $course, $session, $showBaseCoursePosts)
        ) {
            return false;
        }

        $creator = $item->getResourceNode()->getCreator();
        if ($creator instanceof User && $creator->getId() === $currentUser->getId()) {
            return true;
        }

        if (!$course instanceof Course) {
            return Portfolio::VISIBILITY_VISIBLE === $item->getVisibility();
        }

        return match ($item->getVisibility()) {
            Portfolio::VISIBILITY_VISIBLE => true,
            Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER => $canManageCourse,
            Portfolio::VISIBILITY_PER_USER => $advancedSharingEnabled && $this->hasPortfolioUserLink(
                $item,
                $course,
                $session,
                $currentUser,
                $showBaseCoursePosts,
            ),
            default => false,
        };
    }

    private function canViewPortfolioComment(
        PortfolioComment $comment,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $advancedSharingEnabled,
        bool $showBaseCoursePosts,
    ): bool {
        $creator = $comment->getResourceNode()->getCreator();
        if ($creator instanceof User && $creator->getId() === $currentUser->getId()) {
            return true;
        }

        if (PortfolioComment::VISIBILITY_VISIBLE === $comment->getVisibility()) {
            return true;
        }

        if (!$advancedSharingEnabled || !$course instanceof Course
            || PortfolioComment::VISIBILITY_PER_USER !== $comment->getVisibility()
        ) {
            return false;
        }

        return $this->hasPortfolioUserLink(
            $comment,
            $course,
            $session,
            $currentUser,
            $showBaseCoursePosts,
        );
    }

    private function hasPortfolioUserLink(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        User $user,
        bool $showBaseCoursePosts,
    ): bool {
        foreach ($resource->getResourceNode()->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            if ($link->getCourse()?->getId() !== $course->getId()
                || $link->getUser()?->getId() !== $user->getId()
            ) {
                continue;
            }

            $linkSession = $link->getSession();
            if (!$session instanceof Session && null === $linkSession) {
                return true;
            }

            if ($session instanceof Session
                && (($linkSession instanceof Session && $linkSession->getId() === $session->getId())
                    || ($showBaseCoursePosts && null === $linkSession))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePortfolioUser(?User $user): array
    {
        if (!$user instanceof User || null === $user->getId()) {
            return [
                'id' => 0,
                'fullName' => '',
                'username' => '',
                'imageUrl' => '',
            ];
        }

        $imageUrl = '';
        if (\class_exists(UserManager::class)) {
            try {
                $imageUrl = (string) UserManager::getUserPicture((int) $user->getId());
            } catch (Throwable) {
                $imageUrl = '';
            }
        }

        return [
            'id' => (int) $user->getId(),
            'fullName' => $user->getFullName(),
            'username' => $user->getUsername(),
            'imageUrl' => $imageUrl,
        ];
    }

    private function sanitizePortfolioHtml(string $content): string
    {
        if (\class_exists(LegacySecurity::class)) {
            if (\defined('COURSEMANAGERLOWSECURITY')) {
                return (string) LegacySecurity::remove_XSS($content, COURSEMANAGERLOWSECURITY);
            }

            return (string) LegacySecurity::remove_XSS($content);
        }

        return \htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function portfolioExcerpt(string $content, int $length = 380): string
    {
        $plain = \html_entity_decode(
            \strip_tags($content),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plain = \trim(\preg_replace('/\s+/u', ' ', $plain) ?? '');

        if (\mb_strlen($plain) <= $length) {
            return $plain;
        }

        return \rtrim(\mb_substr($plain, 0, $length - 1)).'…';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizePortfolioAttachments(
        AbstractResource $resource,
        ResourceNodeRepository $resourceNodeRepository,
        bool $canDelete = false,
    ): array {
        $node = $resource->getResourceNode();
        $attachments = [];

        foreach ($node->getResourceFiles() as $attachment) {
            if (!$attachment instanceof ResourceFile || null === $attachment->getId()) {
                continue;
            }

            $attachments[] = [
                'id' => (int) $attachment->getId(),
                'filename' => (string) ($attachment->getOriginalName() ?: $attachment->getTitle() ?: 'attachment'),
                'description' => $attachment->getDescription(),
                'size' => (int) ($attachment->getSize() ?? 0),
                'mimeType' => (string) ($attachment->getMimeType() ?? ''),
                'downloadUrl' => $resourceNodeRepository->getResourceFileUrl(
                    $node,
                    ['mode' => 'download'],
                    null,
                    $attachment,
                ),
                'canDelete' => $canDelete,
            ];
        }

        return $attachments;
    }

    /**
     * @return array<int, int>
     */
    private function getPortfolioRecipientIds(
        AbstractResource $resource,
        ?Course $course,
        ?Session $session,
    ): array {
        if (!$course instanceof Course) {
            return [];
        }

        $ids = [];
        foreach ($resource->getResourceNode()->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }
            $user = $link->getUser();
            if (!$user instanceof User || $link->getCourse()?->getId() !== $course->getId()) {
                continue;
            }
            $linkSession = $link->getSession();
            if (($session instanceof Session && $linkSession?->getId() !== $session->getId())
                || (!$session instanceof Session && null !== $linkSession)
            ) {
                continue;
            }
            $ids[] = (int) $user->getId();
        }

        return \array_values(\array_unique($ids));
    }

    /**
     * @return array{courseId: int|null, courseTitle: string, sessionId: int|null, sessionTitle: string}
     */
    private function getPortfolioResourceContext(AbstractResource $resource): array
    {
        foreach ($resource->getResourceNode()->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $course = $link->getCourse();
            if (!$course instanceof Course) {
                continue;
            }

            $session = $link->getSession();

            return [
                'courseId' => (int) $course->getId(),
                'courseTitle' => $course->getTitle(),
                'sessionId' => $session instanceof Session ? (int) $session->getId() : null,
                'sessionTitle' => $session instanceof Session ? $session->getTitle() : '',
            ];
        }

        return [
            'courseId' => null,
            'courseTitle' => '',
            'sessionId' => null,
            'sessionTitle' => '',
        ];
    }

    private function portfolioBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \in_array(\strtolower(\trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function formatPortfolioDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    private function registerPortfolioToolAccess(): void
    {
        if (!\class_exists(Event::class) || !\defined('TOOL_PORTFOLIO')) {
            return;
        }

        try {
            Event::event_access_tool((string) \constant('TOOL_PORTFOLIO'));
        } catch (Throwable) {
            // Tracking must never break Portfolio rendering.
        }
    }
}
