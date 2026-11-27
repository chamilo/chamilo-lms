<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;

final class LoginRedirectPathResolver
{
    /**
     * Resolve the configured destination without accepting an external URL.
     * Keep the profile priority and legacy aliases used by the password login.
     */
    public static function resolve(User $user, mixed $setting, string $fallback): string
    {
        if (!\is_string($setting)) {
            return $fallback;
        }

        $map = json_decode($setting, true);
        if (!\is_array($map)) {
            return $fallback;
        }

        $roles = $user->getRoles();
        $profile = match (true) {
            \in_array('ROLE_ADMIN', $roles, true) => 'ADMIN',
            \in_array('ROLE_SESSION_MANAGER', $roles, true) => 'SESSIONADMIN',
            \in_array('ROLE_TEACHER', $roles, true) => 'COURSEMANAGER',
            \in_array('ROLE_STUDENT_BOSS', $roles, true) => 'STUDENT_BOSS',
            \in_array('ROLE_HR', $roles, true), \in_array('ROLE_DRH', $roles, true) => 'HRM',
            \in_array('ROLE_INVITEE', $roles, true) => 'INVITEE',
            \in_array('ROLE_STUDENT', $roles, true) => 'STUDENT',
            default => null,
        };

        $value = 'HRM' === $profile
            ? ($map['HRM'] ?? $map['DRH'] ?? null)
            : (null !== $profile ? ($map[$profile] ?? null) : null);

        if (!\is_string($value) || '' === $value) {
            return $fallback;
        }

        // Reject schemes, authority-relative URLs, backslashes and control characters.
        // Browsers can treat a backslash as a slash in an HTTP(S) URL.
        if (preg_match('~[\x00-\x20\x7f]~', $value)
            || str_contains($value, '\\')
            || str_starts_with($value, '//')
            || preg_match('~^[a-z][a-z0-9+.-]*:~i', $value)
        ) {
            return $fallback;
        }

        $path = '/'.ltrim($value, '/');
        $decodedPath = rawurldecode($path);
        if (str_starts_with($decodedPath, '//') || str_contains($decodedPath, '\\')
            || preg_match('~[\x00-\x1f\x7f]~', $decodedPath)
        ) {
            return $fallback;
        }

        return match ($path) {
            '/index.php', '/user_portal.php' => '/home',
            '/main/auth/courses.php' => '/courses',
            default => $path,
        };
    }
}
