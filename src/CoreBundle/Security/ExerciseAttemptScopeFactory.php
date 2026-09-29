<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * Turns the security token into an ExerciseAttemptScope.
 *
 * The only place where the exercise attempt rules read roles for the query side, so
 * TrackEExerciseRepository can stay free of the security layer.
 */
final readonly class ExerciseAttemptScopeFactory
{
    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    public function fromToken(?TokenInterface $token): ?ExerciseAttemptScope
    {
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return null;
        }

        // Holding the role only opens the corresponding term; the repository still requires the
        // relation with the attempt's own owner or session for it to match.
        return new ExerciseAttemptScope(
            userId: (int) $user->getId(),
            mayFollowAsStudentBoss: $this->accessDecisionManager->decide($token, ['ROLE_STUDENT_BOSS']),
            mayFollowAsDrh: $this->accessDecisionManager->decide($token, ['ROLE_HR']),
            mayAdministerSessions: $this->accessDecisionManager->decide($token, ['ROLE_SESSION_MANAGER']),
        );
    }
}
