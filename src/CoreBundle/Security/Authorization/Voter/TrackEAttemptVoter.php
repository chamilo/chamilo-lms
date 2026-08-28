<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * An answer row is readable exactly when the attempt it belongs to is.
 *
 * @extends Voter<'VIEW', TrackEAttempt>
 */
class TrackEAttemptVoter extends Voter
{
    public const string VIEW = 'VIEW';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof TrackEAttempt && self::VIEW === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof TrackEAttempt);

        // Delegate through the AccessDecisionManager, not Security::isGranted, so the nested
        // decision runs against the exact token passed to this voter.
        return $this->accessDecisionManager->decide(
            $token,
            [TrackEExerciseVoter::VIEW],
            $subject->getTrackEExercise()
        );
    }
}
