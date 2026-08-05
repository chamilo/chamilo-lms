<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelShortcut;
use Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelTool;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__.'/Entity/Repository/TopLinkRelShortcutRepository.php';
require_once __DIR__.'/Entity/TopLinkRelShortcut.php';

/**
 * Class TopLinksPlugin.
 */
class TopLinksPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '0.3',
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
            []
        );

        $this->isAdminPlugin = true;
        $this->isCoursePlugin = false;
        $this->addCourseTool = false;
        $this->source = 'official';
        $this->type = 'free';
    }

    public static function create(): TopLinksPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function getAdminUrl(): string
    {
        return api_get_path(WEB_PLUGIN_PATH).$this->get_name().'/admin.php';
    }

    /**
     * Kept for backward compatibility with the legacy TopLinks admin flow.
     *
     * Chamilo 2 course home renders shortcuts, not dynamic CTool rows.
     */
    public function addToolInCourse(int $courseId, TopLink $link): void
    {
        $em = Database::getManager();

        /** @var Course|null $course */
        $course = $em->find(Course::class, $courseId);
        if (null === $course) {
            return;
        }

        $this->addShortcutInCourse($course, $link);
    }

    public function addShortcutInCourse(Course $course, TopLink $link): void
    {
        if (null === $link->getId()) {
            return;
        }

        $em = Database::getManager();

        /** @var TopLinkRelShortcut|null $relation */
        $relation = $em->getRepository(TopLinkRelShortcut::class)->findOneByLinkAndCourse($link, $course);
        if (null !== $relation) {
            $shortcut = $relation->getShortcut();

            if (null !== $shortcut) {
                $shortcut->setTitle($link->getTitle());
                $em->persist($shortcut);
                $em->flush();
            }

            return;
        }

        $creator = $course->getCreator();
        if (null === $creator) {
            $creator = api_get_user_entity();
        }

        if (null === $creator) {
            return;
        }

        $shortcut = (new CShortcut())
            ->setTitle($link->getTitle())
            ->setParent($course)
            ->setCreator($creator)
            ->addCourseLink($course)
        ;

        $em->persist($shortcut);
        $em->flush();

        if (null === $shortcut->getResourceNode()) {
            return;
        }

        $shortcut->setShortCutNode($shortcut->getResourceNode());
        $em->persist($shortcut);

        $relation = (new TopLinkRelShortcut())
            ->setLink($link)
            ->setShortcut($shortcut)
        ;

        $em->persist($relation);
        $em->flush();
    }

    public function addShortcutInAllCourses(TopLink $link): void
    {
        $em = Database::getManager();

        /** @var Course $course */
        foreach ($em->getRepository(Course::class)->findAll() as $course) {
            $this->addShortcutInCourse($course, $link);
        }
    }

    public function updateShortcutsForLink(TopLink $link): void
    {
        Database::getManager()
            ->getRepository(TopLinkRelShortcut::class)
            ->updateShortcutTitles($link)
        ;
    }

    public function deleteShortcutsForLink(TopLink $link): void
    {
        $em = Database::getManager();
        $relations = $em->getRepository(TopLinkRelShortcut::class)->findBy(['link' => $link]);

        /** @var TopLinkRelShortcut $relation */
        foreach ($relations as $relation) {
            $shortcut = $relation->getShortcut();

            $em->remove($relation);

            if (null !== $shortcut) {
                $em->remove($shortcut);
            }
        }

        $em->flush();
    }

    public function getMissingCoursesForLink(TopLink $link): array
    {
        return Database::getManager()
            ->getRepository(TopLinkRelShortcut::class)
            ->getMissingCoursesForLink($link)
        ;
    }

    public function install(): void
    {
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        $tableReferences = [
            'toplinks_link' => $em->getClassMetadata(TopLink::class),
            'toplinks_link_rel_tool' => $em->getClassMetadata(TopLinkRelTool::class),
        ];

        $missingMetadata = [];

        foreach ($tableReferences as $tableName => $metadata) {
            if (!$schemaManager->tablesExist([$tableName])) {
                $missingMetadata[] = $metadata;
            }
        }

        if ([] !== $missingMetadata) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema($missingMetadata);
        }

        $this->ensureShortcutRelationTable();
    }

    public function uninstall(): void
    {
        $this->deleteCourseShortcuts();
        $this->deleteCourseTools();
        $this->dropPluginTables();
    }

    public function getIconUrl(?string $imageName): ?string
    {
        $imageName = $this->sanitizeIconName($imageName);

        if (null === $imageName) {
            return null;
        }

        return api_get_path(WEB_PLUGIN_PATH).'TopLinks/image.php?f='.rawurlencode($imageName);
    }

    public function getIconStoragePath(string $imageName): string
    {
        return 'TopLinks/images/'.$imageName;
    }

    public function deleteIcon(?string $imageName): void
    {
        $imageName = $this->sanitizeIconName($imageName);

        if (null === $imageName) {
            return;
        }

        $pluginsFilesystem = Container::getPluginsFileSystem();
        $storagePath = $this->getIconStoragePath($imageName);

        if ($pluginsFilesystem->fileExists($storagePath)) {
            $pluginsFilesystem->delete($storagePath);
        }
    }

    public function sanitizeIconName(?string $imageName): ?string
    {
        $imageName = trim((string) $imageName);

        if ('' === $imageName || basename($imageName) !== $imageName) {
            return null;
        }

        if (!preg_match('/^[a-f0-9]{32}\.(?:png|jpe?g|gif|webp)$/i', $imageName)) {
            return null;
        }

        return $imageName;
    }



    private function ensureShortcutRelationTable(): void
    {
        $connection = Database::getManager()->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if ($schemaManager->tablesExist(['toplinks_link_rel_shortcut'])) {
            return;
        }

        $connection->executeStatement(
            'CREATE TABLE toplinks_link_rel_shortcut (
                id INT AUTO_INCREMENT NOT NULL,
                link_id INT NOT NULL,
                shortcut_id INT NOT NULL,
                INDEX IDX_TOPLINKS_REL_SHORTCUT_LINK (link_id),
                UNIQUE INDEX UNIQ_TOPLINKS_REL_SHORTCUT_SHORTCUT (shortcut_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    private function dropPluginTables(): void
    {
        $schemaManager = Database::getManager()->getConnection()->createSchemaManager();

        foreach (['toplinks_link_rel_shortcut', 'toplinks_link_rel_tool', 'toplinks_link'] as $tableName) {
            if (!$schemaManager->tablesExist([$tableName])) {
                continue;
            }

            $schemaManager->dropTable($tableName);
        }
    }

    private function deleteCourseShortcuts(): void
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['toplinks_link_rel_shortcut'])) {
            return;
        }

        if (class_exists(TopLinkRelShortcut::class)) {
            $relations = $em->getRepository(TopLinkRelShortcut::class)->findAll();

            foreach ($relations as $relation) {
                if (!$relation instanceof TopLinkRelShortcut) {
                    continue;
                }

                $shortcut = $relation->getShortcut();

                $em->remove($relation);

                if (null !== $shortcut) {
                    $em->remove($shortcut);
                }
            }

            $em->flush();

            return;
        }

        $connection->executeStatement(
            'DELETE shortcut
            FROM c_shortcut shortcut
            INNER JOIN toplinks_link_rel_shortcut relation ON relation.shortcut_id = shortcut.id'
        );
        $connection->executeStatement('DELETE FROM toplinks_link_rel_shortcut');
    }

    private function deleteCourseTools(): void
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        if (!$schemaManager->tablesExist(['toplinks_link_rel_tool'])) {
            return;
        }

        $relations = $em->getRepository(TopLinkRelTool::class)->findAll();

        foreach ($relations as $relation) {
            if (!$relation instanceof TopLinkRelTool) {
                continue;
            }

            $tool = $relation->getTool();
            if (null !== $tool) {
                $em->remove($tool);
            }

            $em->remove($relation);
        }

        $em->flush();
    }
}
