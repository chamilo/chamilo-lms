<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Routing\RouterInterface;

/**
 * Destination of a user after a successful login, for every login method.
 */
final class LoginRedirectResolver
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly RouterInterface $router,
    ) {}

    /**
     * A local path, never an external URL.
     */
    public function resolve(User $user): string
    {
        return $this->resolveProfilePath($user) ?? $this->router->generate('index');
    }

    /**
     * Destination set for the user's profile in registration.redirect_after_login.
     * Keeps the profile priority and the legacy aliases of 1.11.
     */
    private function resolveProfilePath(User $user): ?string
    {
        $setting = $this->settingsManager->getSetting('registration.redirect_after_login', true);
        if (!\is_string($setting)) {
            return null;
        }

        $map = json_decode($setting, true);
        if (!\is_array($map)) {
            return null;
        }

        $roles = $user->getRoles();
        $profile = match (true) {
            \in_array('ROLE_ADMIN', $roles, true) => 'ADMIN',
            \in_array('ROLE_SESSION_MANAGER', $roles, true) => 'SESSIONADMIN',
            \in_array('ROLE_TEACHER', $roles, true) => 'COURSEMANAGER',
            \in_array('ROLE_STUDENT_BOSS', $roles, true) => 'STUDENT_BOSS',
            \in_array('ROLE_HR', $roles, true) => 'HRM',
            \in_array('ROLE_INVITEE', $roles, true) => 'INVITEE',
            \in_array('ROLE_STUDENT', $roles, true) => 'STUDENT',
            default => null,
        };

        // 1.11 named the HRM profile "DRH" in this setting.
        $value = match ($profile) {
            null => null,
            'HRM' => $map['HRM'] ?? $map['DRH'] ?? null,
            default => $map[$profile] ?? null,
        };

        if (!\is_string($value) || '' === $value) {
            return null;
        }

        // Reject schemes, authority-relative URLs, backslashes and control characters.
        // Browsers can treat a backslash as a slash in an HTTP(S) URL.
        if (preg_match('~[\x00-\x20\x7f]~', $value)
            || str_contains($value, '\\')
            || str_starts_with($value, '//')
            || preg_match('~^[a-z][a-z0-9+.-]*:~i', $value)
        ) {
            return null;
        }

        $path = '/'.ltrim($value, '/');
        $decodedPath = rawurldecode($path);
        if (str_starts_with($decodedPath, '//') || str_contains($decodedPath, '\\')
            || preg_match('~[\x00-\x1f\x7f]~', $decodedPath)
        ) {
            return null;
        }

        return match ($path) {
            '/index.php', '/user_portal.php' => '/home',
            '/main/auth/courses.php' => '/courses',
            default => $path,
        };
    }
}
