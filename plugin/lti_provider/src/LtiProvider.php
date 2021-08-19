<?php
/* For licensing terms, see /license.txt */

use Packback\Lti1p3;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\LtiOidcLogin;

require_once __DIR__.'/../db/lti13_cookie.php';
require_once __DIR__.'/../db/lti13_cache.php';
require_once __DIR__.'/../db/lti13_database.php';

/**
 * Class LtiProvider.
 */
class LtiProvider
{
    /**
     * Get the class instance.
     *
     * @staticvar LtiProvider $result
     *
     * @return LtiProvider
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Oidc login and register.
     *
     * @throws Lti1p3\OidcException
     */
    public function login($request = null)
    {
        LtiOidcLogin::new(new Lti13Database(), new Lti13Cache(), new Lti13Cookie())
            ->doOidcLoginRedirect(api_get_path(WEB_PLUGIN_PATH)."lti_provider/web/game.php", $request)
            ->doRedirect();
    }

    /**
     * Lti Message Launch.
     */
    public function launch(bool $fromCache = false, ?int $launchId = null): LtiMessageLaunch
    {
        if ($fromCache) {
            $launch = LtiMessageLaunch::fromCache($launchId, new Lti13Database(), new Lti13Cache());
        } else {
            $launch = LtiMessageLaunch::new(new Lti13Database(), new Lti13Cache(), new Lti13Cookie())->validate();
        }

        return $launch;
    }
}
