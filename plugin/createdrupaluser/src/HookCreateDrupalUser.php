<?php
/* For licensing terms, see /license.txt */

/**
 * Hook to create an user in Drupal website
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.createDrupalUser
 */
class HookCreateDrupalUser extends HookObserver implements HookCreateUserObserverInterface
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct(
            'plugin/createdrupaluser/src/CreateDrupalUser.php', 'drupaluser'
        );
    }

    /**
     * Create a Drupal user when the Chamilo user is registered
     * @param HookCreateUserEventInterface $hook The hook
     */
    public function hookCreateUser(HookCreateUserEventInterface $hook)
    {
        $data = $hook->getEventData();

        $drupalDomain = CreateDrupalUser::create()->get('drupal_domain');
        $drupalDomain = rtrim($drupalDomain, '/') . '/';

        if ($data['type'] === HOOK_EVENT_TYPE_POST) {
            $return = $data['return'];
            $originalPassword = $data['originalPassword'];

            $userInfo = UserManager::get_user_info_by_id($return);
            $fields = array(
                'name' => $userInfo['username'],
                'pass' => $originalPassword,
                'mail' => $userInfo['email'],
                'status' => 1,
                'init' => $userInfo['email']
            );
            $extraFields = array(
                'firstname' => $userInfo['firstname'],
                'lastname' => $userInfo['lastname']
            );

            var_dump($drupalDomain . 'sites/all/modules/chamilo/soap.php?wsdl');

            $options = array(
                'location' => $drupalDomain . 'sites/all/modules/chamilo/soap.php?wsdl',
                'uri' => $drupalDomain
            );

            $client = new SoapClient(null, $options);
            $client->addUser($fields, $extraFields);
        }
    }

}
