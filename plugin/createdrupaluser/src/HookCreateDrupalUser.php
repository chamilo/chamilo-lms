<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookCreateDrupalUser
 * Hook to create an user in Drupal website.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.createDrupalUser
 */
class HookCreateDrupalUser extends HookObserver implements HookCreateUserObserverInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'plugin/createdrupaluser/src/CreateDrupalUser.php',
            'drupaluser'
        );
    }

    /**
     * Create a Drupal user when the Chamilo user is registered.
     *
     * @param HookCreateUserEventInterface $hook The hook
     */
    public function hookCreateUser(HookCreateUserEventInterface $hook)
    {
        $data = $hook->getEventData();

        $drupalDomain = CreateDrupalUser::create()->get('drupal_domain');
        $drupalDomain = rtrim($drupalDomain, '/').'/';

        if ($data['type'] === HOOK_EVENT_TYPE_POST) {
            $return = $data['return'];
            $originalPassword = $data['originalPassword'];

            $userInfo = api_get_user_info($return);
            $fields = [
                'name' => $userInfo['username'],
                'pass' => $originalPassword,
                'mail' => $userInfo['email'],
                'status' => 1,
                'init' => $userInfo['email'],
            ];

            $extraFields = [
                'first_name' => $userInfo['firstname'],
                'last_name' => $userInfo['lastname'],
            ];

            $options = [
                'location' => $drupalDomain.'sites/all/modules/chamilo/soap.php?wsdl',
                'uri' => $drupalDomain,
            ];

            $client = new SoapClient(null, $options);
            $drupalUserId = false;

            if (isset($_SESSION['ws_drupal_user_id'])) {
                $drupalUserId = $_SESSION['ws_drupal_user_id'];

                return true;
            }

            if ($drupalUserId === false) {
                $drupalUserId = $client->addUser($fields, $extraFields);
            }

            if ($drupalUserId !== false) {
                UserManager::update_extra_field_value($return, 'drupal_user_id', $drupalUserId);
            }
        }
    }
}
