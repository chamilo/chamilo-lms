<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\User;

/**
 * Trait UserTrait.
 */
trait UserTrait
{
    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
