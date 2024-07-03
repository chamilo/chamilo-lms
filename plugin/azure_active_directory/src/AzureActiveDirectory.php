<?php
/* For license terms, see /license.txt */

use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * AzureActiveDirectory plugin class.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.azure_active_directory
 */
class AzureActiveDirectory extends Plugin
{
    public const SETTING_ENABLE = 'enable';
    public const SETTING_APP_ID = 'app_id';
    public const SETTING_APP_SECRET = 'app_secret';
    public const SETTING_BLOCK_NAME = 'block_name';
    public const SETTING_FORCE_LOGOUT_BUTTON = 'force_logout';
    public const SETTING_MANAGEMENT_LOGIN_ENABLE = 'management_login_enable';
    public const SETTING_MANAGEMENT_LOGIN_NAME = 'management_login_name';
    public const SETTING_PROVISION_USERS = 'provisioning';
    public const SETTING_GROUP_ID_ADMIN = 'group_id_admin';
    public const SETTING_GROUP_ID_SESSION_ADMIN = 'group_id_session_admin';
    public const SETTING_GROUP_ID_TEACHER = 'group_id_teacher';

    public const URL_TYPE_AUTHORIZE = 'login';
    public const URL_TYPE_LOGOUT = 'logout';

    public const EXTRA_FIELD_ORGANISATION_EMAIL = 'organisationemail';
    public const EXTRA_FIELD_AZURE_ID = 'azure_id';

    /**
     * AzureActiveDirectory constructor.
     */
    protected function __construct()
    {
        $settings = [
            self::SETTING_ENABLE => 'boolean',
            self::SETTING_APP_ID => 'text',
            self::SETTING_APP_SECRET => 'text',
            self::SETTING_BLOCK_NAME => 'text',
            self::SETTING_FORCE_LOGOUT_BUTTON => 'boolean',
            self::SETTING_MANAGEMENT_LOGIN_ENABLE => 'boolean',
            self::SETTING_MANAGEMENT_LOGIN_NAME => 'text',
            self::SETTING_PROVISION_USERS => 'boolean',
            self::SETTING_GROUP_ID_ADMIN => 'text',
            self::SETTING_GROUP_ID_SESSION_ADMIN => 'text',
            self::SETTING_GROUP_ID_TEACHER => 'text',
        ];

        parent::__construct('2.3', 'Angel Fernando Quiroz Campos, Yannick Warnier', $settings);
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return $this
     */
    public static function create()
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
     * @return Azure
     */
    public function getProvider()
    {
        $provider = new Azure([
            'clientId' => $this->get(self::SETTING_APP_ID),
            'clientSecret' => $this->get(self::SETTING_APP_SECRET),
            'redirectUri' => api_get_path(WEB_PLUGIN_PATH).'azure_active_directory/src/callback.php',
        ]);

        return $provider;
    }

    /**
     * @param string $urlType Type of URL to generate
     *
     * @return string
     */
    public function getUrl($urlType)
    {
        if (self::URL_TYPE_LOGOUT === $urlType) {
            $provider = $this->getProvider();

            return $provider->getLogoutUrl(
                api_get_path(WEB_PATH)
            );
        }

        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/src/callback.php';
    }

    /**
     * Create extra fields for user when installing.
     */
    public function install()
    {
        UserManager::create_extra_field(
            self::EXTRA_FIELD_ORGANISATION_EMAIL,
            ExtraField::FIELD_TYPE_TEXT,
            $this->get_lang('OrganisationEmail'),
            ''
        );
        UserManager::create_extra_field(
            self::EXTRA_FIELD_AZURE_ID,
            ExtraField::FIELD_TYPE_TEXT,
            $this->get_lang('AzureId'),
            ''
        );
    }
}
