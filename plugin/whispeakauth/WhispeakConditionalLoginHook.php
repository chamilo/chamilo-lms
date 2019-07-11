<?php
/* For licensing terms, see /license.txt */

/**
 * Class WhispeakConditionalLoginHook.
 *
 * Implements a Two-Factor Authentication with Whispeak.
 */
class WhispeakConditionalLoginHook extends HookObserver implements HookConditionalLoginObserverInterface
{
    /**
     * WhispeakConditionalLoginHook constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/whispeakauth/WhispeakAuthPlugin.php',
            'whispeakauth'
        );
    }

    /**
     * Return an associative array (callable, url) needed for Conditional Login.
     * <code>
     * [
     *     'conditional_function' => function (array $userInfo) {},
     *     'url' => '',
     * ]
     * </code>
     * conditional_function returns false to redirect to the url and returns true to continue with the classical login.
     *
     * @param HookConditionalLoginEventInterface $hook
     *
     * @return array
     */
    public function hookConditionalLogin(HookConditionalLoginEventInterface $hook)
    {
        return [
            'conditional_function' => function (array $userInfo) {
                if (ChamiloSession::has(WhispeakAuthPlugin::SESSION_2FA_USER)) {
                    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

                    return true;
                }

                $isEnrolled = WhispeakAuthPlugin::checkUserIsEnrolled($userInfo['user_id']);

                if ($isEnrolled) {
                    ChamiloSession::write(WhispeakAuthPlugin::SESSION_2FA_USER, $userInfo['user_id']);
                }

                return !$isEnrolled;
            },
            'url' => api_get_path(WEB_PLUGIN_PATH).$this->getPluginName().'/authentify.php',
        ];
    }
}
