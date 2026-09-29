<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

use const COURSEMANAGER;
use const STUDENT;

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
 *  5. Impersonator is a HR manager (ROLE_HR / DRH) -> may impersonate ONLY users they
 *     follow directly via the UserRelUser RRHH relation, and only when the target holds no
 *     privilege the DRH itself lacks. Merely belonging to a session the DRH coaches does
 *     NOT grant impersonation rights, and a followed user with higher privileges (session
 *     admin, platform admin, etc.) can never be impersonated.
 *  6. Default deny.
 */
final class LoginAsAuthorizationChecker
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly UserRepository $userRepository,
        private readonly RoleHierarchyInterface $roleHierarchy,
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
     * Replicates api_is_global_platform_admin(): a platform admin who is also a global
     * (super) admin. Resolved directly from the already-loaded User entity instead of the
     * legacy api_is_global_platform_admin() helper, which reloads the user through the
     * legacy Container. That Container is only wired by LegacyListener (kernel.request,
     * priority 7), but the Symfony firewall fires switch_user earlier (priority 8) — so
     * during a "login as" attempt the legacy Container is still null and the legacy helper
     * would fatal with "Call to a member function get() on null".
     */
    private function isGlobalPlatformAdmin(User $user): bool
    {
        if (!$this->isPlatformAdmin($user)) {
            return false;
        }

        return $user->isSuperAdmin();
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
        $allowed = [STUDENT];

        if ('true' === (string) $this->settingsManager->getSetting('session.allow_session_admin_login_as_teacher')) {
            $allowed[] = COURSEMANAGER;
        }

        return \in_array((int) $target->getStatus(), $allowed, true);
    }

    /**
     * HR manager (ROLE_HR / DRH) may login as a user only when BOTH hold:
     *  - the user is followed directly via the UserRelUser RRHH relation (legacy parity:
     *    belonging to a session the DRH coaches is intentionally NOT enough), and
     *  - the user does not hold any privilege the DRH lacks, so impersonation can never be
     *    used to escalate privileges (e.g. a followed session admin or admin is refused).
     *
     * The RRHH lookup goes through UserRepository (Doctrine) instead of the legacy
     * UserManager helper, so the policy no longer depends on the legacy Container being
     * bootstrapped — which the Symfony firewall has not yet done when switch_user fires.
     */
    private function canDrhLoginAs(User $impersonator, User $target): bool
    {
        if (!$this->userRepository->isUserFollowedByDrh((int) $target->getId(), (int) $impersonator->getId())) {
            return false;
        }

        return !$this->targetHasHigherPrivileges($impersonator, $target);
    }

    /**
     * Whether $target holds at least one (hierarchy-expanded) role that $impersonator does
     * not have. Used as a privilege-escalation guard for non-admin impersonators.
     */
    private function targetHasHigherPrivileges(User $impersonator, User $target): bool
    {
        $impersonatorRoles = $this->roleHierarchy->getReachableRoleNames($impersonator->getRoles());
        $targetRoles = $this->roleHierarchy->getReachableRoleNames($target->getRoles());

        return [] !== array_diff($targetRoles, $impersonatorRoles);
    }
}
