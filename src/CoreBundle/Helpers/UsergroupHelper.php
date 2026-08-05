<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use UserGroupModel;

final class UsergroupHelper
{
    /**
     * Synchronizes the sessions assigned to a usergroup.
     *
     * This keeps the legacy enrollment workflow behind a modern helper
     * boundary until the complete usergroup/session flow is migrated.
     *
     * @param list<int> $sessionIds
     */
    public function synchronizeSessions(int $usergroupId, array $sessionIds): void
    {
        $usergroupModel = new UserGroupModel();
        $usergroupModel->subscribe_sessions_to_usergroup($usergroupId, $sessionIds);
    }
}
