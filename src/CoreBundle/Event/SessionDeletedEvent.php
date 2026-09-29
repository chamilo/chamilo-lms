<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\Session;

/**
 * Dispatched as TYPE_PRE, before the session row is removed, so listeners can
 * clean up what references it: rows whose foreign key would block the deletion,
 * and rows a cascade would drop before the listener could read them.
 */
class SessionDeletedEvent extends AbstractEvent
{
    public function getSession(): ?Session
    {
        return $this->data['session'] ?? null;
    }

    public function getSessionId(): ?int
    {
        return $this->getSession()?->getId();
    }
}
