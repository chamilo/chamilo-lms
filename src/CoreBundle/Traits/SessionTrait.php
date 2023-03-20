<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\Session;

trait SessionTrait
{
    protected ?Session $session = null;

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }
}
