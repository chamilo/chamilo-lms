<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
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
        $tokenUser = $token->getUser();

        // Admins have access to everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Course $course */
        $course = $subject;

        $request = $this->requestStack->getCurrentRequest();
        $sessionId = $request?->query?->get('sid');
        $sessionRepository = $this->entityManager->getRepository(Session::class);

        $session = null;
        if (!empty($sessionId)) {
            /** @var Session|null $session */
            $session = $sessionRepository->find($sessionId);
        }

        switch ($attribute) {
            case self::VIEW:
                // Course is hidden then is not visible for nobody expect admins.
                if ($course->isHidden()) {
                    return false;
                }

                // Course::OPEN_WORLD
                if ($course->isPublic()) {
                    if ($tokenUser instanceof User) {
                        $user = $this->getTokenSafeUser($token, $tokenUser);
                        if ($this->isStudent($user, $course, $session)) {
                            if ($this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)) {
                                throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                            }
                        }

                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);
                        if ($course->hasUserAsTeacher($user)) {
                            $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                        }
                        $token->setUser($user);
                    }

                    return true;
                }

                // User should be instance of UserInterface.
                if (!$tokenUser instanceof UserInterface) {
                    return false;
                }

                // Course::OPEN_PLATFORM
                if (Course::OPEN_PLATFORM === $course->getVisibility()) {
                    if ($tokenUser instanceof User) {
                        $user = $this->getTokenSafeUser($token, $tokenUser);

                        if ($this->isStudent($user, $course, $session)) {
                            if ($this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)) {
                                throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                            }
                        }

                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);

                        if ($course->hasUserAsTeacher($user)) {
                            $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                        }

                        $token->setUser($user);
                    }

                    return true;
                }

                // Validation in session
                if ($session && $tokenUser instanceof User) {
                    $user = $this->getTokenSafeUser($token, $tokenUser);

                    $userIsGeneralCoach = $session->hasUserAsGeneralCoach($user);
                    $userIsCourseCoach = $session->hasCourseCoachInCourse($user, $course);
                    $userIsStudent = $session->hasUserInCourse($user, $course, Session::STUDENT);

                    if ($userIsGeneralCoach || $userIsCourseCoach) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);
                        $token->setUser($user);

                        return true;
                    }

                    if ($userIsStudent) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);

                        if ($this->isCourseLockedForUser($user, $course, $session->getId())) {
                            throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                        }

                        $token->setUser($user);

                        return true;
                    }
                }

                // Course::REGISTERED
                if ($tokenUser instanceof User && $course->hasSubscriptionByUser($tokenUser)) {
                    $user = $this->getTokenSafeUser($token, $tokenUser);

                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);

                    if ($course->hasUserAsTeacher($user)) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                    }

                    if ($this->isCourseLockedForUser($user, $course)) {
                        throw new NotAllowedException($this->translator->trans('This course is locked. You must complete the prerequisite(s) first.'), 'warning', 403);
                    }

                    $token->setUser($user);

                    return true;
                }

                return false;

            case self::EDIT:
            case self::DELETE:
                if ($tokenUser instanceof User && $course->hasUserAsTeacher($tokenUser)) {
                    $user = $this->getTokenSafeUser($token, $tokenUser);
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                    $token->setUser($user);

                    return true;
                }

                return false;
        }

        return false;
    }

    /**
     * Returns a "token-safe" User instance to add context roles without persisting them to DB.
     *
     * If the User is managed by Doctrine, we clone it, add roles to the clone,
     * and store the clone in the token.
     */
    private function getTokenSafeUser(TokenInterface $token, User $user): User
    {
        if ($this->entityManager->contains($user)) {
            $user = clone $user;
            $token->setUser($user);
        }

        return $user;
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
