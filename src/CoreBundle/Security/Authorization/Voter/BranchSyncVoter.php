<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'VIEW'|'EDIT'|'DELETE', BranchSync>
 */
final class BranchSyncVoter extends Voter
{
    public function __construct(
        private readonly RoomAccessUrlHelper $roomAccessUrlHelper,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof BranchSync
            && \in_array($attribute, ['VIEW', 'EDIT', 'DELETE'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof BranchSync);

        return $this->roomAccessUrlHelper->isBranchAllowed($subject);
    }
}
