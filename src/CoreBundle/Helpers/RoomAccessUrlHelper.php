<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\Room;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class RoomAccessUrlHelper
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function isBranchAllowed(?BranchSync $branch): bool
    {
        $currentAccessUrlId = $this->accessUrlHelper->getCurrent()?->getId();
        $branchAccessUrlId = $branch?->getUrl()?->getId();

        return null !== $currentAccessUrlId && $currentAccessUrlId === $branchAccessUrlId;
    }

    public function isRoomAllowed(?Room $room): bool
    {
        return null === $room || $this->isBranchAllowed($room->getBranch());
    }

    public function assertBranchAllowed(?BranchSync $branch): void
    {
        if (!$this->isBranchAllowed($branch)) {
            throw new AccessDeniedHttpException('The selected branch does not belong to the current access URL.');
        }
    }

    public function assertRoomAllowed(?Room $room): void
    {
        if (!$this->isRoomAllowed($room)) {
            throw new AccessDeniedHttpException('The selected room does not belong to the current access URL.');
        }
    }
}
