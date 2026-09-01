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

    /**
     * Synchronizes the users assigned to a usergroup (class/social group).
     *
     * Beyond the usergroup_rel_user rows themselves, this also subscribes newly
     * added users to every session/course linked to the usergroup (and, unless
     * the platform is configured to keep them, unsubscribes removed users from
     * those same sessions/courses), then re-syncs any course groups linked to
     * the usergroup -- mirroring the legacy "add users to class" workflow.
     *
     * @param list<int> $userIds
     */
    public function subscribeUsers(int $usergroupId, array $userIds, bool $deleteUsersNotPresent, int $relationType): void
    {
        $usergroupModel = new UserGroupModel();
        $usergroupModel->subscribe_users_to_usergroup($usergroupId, $userIds, $deleteUsersNotPresent, $relationType);
    }

    /**
     * Synchronizes the courses assigned to a usergroup.
     *
     * Beyond the usergroup_rel_course rows themselves, this also subscribes the
     * usergroup's existing members into every newly-linked course (and, unless
     * the platform is configured to keep them, unsubscribes them from courses
     * removed from the usergroup) -- mirroring the legacy "add courses to
     * class" workflow.
     *
     * @param list<int> $courseIds
     */
    public function synchronizeCourses(int $usergroupId, array $courseIds): void
    {
        $usergroupModel = new UserGroupModel();
        $usergroupModel->subscribe_courses_to_usergroup($usergroupId, $courseIds);
    }
}
