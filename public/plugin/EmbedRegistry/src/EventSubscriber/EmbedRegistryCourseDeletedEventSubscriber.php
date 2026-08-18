<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseDeletedEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmbedRegistryCourseDeletedEventSubscriber implements EventSubscriberInterface
{
    private EmbedRegistryPlugin $plugin;

    public function __construct()
    {
        $this->plugin = EmbedRegistryPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_DELETED => 'onCourseDeleted',
        ];
    }

    public function onCourseDeleted(CourseDeletedEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        $courseId = $event->getCourseId();

        // Guarded on installed rather than isEnabled(): plugin_embed_registry_embed
        // keeps its foreign key on course while the plugin is disabled, and that key
        // blocks the deletion.
        if (empty($courseId) || !AppPlugin::getInstance()->isInstalled($this->plugin->get_name())) {
            return;
        }

        $this->deleteShortcut($courseId);

        Database::getManager()
            ->createQuery('DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.course) = :courseId')
            ->setParameter('courseId', $courseId)
            ->execute()
        ;
    }

    private function deleteShortcut(int $courseId): void
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist([EmbedRegistryPlugin::TBL_SHORTCUT])) {
            return;
        }

        $shortcutId = (int) $connection->fetchOne(
            'SELECT shortcut_id FROM '.EmbedRegistryPlugin::TBL_SHORTCUT.' WHERE course_id = :courseId',
            ['courseId' => $courseId]
        );

        $connection->executeStatement(
            'DELETE FROM '.EmbedRegistryPlugin::TBL_SHORTCUT.' WHERE course_id = :courseId',
            ['courseId' => $courseId]
        );

        if (0 === $shortcutId) {
            return;
        }

        $shortcut = $em->getRepository(CShortcut::class)->find($shortcutId);

        if ($shortcut instanceof CShortcut) {
            $em->remove($shortcut);
            $em->flush();
        }
    }
}
