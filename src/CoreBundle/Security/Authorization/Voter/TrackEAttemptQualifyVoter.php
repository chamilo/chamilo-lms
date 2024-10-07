<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\IsAllowedToEditHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'VIEW', TrackEAttemptQualify>
 */
class TrackEAttemptQualifyVoter extends Voter
{
    public const VIEW = 'VIEW';

    public function __construct(
        private readonly Security $security,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $allowed = [
            self::VIEW,
        ];

        return $subject instanceof TrackEAttemptQualify && \in_array($attribute, $allowed);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        \assert($user instanceof User);
        \assert($subject instanceof TrackEAttemptQualify);

        $trackExercise = $subject->getTrackExercise();
        $session = $trackExercise->getSession();
        $course = $trackExercise->getCourse();

        $isAllowedToEdit = $this->isAllowedToEditHelper->check(false, true, false, true, $course, $session) || $user->isCourseTutor();

        if ($isAllowedToEdit) {
            return true;
        }

        if ($trackExercise->getUser()->getId() === $user->getId()) {
            return true;
        }

        return false;
    }
}
