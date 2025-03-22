<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\UserCreatedEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateDrupalUserEventSubscriber implements EventSubscriberInterface
{
    private CreateDrupalUser $plugin;

    public function __construct()
    {
        $this->plugin = CreateDrupalUser::create();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::USER_CREATED => 'onCreateUser',
        ];
    }

    public function onCreateUser(UserCreatedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)) {
            return;
        }

        $drupalDomain = $this->plugin->get('drupal_domain');
        $drupalDomain = rtrim($drupalDomain, '/').'/';

        if (HOOK_EVENT_TYPE_POST === $event->getType()) {
            $return = $event->getUser();
            $originalPassword = $event->getOriginalPassword();

            $userInfo = api_get_user_info($return->getId());
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
                //$drupalUserId = $_SESSION['ws_drupal_user_id'];

                return;
            }

            if (false === $drupalUserId) {
                $drupalUserId = $client->addUser($fields, $extraFields);
            }

            if (false !== $drupalUserId) {
                UserManager::update_extra_field_value($return->getId(), 'drupal_user_id', $drupalUserId);
            }
        }
    }
}
