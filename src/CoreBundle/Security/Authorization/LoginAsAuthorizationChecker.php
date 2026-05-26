<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use SessionManager;
use UserManager;

/**
 * Centralized authorization policy for "login as" / Symfony switch_user impersonation.
 *
 * This service is the single source of truth — both the modern SwitchUserSubscriber
 * (Symfony firewall) and the legacy api_can_login_as() helper delegate here.
 *
 * Policy mirrors the historical Chamilo rules:
 *  1. Self-impersonation is forbidden.
 *  2. Target is a platform admin -> only a global platform admin may impersonate.
 *  3. Impersonator is a platform admin (non-global) -> may impersonate any non-admin.
 *  4. Impersonator is a session administrator (ROLE_SESSION_MANAGER) -> may impersonate
 *     only users with status STUDENT, plus COURSEMANAGER when the setting
 *     session.allow_session_admin_login_as_teacher is enabled.
 *  5. Impersonator is a HR manager (ROLE_HR / DRH) -> may impersonate either
 *       a) users followed via the UserRelUser RRHH relation, or
 *       b) users belonging to one of their sessions when the setting
 *          drh_can_access_all_session_content is enabled.
 *  6. Default deny.
 */
final class LoginAsAuthorizationChecker
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Returns true when $impersonator is allowed to switch into $target.
     */
    public function canLoginAs(User $impersonator, User $target): bool
    {
        // Rule 1: no self-impersonation.
        if ($impersonator->getId() === $target->getId()) {
            return false;
        }

        // Rule 2: target is a platform admin -> require global-admin-edits-admin policy.
        if ($this->isPlatformAdmin($target)) {
            return $this->globalAdminCanEditAdmin($impersonator, $target);
        }

        // Rule 3: impersonator is a platform admin -> may impersonate any non-admin target.
        if ($this->isPlatformAdmin($impersonator)) {
            return true;
        }

        // Rule 4: session administrator path.
        if ($impersonator->isSessionAdmin()) {
            return $this->canSessionAdminLoginAs($target);
        }

        // Rule 5: HR manager (DRH) path.
        if ($impersonator->isHRM()) {
            return $this->canDrhLoginAs($impersonator, $target);
        }

        // Rule 6: default deny.
        return false;
    }

    /**
     * Replicates api_is_platform_admin_by_id(): user is registered as platform admin.
     */
    private function isPlatformAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Replicates api_is_global_platform_admin(): the user is a platform admin AND is
     * registered on access URL #1 (the main installation). Falls back to the legacy
     * helper to honor multi-URL setups.
     */
    private function isGlobalPlatformAdmin(User $user): bool
    {
        if (!$this->isPlatformAdmin($user)) {
            return false;
        }

        return \api_is_global_platform_admin($user->getId());
    }

    /**
     * Replicates api_global_admin_can_edit_admin():
     *  - global admins can edit anyone (including other global admins),
     *  - regular platform admins can edit any admin except global admins,
     *  - non-admins cannot edit admins.
     */
    private function globalAdminCanEditAdmin(User $impersonator, User $target): bool
    {
        if ($this->isGlobalPlatformAdmin($impersonator)) {
            return true;
        }

        if (!$this->isPlatformAdmin($impersonator)) {
            return false;
        }

        return !$this->isGlobalPlatformAdmin($target);
    }

    /**
     * Session admin (ROLE_SESSION_MANAGER) may login as users with status STUDENT,
     * extended to COURSEMANAGER (teacher) when the platform setting allows it.
     */
    private function canSessionAdminLoginAs(User $target): bool
    {
        $allowed = [\STUDENT];

        if ('true' === (string) $this->settingsManager->getSetting('session.allow_session_admin_login_as_teacher')) {
            $allowed[] = \COURSEMANAGER;
        }

        return in_array((int) $target->getStatus(), $allowed, true);
    }

    /**
     * HR manager (ROLE_HR / DRH) may login as:
     *  - users they explicitly follow (UserRelUser RRHH relation), or
     *  - users in one of their sessions when drh_can_access_all_session_content is enabled.
     */
    private function canDrhLoginAs(User $impersonator, User $target): bool
    {
        if ('true' === (string) $this->settingsManager->getSetting('drh_can_access_all_session_content')) {
            $users = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus('drh_all', $impersonator->getId());
            if (is_array($users)) {
                foreach ($users as $row) {
                    if (isset($row['id']) && (int) $row['id'] === $target->getId()) {
                        return true;
                    }
                }
            }
        }

        return UserManager::is_user_followed_by_drh($target->getId(), $impersonator->getId());
    }
}