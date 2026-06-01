<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * @extends Voter<'VIEW'|'EDIT'|'DELETE', Course>
 */
class CourseVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;

    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly SettingsManager $settingsManager,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
    ) {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // only vote on Post objects inside this voter
        return $subject instanceof Course;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Admins have access to everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Course $course */
        $course = $subject;
        $request = $this->requestStack->getCurrentRequest();

        $sessionId = $request?->query?->get('sid');
        $session = null;

        if (!empty($sessionId)) {
            /** @var Session|null $session */
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        }

        switch ($attribute) {
            case self::VIEW:
                // Course is hidden, so it is not visible for anyone except admins.
                if ($course->isHidden()) {
                    return false;
                }

                // Course::OPEN_WORLD
                if ($course->isPublic()) {
                    if ($user instanceof User
                        && $this->isStudent($user, $course, $session)
                        && $this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)
                    ) {
                        throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                    }

                    return true;
                }

                if (!$user instanceof UserInterface) {
                    return false;
                }

                // Course::OPEN_PLATFORM
                if (Course::OPEN_PLATFORM === $course->getVisibility()
                    && false === $this->isOpenCourseAccessBlockedForRegisteredUsers()
                ) {
                    if ($user instanceof User
                        && $this->isStudent($user, $course, $session)
                        && $this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)
                    ) {
                        throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                    }

                    return true;
                }

                // Session-based access.
                if (null !== $session && $user instanceof User) {
                    if ($session->hasUserAsGeneralCoach($user)
                        || $session->hasCourseCoachInCourse($user, $course)
                    ) {
                        return true;
                    }

                    if ($session->hasUserInCourse($user, $course, Session::STUDENT)) {
                        if ($this->isCourseLockedForUser($user, $course, $session->getId())) {
                            throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                        }

                        return true;
                    }
                }

                // Course::REGISTERED
                if ($user instanceof User && $course->hasSubscriptionByUser($user)) {
                    if ($this->isCourseLockedForUser($user, $course)) {
                        throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                    }

                    return true;
                }

                return false;

            case self::EDIT:
            case self::DELETE:
                return $user instanceof User && $course->hasUserAsTeacher($user);
        }

        return false;
    }

    /**
     * Checks whether registered users must be subscribed before accessing
     * OPEN_PLATFORM course contents.
     */
    private function isOpenCourseAccessBlockedForRegisteredUsers(): bool
    {
        return filter_var(
            $this->settingsManager->getSetting('course.block_registered_users_access_to_open_course_contents', true),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * Checks whether the given course is locked for the user
     * due to unmet prerequisite sequences.
     */
    private function isCourseLockedForUser(User $user, Course $course, int $sessionId = 0): bool
    {
        $sequenceRepo = $this->entityManager->getRepository(SequenceResource::class);

        $sequences = $sequenceRepo->getRequirements(
            $course->getId(),
            SequenceResource::COURSE_TYPE
        );

        if (empty($sequences)) {
            return false;
        }

        $statusList = $sequenceRepo->checkRequirementsForUser(
            $sequences,
            SequenceResource::COURSE_TYPE,
            $user->getId()
        );

        return !$sequenceRepo->checkSequenceAreCompleted($statusList);
    }

    private function isStudent(User $user, Course $course, ?Session $session): bool
    {
        if ($session) {
            return $session->hasUserInCourse($user, $course, Session::STUDENT);
        }

        return $course->hasUserAsStudent($user);
    }
}
