<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CThematic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * What the Course Progress providers need beyond "may this user edit here", which
 * IsAllowedToEditHelper answers on its own.
 */
readonly class CourseProgressHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    public function assertToolEnabled(Course $course): void
    {
        if ($this->isToolEnabled($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Course Progress tool is disabled for this course.');
    }

    public function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    public function assertCanManage(Course $course, ?Session $session): void
    {
        if ($this->canManage($course, $session)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage course progress in this context.');
    }

    public function canManage(Course $course, ?Session $session): bool
    {
        return $this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session);
    }

    /**
     * Whether the current user may see the course progress.
     *
     * Broader than the editing rules on purpose: a visitor reads it on a public course with no
     * registration code, and an HR manager or a session administrator reads it without being
     * able to change anything.
     */
    public function canRead(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->userHelper->getCurrent();

        if (!$user instanceof User) {
            return null === $session
                && $course->isPublic()
                && '' === trim((string) $course->getRegistrationCode());
        }

        if ($this->security->isGranted('ROLE_HR') || $this->security->isGranted(CourseVoter::VIEW, $course)) {
            return true;
        }

        return $session instanceof Session
            && $user->isSessionAdmin()
            && $this->isSettingEnabled('session.session_admins_access_all_content');
    }

    /**
     * Whether the thematic belongs to this exact course and session, rather than being
     * inherited from the base course into a session.
     */
    public function thematicBelongsToExactContext(CThematic $thematic, Course $course, ?Session $session): bool
    {
        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }

    private function isToolEnabled(Course $course): bool
    {
        $settings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $course->getId(),
                'variable' => 'enabled',
            ],
            ['iid' => 'ASC'],
        );

        $legacyValue = null;

        foreach ($settings as $setting) {
            if (!$setting instanceof CCourseSetting) {
                continue;
            }

            $category = trim((string) $setting->getCategory());
            $value = $setting->getValue();

            if ('course_progress' === $category) {
                return $this->resolveEnabledValue($value);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return true;
        }

        return $this->resolveEnabledValue($legacyValue);
    }

    private function isSettingEnabled(string $name): bool
    {
        return $this->resolveEnabledValue($this->settingsManager->getSetting($name, true));
    }

    /**
     * An absent course setting means the tool is on, which is how the legacy pages read it.
     */
    private function resolveEnabledValue(mixed $value): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return true;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
