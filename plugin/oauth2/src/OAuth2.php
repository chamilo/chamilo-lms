<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use League\OAuth2\Client\Provider\AbstractProvider;
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

    public const SETTING_ENABLE = 'enable';

    public const SETTING_FORCE_REDIRECT = 'force_redirect';
    public const SETTING_SKIP_FORCE_REDIRECT_IN = 'skip_force_redirect_in';

    public const SETTING_CLIENT_ID = 'client_id';
    public const SETTING_CLIENT_SECRET = 'client_secret';

    public const SETTING_AUTHORIZE_URL = 'authorize_url';
    public const SETTING_SCOPES = 'scopes';
    public const SETTING_SCOPE_SEPARATOR = 'scope_separator';

    public const SETTING_ACCESS_TOKEN_URL = 'access_token_url';
    public const SETTING_ACCESS_TOKEN_METHOD = 'access_token_method';
    // const SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID = 'access_token_resource_owner_id';

    public const SETTING_RESOURCE_OWNER_DETAILS_URL = 'resource_owner_details_url';

    public const SETTING_RESPONSE_ERROR = 'response_error';
    public const SETTING_RESPONSE_CODE = 'response_code';
    public const SETTING_RESPONSE_RESOURCE_OWNER_ID = 'response_resource_owner_id';

    public const SETTING_UPDATE_USER_INFO = 'update_user_info';
    public const SETTING_CREATE_NEW_USERS = 'create_new_users';
    public const SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME = 'response_resource_owner_firstname';
    public const SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME = 'response_resource_owner_lastname';
    public const SETTING_RESPONSE_RESOURCE_OWNER_STATUS = 'response_resource_owner_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_TEACHER_STATUS = 'response_resource_owner_teacher_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_SESSADMIN_STATUS = 'response_resource_owner_sessadmin_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_DRH_STATUS = 'response_resource_owner_drh_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_STUDENT_STATUS = 'response_resource_owner_student_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_ANON_STATUS = 'response_resource_owner_anon_status';
    public const SETTING_RESPONSE_RESOURCE_OWNER_EMAIL = 'response_resource_owner_email';
    public const SETTING_RESPONSE_RESOURCE_OWNER_USERNAME = 'response_resource_owner_username';

    public const SETTING_RESPONSE_RESOURCE_OWNER_URLS = 'response_resource_owner_urls';

    public const SETTING_LOGOUT_URL = 'logout_url';

    public const SETTING_BLOCK_NAME = 'block_name';

    public const SETTING_MANAGEMENT_LOGIN_ENABLE = 'management_login_enable';
    public const SETTING_MANAGEMENT_LOGIN_NAME = 'management_login_name';

    public const SETTING_ALLOW_THIRD_PARTY_LOGIN = 'allow_third_party_login';

    public const EXTRA_FIELD_OAUTH2_ID = 'oauth2_id';

    private const DEBUG = false;

    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Sébastien Ducoulombier',
            [
                self::SETTING_ENABLE => 'boolean',

                self::SETTING_FORCE_REDIRECT => 'boolean',
                self::SETTING_SKIP_FORCE_REDIRECT_IN => 'text',

                self::SETTING_CLIENT_ID => 'text',
                self::SETTING_CLIENT_SECRET => 'text',

                self::SETTING_AUTHORIZE_URL => 'text',
                self::SETTING_SCOPES => 'text',
                self::SETTING_SCOPE_SEPARATOR => 'text',

                self::SETTING_ACCESS_TOKEN_URL => 'text',
                self::SETTING_ACCESS_TOKEN_METHOD => [
                    'type' => 'select',
                    'options' => [
                        AbstractProvider::METHOD_POST => 'POST',
                        AbstractProvider::METHOD_GET => 'GET',
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
                self::SETTING_RESPONSE_RESOURCE_OWNER_TEACHER_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_SESSADMIN_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_DRH_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_STUDENT_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_ANON_STATUS => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL => 'text',
                self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME => 'text',

                self::SETTING_RESPONSE_RESOURCE_OWNER_URLS => 'text',

                self::SETTING_LOGOUT_URL => 'text',

                self::SETTING_BLOCK_NAME => 'text',

                self::SETTING_MANAGEMENT_LOGIN_ENABLE => 'boolean',
                self::SETTING_MANAGEMENT_LOGIN_NAME => 'text',

                self::SETTING_ALLOW_THIRD_PARTY_LOGIN => 'boolean',
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
    public static function create(): OAuth2
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function getProvider(): GenericProvider
    {
        $redirectUri = api_get_path(WEB_PLUGIN_PATH).'oauth2/src/callback.php';
        // In cases not precisely defined yet, this alternative version might be necessary - see BT#20611
        //$redirectUri = api_get_path(WEB_PATH).'authorization-code/callback';
        $options = [
            'clientId' => $this->get(self::SETTING_CLIENT_ID),
            'clientSecret' => $this->get(self::SETTING_CLIENT_SECRET),
            'redirectUri' => $redirectUri,
            'urlAuthorize' => $this->get(self::SETTING_AUTHORIZE_URL),
            'urlResourceOwnerDetails' => $this->get(self::SETTING_RESOURCE_OWNER_DETAILS_URL),
        ];

        if ('' === $scopeSeparator = (string) $this->get(self::SETTING_SCOPE_SEPARATOR)) {
            $scopeSeparator = ' ';
        }

        $options['scopeSeparator'] = $scopeSeparator;

        if ('' !== $scopes = (string) $this->get(self::SETTING_SCOPES)) {
            $options['scopes'] = explode($scopeSeparator, $scopes);
        }

        if ('' !== $urlAccessToken = (string) $this->get(self::SETTING_ACCESS_TOKEN_URL)) {
            $options['urlAccessToken'] = $urlAccessToken;
        }

        if ('' !== $accessTokenMethod = (string) $this->get(self::SETTING_ACCESS_TOKEN_METHOD)) {
            $options['accessTokenMethod'] = $accessTokenMethod;
        }

//        if ('' !== $accessTokenResourceOwnerId = (string) $this->get(self::SETTING_ACCESS_TOKEN_RESOURCE_OWNER_ID)) {
//            $options['accessTokenResourceOwnerId'] = $accessTokenResourceOwnerId;
//        }

        if ('' !== $responseError = (string) $this->get(self::SETTING_RESPONSE_ERROR)) {
            $options['responseError'] = $responseError;
        }

        if ('' !== $responseCode = (string) $this->get(self::SETTING_RESPONSE_CODE)) {
            $options['responseCode'] = $responseCode;
        }

        if ('' !== $responseResourceOwnerId = (string) $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID)) {
            $options['responseResourceOwnerId'] = $responseResourceOwnerId;
        }

        return new GenericProvider($options);
    }

    /**
     * @throws IdentityProviderException
     *
     * @return array user information, as returned by api_get_user_info(userId)
     */
    public function getUserInfo(GenericProvider $provider, AccessToken $accessToken): array
    {
        $url = $provider->getResourceOwnerDetailsUrl($accessToken);
        $request = $provider->getAuthenticatedRequest($provider::METHOD_GET, $url, $accessToken);
        $response = $provider->getParsedResponse($request);
        $this->log('response', print_r($response, true));

        if (false === is_array($response)) {
            $this->log('invalid response', print_r($response, true));
            throw new UnexpectedValueException($this->get_lang('InvalidJsonReceivedFromProvider'));
        }
        $resourceOwnerId = $this->getValueByKey(
            $response,
            $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID)
        );
        if (empty($resourceOwnerId)) {
            $this->log('missing setting', 'response_resource_owner_id');
            throw new RuntimeException($this->get_lang('WrongResponseResourceOwnerId'));
        }
        $this->log('response resource owner id', $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ID));
        $extraFieldValue = new ExtraFieldValue('user');
        $result = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            self::EXTRA_FIELD_OAUTH2_ID,
            $resourceOwnerId
        );
        if (false === $result) {
            $this->log('user not found', "extrafield 'oauth2_id' with value '$resourceOwnerId'");

            $username = $this->getValueByKey(
                $response,
                $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME),
                'oauth2user_'.$resourceOwnerId
            );

            $userInfo = api_get_user_info_from_username($username);

            if (false !== $userInfo && !empty($userInfo['id']) && 'platform' === $userInfo['auth_source']) {
                $this->log('platform user exists', print_r($userInfo, true));

                $userId = $userInfo['id'];
            } else {
                // authenticated user not found in internal database
                if ('true' !== $this->get(self::SETTING_CREATE_NEW_USERS)) {
                    $this->log('exception', 'create_new_users setting is disabled');
                    $message = sprintf(
                        $this->get_lang('NoUserAccountAndUserCreationNotAllowed'),
                        Display::encrypted_mailto_link(api_get_setting('emailAdministrator'))
                    );
                    throw new RuntimeException($message);
                }

                require_once __DIR__.'/../../../main/auth/external_login/functions.inc.php';

                $firstName = $this->getValueByKey(
                    $response,
                    $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME),
                    $this->get_lang('DefaultFirstname')
                );
                $lastName = $this->getValueByKey(
                    $response,
                    $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME),
                    $this->get_lang('DefaultLastname')
                );
                $status = $this->mapUserStatusFromResponse($response);
                $email = $this->getValueByKey(
                    $response,
                    $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL),
                    'oauth2user_'.$resourceOwnerId.'@'.(gethostname() or 'localhost')
                );

                $userInfo = [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'status' => $status,
                    'email' => $email,
                    'username' => $username,
                    'auth_source' => 'oauth2',
                ];
                $userId = external_add_user($userInfo);
                if (false === $userId) {
                    $this->log('user not created', print_r($userInfo, true));
                    throw new RuntimeException($this->get_lang('FailedUserCreation'));
                }
                $this->log('user created', (string) $userId);
            }

            $this->updateUser($userId, $response);
            // Not checking function update_extra_field_value return value because not reliable
            UserManager::update_extra_field_value($userId, self::EXTRA_FIELD_OAUTH2_ID, $resourceOwnerId);
            $this->updateUserUrls($userId, $response);
        } else {
            $this->log('user found', "extrafield 'oauth2_id' with value '$resourceOwnerId'");
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
            $this->log('user info not found', (string) $userId);
            throw new LogicException($this->get_lang('InternalErrorCannotGetUserInfo'));
        }

        $this->log('user info', print_r($userInfo, true));

        return $userInfo;
    }

    public function getSignInURL(): string
    {
        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/src/callback.php';
        // In cases not precisely defined yet, this alternative version might be necessary - see BT#20611
        //return api_get_path(WEB_PATH).'authorization-code/callback';
    }

    public function getLogoutUrl(): string
    {
        $token = ChamiloSession::read('oauth2AccessToken');
        $idToken = !empty($token['id_token']) ? $token['id_token'] : null;

        return $this->get(self::SETTING_LOGOUT_URL).'?'.http_build_query(
            [
                'id_token_hint' => $idToken,
                'post_logout_redirect_uri' => api_get_path(WEB_PATH),
            ]
        );
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

    public static function isFirstLoginAfterAuthSource(int $userId): bool
    {
        $em = Database::getManager();

        $lastLogin = $em
            ->getRepository(TrackELogin::class)
            ->findOneBy(
                ['loginUserId' => $userId],
                ['loginDate' => 'DESC']
            )
        ;

        if (!$lastLogin) {
            return false;
        }

        $objExtraField = new ExtraField('user');
        $field = $objExtraField->getHandlerEntityByFieldVariable(self::EXTRA_FIELD_OAUTH2_ID);

        $fieldValue = $em
            ->getRepository(ExtraFieldValues::class)
            ->findOneBy(
                ['itemId' => $userId, 'field' => $field]
            )
        ;

        if (!$fieldValue) {
            return false;
        }

        return $fieldValue->getCreatedAt() >= $lastLogin->getLoginDate();
    }

    private function mapUserStatusFromResponse(array $response, int $defaultStatus = STUDENT): int
    {
        $status = $this->getValueByKey(
            $response,
            $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_STATUS),
            $defaultStatus
        );

        $responseStatus = [];

        if ($teacherStatus = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_TEACHER_STATUS)) {
            $responseStatus[COURSEMANAGER] = $teacherStatus;
        }

        if ($sessAdminStatus = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_SESSADMIN_STATUS)) {
            $responseStatus[SESSIONADMIN] = $sessAdminStatus;
        }

        if ($drhStatus = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_DRH_STATUS)) {
            $responseStatus[DRH] = $drhStatus;
        }

        if ($studentStatus = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_STUDENT_STATUS)) {
            $responseStatus[STUDENT] = $studentStatus;
        }

        if ($anonStatus = $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_ANON_STATUS)) {
            $responseStatus[ANONYMOUS] = $anonStatus;
        }

        $map = array_flip($responseStatus);

        return $map[$status] ?? $status;
    }

    /**
     * Extends ArrayAccessorTrait::getValueByKey to return a list of values
     * $key can contain wild card character *
     * It will be replaced by 0, 1, 2 and so on as long as the resulting key exists in $data
     * This is a recursive function, allowing for more than one occurrence of the wild card character.
     */
    private function getValuesByKey(array $data, string $key, array $default = []): array
    {
        if (!is_string($key) || empty($key) || !count($data)) {
            return $default;
        }
        $pos = strpos($key, '*');
        if ($pos === false) {
            $value = $this->getValueByKey($data, $key);

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

    /**
     * @throws Exception
     */
    private function updateUser($userId, $response)
    {
        $user = UserManager::getRepository()->find($userId);
        $user->setFirstname(
            $this->getValueByKey(
                $response,
                $this->get(
                    self::SETTING_RESPONSE_RESOURCE_OWNER_FIRSTNAME
                ),
                $user->getFirstname()
            )
        );
        $user->setLastname(
            $this->getValueByKey(
                $response,
                $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_LASTNAME),
                $user->getLastname()
            )
        );
        $user->setUserName(
            $this->getValueByKey(
                $response,
                $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_USERNAME),
                $user->getUsername()
            )
        );
        $user->setEmail(
            $this->getValueByKey(
                $response,
                $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_EMAIL),
                $user->getEmail()
            )
        );
        $status = $this->mapUserStatusFromResponse(
            $response,
            $user->getStatus()
        );
        $user->setStatus($status);
        $user->setAuthSource('oauth2');
        $configFilePath = __DIR__.'/../config.php';
        if (file_exists($configFilePath)) {
            require_once $configFilePath;
            $functionName = 'oauth2UpdateUserFromResourceOwnerDetails';
            if (function_exists($functionName)) {
                $functionName($response, $user);
            }
        }

        try {
            UserManager::getManager()->updateUser($user);
        } catch (UniqueConstraintViolationException $exception) {
            throw new Exception(get_lang('UserNameUsedTwice'));
        }
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
            $key = (string) $this->get(self::SETTING_RESPONSE_RESOURCE_OWNER_URLS);
            if (!empty($key)) {
                $availableUrls = [];
                foreach (UrlManager::get_url_data() as $existingUrl) {
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
                foreach (UrlManager::get_access_url_from_user($userId) as $grantedUrl) {
                    $grantedUrlIds[] = $grantedUrl['access_url_id'];
                }
                foreach (array_diff($grantedUrlIds, $allowedUrlIds) as $extraUrlId) {
                    UrlManager::delete_url_rel_user($userId, $extraUrlId);
                }
                foreach (array_diff($allowedUrlIds, $grantedUrlIds) as $missingUrlId) {
                    UrlManager::add_user_to_url($userId, $missingUrlId);
                }
            }
        }
    }

    private function log(string $key, string $content)
    {
        if (self::DEBUG) {
            error_log("OAuth2 plugin: $key: $content");
        }
    }
}
