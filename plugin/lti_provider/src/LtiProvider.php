<?php
/* For licensing terms, see /license.txt */

use \IMSGlobal\LTI;
use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
require_once __DIR__ . '/../db/demo_database.php';

/**
 * Class LtiProvider
 */
class LtiProvider
{

    /**
     * Oidc login and register
     */
    public function login($request = null) {
        LTI\LTI_OIDC_Login::new(new Demo_Database())
            ->do_oidc_login_redirect(api_get_path(WEB_PLUGIN_PATH). "lti_provider/web/game.php", $request)
            ->do_redirect();
    }

    /**
     * Lti Message Launch
     * @param bool $from_cache
     * @param null $launch_id
     * return $launch
     */
    public function launch($from_cache = false, $launch_id = null) {
        if ($from_cache) {
            $launch = LTI\LTI_Message_Launch::from_cache($launch_id, new Demo_Database());
        } else {
            $launch = LTI\LTI_Message_Launch::new(new Demo_Database())->validate();
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
