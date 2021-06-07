<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;

trait UserCreatorTrait
{
    public ?ResourceNode $resourceNode = null;
    public ?User $resourceNodeCreator = null;

    public function getCreator(): ?User
    {
        if (null === $this->resourceNode) {
            return null;
        }

        return $this->resourceNode->getCreator();
    }

    public function setCreator(User $user)
    {
        $this->resourceNodeCreator = $user;

        return $this;
    }

    public function getResourceNodeCreator(): ?User
    {
        return $this->resourceNodeCreator;
    }
}
