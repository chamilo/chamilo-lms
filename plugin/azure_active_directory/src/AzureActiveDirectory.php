<?php
/**
 * AzureActiveDirectory plugin class
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.azure_active_directory
 */
class AzureActiveDirectory extends Plugin
{
    const SETTING_ENABLE = 'enable';
    const SETTING_APP_ID = 'app_id';
    const SETTING_TENANT = 'tenant';
    const SETTING_SIGNUP_POLICY = 'signup_policy';
    const SETTING_SIGNIN_POLICY = 'signin_policy';
    const SETTING_BLOCK_NAME = 'block_name';
    const URL_TYPE_SIGNUP = 'sign-up';
    const URL_TYPE_SIGNIN = 'sign-in';
    const URL_TYPE_SIGNOUT = 'sign-out';

    /**
     * AzureActiveDirectory constructor.
     */
    protected function __construct()
    {
        $settings = [
            self::SETTING_ENABLE => 'boolean',
            self::SETTING_APP_ID => 'text',
            self::SETTING_TENANT => 'text',
            self::SETTING_SIGNUP_POLICY => 'text',
            self::SETTING_SIGNIN_POLICY => 'text',
            self::SETTING_BLOCK_NAME => 'text'
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $settings);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return Tour
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'azure_active_directory';
    }

    /**
     * @param $urlType Type of URL to generate
     * @return string
     */
    public function getUrl($urlType)
    {
        $settingsInfo = $this->get_settings();
        $settings = [];

        foreach ($settingsInfo as $settingInfo) {
            $variable = str_replace($this->get_name() . '_', '', $settingInfo['variable']);

            $settings[$variable] = $settingInfo['selected_value'];
        }

        $url = "https://login.microsoftonline.com/{$settings[self::SETTING_TENANT]}/oauth2/v2.0/";
        $callback = api_get_path(WEB_PLUGIN_PATH) . $this->get_name() . '/src/callback.php';

        if ($urlType === self::URL_TYPE_SIGNOUT) {
            $action = 'logout';
            $urlParams = [
                'p' => $settings[self::SETTING_SIGNIN_POLICY],
                'post_logout_redirect_uri' => $callback
            ];
        } else {
            $action = 'authorize';
            $policy = $settings[self::SETTING_SIGNUP_POLICY];

            if ($urlType === self::URL_TYPE_SIGNIN) {
                $policy = $settings[self::SETTING_SIGNIN_POLICY];
            }

            $urlParams = [
                'client_id' => $settings[self::SETTING_APP_ID],
                'response_type' => 'id_token',
                'redirect_uri' => $callback,
                'scope' => 'openid',
                'response_mode' => 'form_post',
                'state' => time(),
                'nonce' => time(),
                'p' => $policy
            ];
        }

        return $url . $action . '?' . http_build_query($urlParams);
    }
}