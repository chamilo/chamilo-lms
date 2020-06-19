<?php
/* For license terms, see /license.txt */

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
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
    // const SETTING_SCOPES = 'scopes';
    // const SETTING_SCOPE_SEPARATOR = 'scope_separator';

    const SETTING_ACCESS_TOKEN_URL = 'access_token_url';
    const SETTING_ACCESS_TOKEN_METHOD = 'access_token_method';
    // const SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID = 'access_token_resource_owner_id';

    const SETTING_RESOURCE_OWNER_DETAILS_URL = 'resource_owner_details_url';

    const SETTING_RESPONSE_ERROR = 'response_error';
    const SETTING_RESPONSE_CODE = 'response_code';
    const SETTING_RESPONSE_RESOURCE_OWNER_ID = 'response_resource_owner_id';

    const SETTING_UPDATE_USER_INFO = 'update_user_info';
    const SETTING_CREATE_NEW_USERS = 'create_new_users';
    const SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME = 'response_resource_owner_firstname';
    const SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME = 'response_resource_owner_lastname';
    const SETTING_RESPONSE_RESOURCE_OWNER_STATUS = 'response_resource_owner_status';
    const SETTING_RESPONSE_RESOURCE_OWNER_EMAIL = 'response_resource_owner_email';
    const SETTING_RESPONSE_RESOURCE_OWNER_USERNAME = 'response_resource_owner_username';

    const SETTING_RESPONSE_RESOURCE_OWNER_URLS = 'response_resource_owner_urls';

    const SETTING_LOGOUT_URL = 'logout_url';

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
                // self::SETTING_SCOPES => 'text',
                // self::SETTING_SCOPE_SEPARATOR => 'text',

                self::SETTING_ACCESS_TOKEN_URL => 'text',
                self::SETTING_ACCESS_TOKEN_METHOD => [
                    'type' => 'select',
                    'options' => [
                        GenericProvider::METHOD_POST => 'POST',
                        GenericProvider::METHOD_GET => 'GET',
                    ],
                ],
                // self::SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID => 'text',

                self::SETTING_RESOURCE_OWNER_DETAILS_URL => 'text',

                self::SETTING_RESPONSE_ERROR => 'text',
                self::SETTING_RESPONSE_CODE => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_ID => 'text',

                self::SETTING_UPDATE_USER_INFO => 'boolean',
                self::SETTING_CREATE_NEW_USERS => 'boolean',
                self::SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME => 'text',

                self::SETTING_RESPONSE_RESOURCE_OWNER_URLS => 'text',

                self::SETTING_LOGOUT_URL => 'text',

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
                // 'scopes' => $this->get(self::SETTING_SCOPES) or null,
                // 'scopeSeparator' => $this->get(self::SETTING_SCOPE_SEPARATOR) ?: ',',

                'urlAccessToken' => $this->get(self::SETTING_ACCESS_TOKEN_URL),
                'accessTokenMethod' => $this->get(self::SETTING_ACCESS_TOKEN_METHOD) ?: GenericProvider::METHOD_POST,
                //'accessTokenResourceOwnerId' => $this->get(self::SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID)
                //    ?: GenericProvider::ACCESS_TOKEN_RESOURCE_OWNER_ID,

                'urlResourceOwnerDetails' => $this->get(self::SETTING_RESOURCE_OWNER_DETAILS_URL),

                'responseError' => $this->get(self::SETTING_RESPONSE_ERROR) ?: 'error',
                'responseCode' => $this->get(self::SETTING_RESPONSE_CODE) ?: null,
                'responseResourceOwnerId' => $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID) ?: 'id',
            ]
        );
    }

    /**
     * @throws IdentityProviderException
     *
     * @return array user information, as returned by api_get_user_info(userId)
     *
     * @var AccessToken
     * @var GenericProvider
     */
    public function getUserInfo($provider, $accessToken)
    {
        $url = $provider->getResourceOwnerDetailsUrl($accessToken);
        $request = $provider->getAuthenticatedRequest($provider::METHOD_GET, $url, $accessToken);
        $response = $provider->getParsedResponse($request);
        if (false === is_array($response)) {
            throw new UnexpectedValueException($this->get_lang('InvalidJsonReceivedFromProvider'));
        }
        $resourceOwnerId = $this->getValueByKey(
            $response,
            $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID)
        );
        if (empty($resourceOwnerId)) {
            throw new RuntimeException($this->get_lang('WrongResponseResourceOwnerId'));
        }
        $extraFieldValue = new ExtraFieldValue('user');
        $result = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            self::EXTRA_FIELD_OAUTH2_ID,
            $resourceOwnerId
        );
        if (false === $result) {
            // authenticated user not found in internal database
            if ('true' !== $this->get(self::SETTING_CREATE_NEW_USERS)) {
                throw new RuntimeException($this->get_lang('NoUserHasThisOauthCode'));
            }
            require_once __DIR__.'/../../../main/auth/external_login/functions.inc.php';
            $userId = external_add_user(
                [
                    'firstname' => $this->getValueByKey($response, $this->get(
                        self::SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME
                    ), $this->get_lang('DefaultFirstname')),
                    'lastname' => $this->getValueByKey($response, $this->get(
                        self::SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME
                    ), $this->get_lang('DefaultLastname')),
                    'status' => $this->getValueByKey($response, $this->get(
                        self::SETTING_RESPONSE_RESOURCE_OWNER_STATUS
                    ), STUDENT),
                    'email' => $this->getValueByKey($response, $this->get(
                        self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL
                    ), 'oauth2user_'.$resourceOwnerId.'@'.(gethostname() or 'localhost')),
                    'username' => $this->getValueByKey($response, $this->get(
                        self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME
                    ), 'oauth2user_'.$resourceOwnerId),
                    'auth_source' => 'oauth2',
                ]
            );
            if (false === $userId) {
                throw new RuntimeException($this->get_lang('FailedUserCreation'));
            }
            $this->updateUser($userId, $response);
            // Not checking function update_extra_field_value return value because not reliable
            UserManager::update_extra_field_value($userId, self::EXTRA_FIELD_OAUTH2_ID, $resourceOwnerId);
            $this->updateUserUrls($userId, $response);
        } else {
            // authenticated user found in internal database
            if (is_array($result) and array_key_exists('item_id', $result)) {
                $userId = $result['item_id'];
            } else {
                $userId = $result;
            }
            if ('true' === $this->get(self::SETTING_UPDATE_USER_INFO)) {
                $this->updateUser($userId, $response);
                $this->updateUserUrls($userId, $response);
            }
        }
        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            throw new LogicException($this->get_lang('InternalErrorCannotGetUserInfo'));
        }

        return $userInfo;
    }

    public function getSignInURL()
    {
        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/src/callback.php';
    }

    public function getLogoutUrl()
    {
        return $this->get(self::SETTING_LOGOUT_URL);
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

    /**
     * Extends ArrayAccessorTrait::getValueByKey to return a list of values
     * $key can contain wild card character *
     * It will be replaced by 0, 1, 2 and so on as long as the resulting key exists in $data
     * This is a recursive function, allowing for more than one occurrence of the wild card character.
     *
     * @param string $key
     * @param array  $default
     *
     * @return array
     */
    private function getValuesByKey(array $data, $key, $default = [])
    {
        if (!is_string($key) || empty($key) || !count($data)) {
            return $default;
        }
        $pos = strpos($key, '*');
        if ($pos === false) {
            $value = $this->getValueByKey($data, $key, null);

            return is_null($value) ? [] : [$value];
        }
        $values = [];
        $beginning = substr($key, 0, $pos);
        $remaining = substr($key, $pos + 1);
        $index = 0;
        do {
            $newValues = $this->getValuesByKey(
                $data,
                $beginning.$index.$remaining
            );
            $values = array_merge($values, $newValues);
            $index++;
        } while ($newValues);

        return $values;
    }

    private function updateUser($userId, $response)
    {
        /**
         * @var $user Chamilo\UserBundle\Entity\User
         */
        $user = UserManager::getRepository()->find($userId);
        $user->setFirstname(
            $this->getValueByKey($response, $this->get(
                self::SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME
            ), $user->getFirstname())
        );
        $user->setLastname(
            $this->getValueByKey($response, $this->get(
                self::SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME
            ), $user->getLastname())
        );
        $user->setUserName(
            $this->getValueByKey($response, $this->get(
                self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME
            ), $user->getUsername())
        );
        $user->setEmail(
            $this->getValueByKey($response, $this->get(
                self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL
            ), $user->getEmail())
        );
        $user->setStatus(
            $this->getValueByKey($response, $this->get(
                self::SETTING_RESPONSE_RESOURCE_OWNER_STATUS
            ), $user->getStatus())
        );
        $user->setAuthSource('oauth2');
        $configFilePath = __DIR__.'/../config.php';
        if (file_exists($configFilePath)) {
            require_once $configFilePath;
            $functionName = 'oauth2UpdateUserFromResourceOwnerDetails';
            if (function_exists($functionName)) {
                $functionName($response, $user);
            }
        }
        UserManager::getManager()->updateUser($user);
    }

    /**
     * Updates the Access URLs associated to a user
     * according to the OAuth2 server response resource owner
     * if multi-URL is enabled and SETTING_RESPONSE_RESOURCE_OWNER_URLS defined.
     *
     * @param $userId integer
     * @param $response array
     */
    private function updateUserUrls($userId, $response)
    {
        if (api_is_multiple_url_enabled()) {
            $key = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_URLS);
            if (!empty($key)) {
                $availableUrls = [];
                foreach (URLManager::get_url_data() as $existingUrl) {
                    $urlId = $existingUrl['id'];
                    $availableUrls[strval($urlId)] = $urlId;
                    $availableUrls[$existingUrl['url']] = $urlId;
                }
                $allowedUrlIds = [];
                foreach ($this->getValuesByKey($response, $key) as $value) {
                    if (array_key_exists($value, $availableUrls)) {
                        $allowedUrlIds[] = $availableUrls[$value];
                    } else {
                        $newValue = ($value[-1] === '/') ? substr($value, 0, -1) : $value.'/';
                        if (array_key_exists($newValue, $availableUrls)) {
                            $allowedUrlIds[] = $availableUrls[$newValue];
                        }
                    }
                }
                $grantedUrlIds = [];
                foreach (URLManager::get_access_url_from_user($userId) as $grantedUrl) {
                    $grantedUrlIds[] = $grantedUrl['access_url_id'];
                }
                foreach (array_diff($grantedUrlIds, $allowedUrlIds) as $extraUrlId) {
                    URLManager::delete_url_rel_user($userId, $extraUrlId);
                }
                foreach (array_diff($allowedUrlIds, $grantedUrlIds) as $missingUrlId) {
                    URLManager::add_user_to_url($userId, $missingUrlId);
                }
            }
        }
    }
}
