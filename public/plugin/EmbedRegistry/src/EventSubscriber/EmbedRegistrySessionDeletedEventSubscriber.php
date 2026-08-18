<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionDeletedEvent;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmbedRegistrySessionDeletedEventSubscriber implements EventSubscriberInterface
{
    private EmbedRegistryPlugin $plugin;

    public function __construct()
    {
        $this->plugin = EmbedRegistryPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::SESSION_DELETED => 'onSessionDeleted',
        ];
    }

    public function onSessionDeleted(SessionDeletedEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        $sessionId = $event->getSessionId();

        // Guarded on installed rather than isEnabled(): plugin_embed_registry_embed
        // keeps its foreign key on session while the plugin is disabled, and that
        // key blocks the deletion.
        if (empty($sessionId) || !AppPlugin::getInstance()->isInstalled($this->plugin->get_name())) {
            return;
        }

        Database::getManager()
            ->createQuery('DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.session) = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->execute()
        ;
    }
}
