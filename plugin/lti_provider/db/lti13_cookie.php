<?php
/* For license terms, see /license.txt */

use Packback\Lti1p3\Interfaces\Cookie as Lti1p3Cookie;

class Lti13Cookie implements Lti1p3Cookie
{
    public function getCookie($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        // Look for backup cookie if same site is not supported by the user's browser.
        if (isset($_COOKIE["LEGACY_" . $name])) {
            return $_COOKIE["LEGACY_" . $name];
        }
        return false;
    }

    public function setCookie($name, $value, $exp = 3600, $options = []): self
    {
        $cookie_options = [
            'expires' => time() + $exp
        ];

        // SameSite none and secure will be required for tools to work inside iframes
        /*$same_site_options = [
            'samesite' => 'None',
            'secure' => false,
            'httponly' => true
        ];*/

        setcookie($name, $value, array_merge($cookie_options, $same_site_options, $options));

        // Set a second fallback cookie in the event that "SameSite" is not supported
        setcookie("LEGACY_" . $name, $value, array_merge($cookie_options, $options));
        return $this;
    }
}
