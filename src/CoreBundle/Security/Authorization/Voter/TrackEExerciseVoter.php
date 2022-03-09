<?php

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TrackEExerciseVoter extends Voter
{
    public const VIEW = 'VIEW';

    public function __construct(
        private Security $security
    ) {
    }

    protected function supports(string $attribute, $subject): bool
    {
        $allowed = [
            self::VIEW,
        ];

        return $subject instanceof TrackEExercise && in_array($attribute, $allowed);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var TrackEExercise $attempt */
        $attempt = $subject;

        $course = $attempt->getCourse();
        $session = $attempt->getSession();

        if ($attempt->getUser() === $user) {
            return true;
        }

        if ($session) {
            if ($session->hasUserAsGeneralCoach($user)) {
                return true;
            }

            if ($session->hasCourseCoachInCourse($user, $course)) {
                return true;
            }
        } else {
            if ($course->hasTeacher($user)) {
                return true;
            }
        }

        return false;
    }
}
