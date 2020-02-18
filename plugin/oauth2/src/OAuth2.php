<?php
/* For license terms, see /license.txt */

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use \League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * OAuth2 plugin class.
 *
 * @author Sébastien Ducoulombier <seb@ldd.fr>
 * inspired by AzureActiveDirectory plugin class from Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.oauth2
 */
class OAuth2 extends Plugin
{
    use ArrayAccessorTrait;

    const SETTING_ENABLE = 'enable';

    const SETTING_CLIENT_ID = 'client_id';
    const SETTING_CLIENT_SECRET = 'client_secret';

    const SETTING_AUTHORIZE_URL = 'authorize_url';
    # const SETTING_SCOPES = 'scopes';
    # const SETTING_SCOPE_SEPARATOR = 'scope_separator';

    const SETTING_ACCESS_TOKEN_URL = 'access_token_url';
    const SETTING_ACCESS_TOKEN_METHOD = 'access_token_method';
    # const SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID = 'access_token_resource_owner_id';

    const SETTING_RESOURCE_OWNER_DETAILS_URL = 'resource_owner_details_url';

    const SETTING_RESPONSE_ERROR = 'response_error';
    const SETTING_RESPONSE_CODE = 'response_code';
    const SETTING_RESPONSE_RESOURCE_OWNER_ID = 'response_resource_owner_id';

    const SETTING_BLOCK_NAME = 'block_name';

    const SETTING_MANAGEMENT_LOGIN_ENABLE = 'management_login_enable';
    const SETTING_MANAGEMENT_LOGIN_NAME = 'management_login_name';

    const EXTRA_FIELD_OAUTH2_ID = 'oauth2_id';

    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Sébastien Ducoulombier',
            [
                self::SETTING_ENABLE => 'boolean',

                self::SETTING_CLIENT_ID => 'text',
                self::SETTING_CLIENT_SECRET => 'text',

                self::SETTING_AUTHORIZE_URL => 'text',
                # self::SETTING_SCOPES => 'text',
                # self::SETTING_SCOPE_SEPARATOR => 'text',

                self::SETTING_ACCESS_TOKEN_URL => 'text',
                self::SETTING_ACCESS_TOKEN_METHOD => [
                    'type' => 'select',
                    'options' => [
                        GenericProvider::METHOD_POST => 'POST',
                        GenericProvider::METHOD_GET => 'GET',
                    ]
                ],
                # self::SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID => 'text',

                self::SETTING_RESOURCE_OWNER_DETAILS_URL => 'text',

                self::SETTING_RESPONSE_ERROR => 'text',
                self::SETTING_RESPONSE_CODE => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_ID => 'text',

                self::SETTING_BLOCK_NAME => 'text',

                self::SETTING_MANAGEMENT_LOGIN_ENABLE => 'boolean',
                self::SETTING_MANAGEMENT_LOGIN_NAME => 'text',
            ]
        );
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
        return 'oauth2';
    }

    /**
     * @return GenericProvider
     */
    public function getProvider()
    {
        return new GenericProvider(
            [
                'clientId' => $this->get(self::SETTING_CLIENT_ID),
                'clientSecret' => $this->get(self::SETTING_CLIENT_SECRET),
                'redirectUri' => api_get_path(WEB_PLUGIN_PATH).'oauth2/src/callback.php',

                'urlAuthorize' => $this->get(self::SETTING_AUTHORIZE_URL),
                # 'scopes' => $this->get(self::SETTING_SCOPES) or null,
                # 'scopeSeparator' => $this->get(self::SETTING_SCOPE_SEPARATOR) ?: ',',

                'urlAccessToken' => $this->get(self::SETTING_ACCESS_TOKEN_URL),
                'accessTokenMethod' => $this->get(self::SETTING_ACCESS_TOKEN_METHOD) ?: GenericProvider::METHOD_POST,
                #'accessTokenResourceOwnerId' => $this->get(self::SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID)
                #    ?: GenericProvider::ACCESS_TOKEN_RESOURCE_OWNER_ID,

                'urlResourceOwnerDetails' => $this->get(self::SETTING_RESOURCE_OWNER_DETAILS_URL),

                'responseError' => $this->get(self::SETTING_RESPONSE_ERROR) ?: 'error',
                'responseCode' => $this->get(self::SETTING_RESPONSE_CODE) ?: null,
                'responseResourceOwnerId' => $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID) ?: 'id',
            ]
        );
    }

    /**
     * @return array user information, as returned by api_get_user_info(userId)
     * @throws IdentityProviderException
     * @var AccessToken $accessToken
     * @var GenericProvider $provider
     */
    public function getUserInfo($provider, $accessToken)
    {
        $url = $provider->getResourceOwnerDetailsUrl($accessToken);
        $request = $provider->getAuthenticatedRequest($provider::METHOD_GET, $url, $accessToken);
        $response = $provider->getParsedResponse($request);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                get_lang('invalid_json_received_from_provider')
            );
        }
        $resourceOwnerId = $this->getValueByKey(
            $response,
            $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID)
        );
        if (empty($resourceOwnerId)) {
            throw new RuntimeException(
                get_lang('wrong_response_resource_owner_id')
            );
        }
        $extraFieldValue = new ExtraFieldValue('user');
        $result = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            OAuth2::EXTRA_FIELD_OAUTH2_ID,
            $resourceOwnerId
        );
        if (false === $result) {
            throw new RuntimeException(
                get_lang('no_user_has_this_oauth_code')
            );
        }
        if (is_array($result) and array_key_exists('item_id', $result)) {
            $userId = $result['item_id'];
        } else {
            $userId = $result;
        }
        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            throw new LogicException(
                get_lang('internal_error_cannot_get_user_info')
            );
        }

        return $userInfo;
    }

    public function getSignInURL()
    {
        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/src/callback.php';
    }

    /**
     * Create extra fields for user when installing.
     */
    public function install()
    {
        UserManager::create_extra_field(
            self::EXTRA_FIELD_OAUTH2_ID,
            ExtraField::FIELD_TYPE_TEXT,
            $this->get_lang('OAuth2Id'),
            ''
        );
    }
}
