<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StudentFollowUpUserDeletedEventSubscriber implements EventSubscriberInterface
{
    private StudentFollowUpPlugin $plugin;

    public function __construct()
    {
        $this->plugin = StudentFollowUpPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::USER_DELETED => 'onUserDeleted',
        ];
    }

    public function onUserDeleted(UserDeletedEvent $event): void
    {
        // TYPE_PRE only: sfu_post keeps a RESTRICT foreign key on user, so the rows
        // have to go before the user row does. And only on a hard delete -- a soft
        // delete keeps the user restorable, so its posts must survive too.
        if (AbstractEvent::TYPE_PRE !== $event->getType() || !$event->isHardDelete()) {
            return;
        }

        $userId = $event->getUser()?->getId();

        // Both spellings, as StudentFollowUpPlugin::getPermissions() does: an older
        // install may hold the lowercase title, and missing it means a foreign key
        // error instead of a cleanup. Guarded on installed rather than isEnabled(),
        // since the rows outlive the plugin being disabled.
        $appPlugin = AppPlugin::getInstance();
        $installed = $appPlugin->isInstalled($this->plugin->get_name())
            || $appPlugin->isInstalled('studentfollowup');

        if (empty($userId) || !$installed) {
            return;
        }

        $connection = Database::getManager()->getConnection();

        $connection->executeStatement('DELETE FROM sfu_post WHERE user_id = :userId', ['userId' => $userId]);
        $connection->executeStatement(
            'DELETE FROM sfu_post WHERE insert_user_id = :userId',
            ['userId' => $userId]
        );
    }
}
