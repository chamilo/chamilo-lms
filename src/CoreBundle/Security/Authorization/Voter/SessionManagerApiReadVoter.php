<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\SessionManagerApiScope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<'SESSION_MANAGER_API_READ', Course|Session|ExtraField|ExtraFieldValues> */
final class SessionManagerApiReadVoter extends Voter
{
    public const string VIEW = 'SESSION_MANAGER_API_READ';

    public function __construct(
        private readonly SessionManagerApiScope $scope
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute
            && ($subject instanceof Course
                || $subject instanceof Session
                || $subject instanceof ExtraField
                || $subject instanceof ExtraFieldValues);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $actor = $token->getUser();
        if (!$actor instanceof User || !$actor->hasRole('ROLE_SESSION_MANAGER')) {
            return false;
        }

        return match (true) {
            $subject instanceof Course => $this->scope->canReadCourse($actor, $subject),
            $subject instanceof Session => $this->scope->canReadSession($actor, $subject),
            $subject instanceof ExtraField => $this->scope->canReadExtraField($actor, $subject),
            $subject instanceof ExtraFieldValues => $this->scope->canReadExtraFieldValue($actor, $subject),
            default => false,
        };
    }
}
