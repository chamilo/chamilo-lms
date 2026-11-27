<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * What the Course Description providers need to know beyond "may this user edit here",
 * which IsAllowedToEditHelper answers on its own.
 */
readonly class CourseDescriptionHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CourseSettingPolicyManager $courseSettingPolicyManager,
        private UserHelper $userHelper,
        private SessionManagerApiScope $sessionManagerApiScope,
    ) {}

    /**
     * Resolve the sortable link in the exact course/session scope, including the
     * NULL base-session case. Inherited and individually targeted links must not move.
     */
    public function getReorderLink(CCourseDescription $description, Course $course, ?Session $session): ?ResourceLink
    {
        $node = $description->getResourceNode();
        if (null === $node || !$this->security->isGranted('EDIT', $node)) {
            return null;
        }

        // The list query can hydrate a filtered resourceLinks collection.
        // Resolve the exact link independently of that collection.
        $links = $this->entityManager->getRepository(ResourceLink::class)->findBy([
            'resourceNode' => $node,
            'course' => $course,
            'session' => $session,
            'group' => null,
            'user' => null,
            'userGroup' => null,
            'resourceTypeGroup' => $node->getResourceType()->getId(),
            'deletedAt' => null,
        ]);

        return 1 === \count($links) ? $links[0] : null;
    }

    public function assertToolEnabled(Course $course): void
    {
        if ($this->isToolEnabled($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Course Description tool is disabled for this course.');
    }

    public function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    /**
     * Whether the current user may see the course descriptions.
     *
     * Deliberately broader than the editing rules: a visitor reads them on a public course
     * with no registration code, and an HR manager or a session administrator reads them
     * without being able to change anything.
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

        // Integration reads without sid are allowed only for courses in sessions managed
        // by this actor on the current access URL. Supplying sid must not widen the scope.
        if ($this->security->isGranted('ROLE_SESSION_MANAGER')
            && $this->sessionManagerApiScope->canReadCourse($user, $course)
            && (null === $session || $this->sessionManagerApiScope->canReadSession($user, $session))
        ) {
            return true;
        }

        return $session instanceof Session
            && $user->isSessionAdmin()
            && $this->isSettingEnabled('session.session_admins_access_all_content')
            && $this->sessionManagerApiScope->canReadSession($user, $session);
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

            if ('course_description' === $category) {
                return $this->resolveEnabledValue($this->courseSettingPolicyManager->effectiveValue('enabled', $value, '1', 'course_description'));
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return $this->resolveEnabledValue($this->courseSettingPolicyManager->effectiveValue('enabled', null, '1', 'course_description'));
        }

        return $this->resolveEnabledValue($this->courseSettingPolicyManager->effectiveValue('enabled', $legacyValue, '1', 'course_description'));
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
