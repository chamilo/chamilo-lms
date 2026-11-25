<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'ASSIGNMENT_ITEM_CONTEXT', CStudentPublication>
 */
final class AssignmentItemContextVoter extends Voter
{
    public const string ACCESS = 'ASSIGNMENT_ITEM_CONTEXT';

    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof CStudentPublication;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $assignment = 'file' === $subject->getFiletype() ? $subject->getPublicationParent() : $subject;
        $isSessionCopy = null !== $assignment?->getPublicationParent();
        if (null === $course && ($isSessionCopy || null !== $session)) {
            // A session copy (and its submissions) must be requested in an explicit context.
            return false;
        }

        $isAdmin = $this->accessDecisionManager->decide($token, ['ROLE_ADMIN']);
        foreach ($subject->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            if (null === $linkCourse || null !== $link->getDeletedAt()) {
                continue;
            }
            if (null !== $course && ($linkCourse->getId() !== $course->getId()
                || $linkSession?->getId() !== $session?->getId())) {
                continue;
            }
            if ($isAdmin || $linkCourse->hasUserAsTeacher($user)) {
                return true;
            }
            if (null === $linkSession) {
                // Session enrollment alone does not grant access to the original assignment.
                if ($linkCourse->hasSubscriptionByUser($user)) {
                    return true;
                }

                continue;
            }
            if ($linkSession->hasCourse($linkCourse) && ($linkSession->hasUserAsGeneralCoach($user)
                || $linkSession->hasCourseCoachInCourse($user, $linkCourse)
                || $linkSession->hasUserInCourse($user, $linkCourse, Session::STUDENT))) {
                return true;
            }
        }

        return false;
    }
}
