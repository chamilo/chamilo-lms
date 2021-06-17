<?php
/* For licensing terms, see /license.txt */

use Packback\Lti1p3;

require_once __DIR__ . '/../db/lti13_cookie.php';
require_once __DIR__ . '/../db/lti13_cache.php';
require_once __DIR__ . '/../db/lti13_database.php';

use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\LtiMessageLaunch;



/**
 * Class LtiProvider
 */
class LtiProvider
{

    /**
     * Oidc login and register
     */
    public function login($request = null) {
        LtiOidcLogin::new(new Lti13Database, new Lti13Cache(), new Lti13Cookie)
            ->doOidcLoginRedirect(api_get_path(WEB_PLUGIN_PATH). "lti_provider/web/game.php", $request)
            ->doRedirect();
    }

    /**
     * Lti Message Launch
     * @param bool $fromCache
     * @param null $launchId
     * return $launch
     */
    public function launch($fromCache = false, $launchId = null) {
        if ($fromCache) {
            $launch = LtiMessageLaunch::fromCache($launchId, new Lti13Database(), new Lti13Cache());
        } else {
            $launch = LtiMessageLaunch::new(new Lti13Database(), new Lti13Cache(), new Lti13Cookie)->validate();
        }
        return $launch;
    }

    /**
     * Get the class instance
     * @staticvar LtiProvider $result
     * @return LtiProvider
     */
    public static function create()
    {
        static $result = null;
        return $result ?: $result = new self();
    }

}
