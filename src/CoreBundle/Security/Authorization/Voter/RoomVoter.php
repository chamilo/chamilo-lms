<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'VIEW'|'EDIT'|'DELETE', Room>
 */
final class RoomVoter extends Voter
{
    public function __construct(
        private readonly RoomAccessUrlHelper $roomAccessUrlHelper,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Room
            && \in_array($attribute, ['VIEW', 'EDIT', 'DELETE'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        \assert($subject instanceof Room);

        return $this->roomAccessUrlHelper->isRoomAllowed($subject);
    }
}
