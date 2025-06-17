<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\AzureActiveDirectory\AzureSyncState;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
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
    public const SETTING_UPDATE_USERS = 'update_users';
    public const SETTING_GROUP_ID_ADMIN = 'group_id_admin';
    public const SETTING_GROUP_ID_SESSION_ADMIN = 'group_id_session_admin';
    public const SETTING_GROUP_ID_TEACHER = 'group_id_teacher';
    public const SETTING_EXISTING_USER_VERIFICATION_ORDER = 'existing_user_verification_order';
    public const SETTING_TENANT_ID = 'tenant_id';
    public const SETTING_DEACTIVATE_NONEXISTING_USERS = 'deactivate_nonexisting_users';
    public const SETTING_GET_USERS_DELTA = 'script_users_delta';
    public const SETTING_GET_USERGROUPS_DELTA = 'script_usergroups_delta';
    public const SETTING_GROUP_FILTER = 'group_filter_regex';

    public const URL_TYPE_AUTHORIZE = 'login';
    public const URL_TYPE_LOGOUT = 'logout';

    public const EXTRA_FIELD_ORGANISATION_EMAIL = 'organisationemail';
    public const EXTRA_FIELD_AZURE_ID = 'azure_id';
    public const EXTRA_FIELD_AZURE_UID = 'azure_uid';

    public const API_PAGE_SIZE = 999;

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
            self::SETTING_UPDATE_USERS => 'boolean',
            self::SETTING_GROUP_ID_ADMIN => 'text',
            self::SETTING_GROUP_ID_SESSION_ADMIN => 'text',
            self::SETTING_GROUP_ID_TEACHER => 'text',
            self::SETTING_EXISTING_USER_VERIFICATION_ORDER => 'text',
            self::SETTING_TENANT_ID => 'text',
            self::SETTING_DEACTIVATE_NONEXISTING_USERS => 'boolean',
            self::SETTING_GET_USERS_DELTA => 'boolean',
            self::SETTING_GET_USERGROUPS_DELTA => 'boolean',
            self::SETTING_GROUP_FILTER => 'text',
        ];

        parent::__construct('2.5', 'Angel Fernando Quiroz Campos, Yannick Warnier', $settings);
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

    public function getProvider(): Azure
    {
        return new Azure([
            'clientId' => $this->get(self::SETTING_APP_ID),
            'clientSecret' => $this->get(self::SETTING_APP_SECRET),
            'redirectUri' => api_get_path(WEB_PLUGIN_PATH).'azure_active_directory/src/callback.php',
            'urlAPI' => 'https://graph.microsoft.com/v1.0/',
            'resource' => 'https://graph.microsoft.com',
        ]);
    }

    public function getProviderForApiGraph(): Azure
    {
        $provider = $this->getProvider();
        $provider->tenant = $this->get(AzureActiveDirectory::SETTING_TENANT_ID);
        $provider->authWithResource = false;

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
     *
     * @throws ToolsException
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
        UserManager::create_extra_field(
            self::EXTRA_FIELD_AZURE_UID,
            ExtraField::FIELD_TYPE_TEXT,
            $this->get_lang('AzureUid'),
            ''
        );

        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist(['azure_ad_sync_state'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(AzureSyncState::class),
            ]
        );
    }

    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist(['azure_ad_sync_state'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(AzureSyncState::class),
            ]
        );
    }

    public function getExistingUserVerificationOrder(): array
    {
        $defaultOrder = [1, 2, 3];

        $settingValue = $this->get(self::SETTING_EXISTING_USER_VERIFICATION_ORDER);
        $selectedOrder = array_filter(
            array_map(
                'trim',
                explode(',', $settingValue)
            )
        );
        $selectedOrder = array_map('intval', $selectedOrder);
        $selectedOrder = array_filter(
            $selectedOrder,
            function ($position) use ($defaultOrder): bool {
                return in_array($position, $defaultOrder);
            }
        );

        if ($selectedOrder) {
            return $selectedOrder;
        }

        return $defaultOrder;
    }

    public function getUserIdByVerificationOrder(array $azureUserData): ?int
    {
        $selectedOrder = $this->getExistingUserVerificationOrder();

        $extraFieldValue = new ExtraFieldValue('user');
        $positionsAndFields = [
            1 => $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                AzureActiveDirectory::EXTRA_FIELD_ORGANISATION_EMAIL,
                $azureUserData['mail']
            ),
            2 => $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                AzureActiveDirectory::EXTRA_FIELD_AZURE_ID,
                $azureUserData['mailNickname']
            ),
            3 => $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                AzureActiveDirectory::EXTRA_FIELD_AZURE_UID,
                $azureUserData['id']
            ),
        ];

        foreach ($selectedOrder as $position) {
            if (!empty($positionsAndFields[$position]) && isset($positionsAndFields[$position]['item_id'])) {
                return (int) $positionsAndFields[$position]['item_id'];
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function registerUser(array $azureUserInfo)
    {
        if (empty($azureUserInfo)) {
            throw new Exception('Groups info not found.');
        }

        $userId = $this->getUserIdByVerificationOrder($azureUserInfo);

        if (empty($userId)) {
            // If we didn't find the user
            if ($this->get(self::SETTING_PROVISION_USERS) !== 'true') {
                throw new Exception('User not found when checking the extra fields from '.$azureUserInfo['mail'].' or '.$azureUserInfo['mailNickname'].' or '.$azureUserInfo['id'].'.');
            }

            [
                $firstNme,
                $lastName,
                $username,
                $email,
                $phone,
                $authSource,
                $active,
                $extra,
            ] = $this->formatUserData($azureUserInfo);

            // If the option is set to create users, create it
            $userId = UserManager::create_user(
                $firstNme,
                $lastName,
                STUDENT,
                $email,
                $username,
                '',
                null,
                null,
                $phone,
                null,
                $authSource,
                null,
                $active,
                null,
                $extra,
                null,
                null
            );

            if (!$userId) {
                throw new Exception(get_lang('UserNotAdded').' '.$azureUserInfo['userPrincipalName']);
            }

            return $userId;
        }

        if ($this->get(self::SETTING_UPDATE_USERS) === 'true') {
            [
                $firstNme,
                $lastName,
                $username,
                $email,
                $phone,
                $authSource,
                $active,
                $extra,
            ] = $this->formatUserData($azureUserInfo);

            $userId = UserManager::update_user(
                $userId,
                $firstNme,
                $lastName,
                $username,
                '',
                $authSource,
                $email,
                STUDENT,
                null,
                $phone,
                null,
                null,
                $active,
                null,
                0,
                $extra
            );

            if (!$userId) {
                throw new Exception(get_lang('CouldNotUpdateUser').' '.$azureUserInfo['userPrincipalName']);
            }
        }

        return $userId;
    }

    /**
     * @return array<string, string|false>
     */
    public function getGroupUidByRole(): array
    {
        $groupUidList = [
            'admin' => $this->get(self::SETTING_GROUP_ID_ADMIN),
            'sessionAdmin' => $this->get(self::SETTING_GROUP_ID_SESSION_ADMIN),
            'teacher' => $this->get(self::SETTING_GROUP_ID_TEACHER),
        ];

        return array_filter($groupUidList);
    }

    /**
     * @return array<string, callable>
     */
    public function getUpdateActionByRole(): array
    {
        return [
            'admin' => function (User $user) {
                $user->setStatus(COURSEMANAGER);

                UserManager::addUserAsAdmin($user, false);
            },
            'sessionAdmin' => function (User $user) {
                $user->setStatus(SESSIONADMIN);

                UserManager::removeUserAdmin($user, false);
            },
            'teacher' => function (User $user) {
                $user->setStatus(COURSEMANAGER);

                UserManager::removeUserAdmin($user, false);
            },
        ];
    }

    public function getSyncState(string $title): ?AzureSyncState
    {
        $stateRepo = Database::getManager()->getRepository(AzureSyncState::class);

        return $stateRepo->findOneBy(['title' => $title]);
    }

    public function saveSyncState(string $title, $value)
    {
        $state = $this->getSyncState($title);

        if (!$state) {
            $state = new AzureSyncState();
            $state->setTitle($title);

            Database::getManager()->persist($state);
        }

        $state->setValue($value);

        Database::getManager()->flush();
    }

    /**
     * @throws Exception
     */
    private function formatUserData(array $azureUserInfo): array
    {
        $phone = null;

        if (isset($azureUserInfo['telephoneNumber'])) {
            $phone = $azureUserInfo['telephoneNumber'];
        } elseif (isset($azureUserInfo['businessPhones'][0])) {
            $phone = $azureUserInfo['businessPhones'][0];
        } elseif (isset($azureUserInfo['mobilePhone'])) {
            $phone = $azureUserInfo['mobilePhone'];
        }

        // If the option is set to create users, create it
        $firstNme = $azureUserInfo['givenName'];
        $lastName = $azureUserInfo['surname'];
        $email = $azureUserInfo['mail'];
        $username = $azureUserInfo['userPrincipalName'];
        $authSource = 'azure';
        $active = ($azureUserInfo['accountEnabled'] ? 1 : 0);
        $extra = [
            'extra_'.self::EXTRA_FIELD_ORGANISATION_EMAIL => $azureUserInfo['mail'],
            'extra_'.self::EXTRA_FIELD_AZURE_ID => $azureUserInfo['mailNickname'],
            'extra_'.self::EXTRA_FIELD_AZURE_UID => $azureUserInfo['id'],
        ];

        return [
            $firstNme,
            $lastName,
            $username,
            $email,
            $phone,
            $authSource,
            $active,
            $extra,
        ];
    }
}
