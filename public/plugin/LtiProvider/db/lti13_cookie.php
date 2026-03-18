<?php
/* For license terms, see /license.txt */

use Packback\Lti1p3\Interfaces\ICookie as Lti1p3Cookie;

class Lti13Cookie implements Lti1p3Cookie
{
    public function getCookie(string $name): ?string
    {
        if (isset($_COOKIE[$name]) && is_string($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        if (isset($_COOKIE['LEGACY_'.$name]) && is_string($_COOKIE['LEGACY_'.$name])) {
            return $_COOKIE['LEGACY_'.$name];
        }

        return null;
    }

    public function setCookie(string $name, string $value, int $exp = 3600, array $options = []): void
    {
        $isHttps = 0 === strpos(api_get_path(WEB_PATH), 'https://');

        $baseOptions = [
            'expires' => time() + $exp,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'None',
        ];

        setcookie($name, $value, array_merge($baseOptions, $options));

        // Fallback cookie for legacy browsers that ignore SameSite=None.
        $legacyOptions = [
            'expires' => time() + $exp,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
        ];

        setcookie('LEGACY_'.$name, $value, array_merge($legacyOptions, $options));
    }
}
