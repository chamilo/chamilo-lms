<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query;

final class Version20260922123000 extends AbstractMigrationChamilo
{
    private const string SETTING_VARIABLE = 'toolbox';
    private const string TOOL_TITLE = 'toolbox';
    private const int FLUSH_BATCH_SIZE = 200;

    private const array REQUIRED_SETTING_COLUMNS = [
        'access_url',
        'variable',
        'subkey',
        'type',
        'category',
        'selected_value',
        'title',
        'comment',
        'scope',
        'subkeytext',
        'access_url_changeable',
        'access_url_locked',
        'value_template_id',
    ];

    public function getDescription(): string
    {
        return 'Add the AI Toolbox foundation, version storage, setting and course tool.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_toolbox')) {
            $this->addSql(
                'CREATE TABLE c_toolbox (
                    iid INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    current_version INT DEFAULT 1 NOT NULL,
                    UNIQUE INDEX UNIQ_C_TOOLBOX_RESOURCE_NODE (resource_node_id),
                    PRIMARY KEY(iid),
                    CONSTRAINT FK_C_TOOLBOX_RESOURCE_NODE FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        if (!$schema->hasTable('c_toolbox_version')) {
            $this->addSql(
                'CREATE TABLE c_toolbox_version (
                    id INT AUTO_INCREMENT NOT NULL,
                    toolbox_id INT NOT NULL,
                    created_by_id INT DEFAULT NULL,
                    version_number INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    prompt LONGTEXT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX IDX_C_TOOLBOX_VERSION_TOOLBOX (toolbox_id),
                    INDEX IDX_C_TOOLBOX_VERSION_CREATOR (created_by_id),
                    UNIQUE INDEX uniq_toolbox_version_number (toolbox_id, version_number),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_C_TOOLBOX_VERSION_TOOLBOX FOREIGN KEY (toolbox_id) REFERENCES c_toolbox (iid) ON DELETE CASCADE,
                    CONSTRAINT FK_C_TOOLBOX_VERSION_CREATOR FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );
        }

        foreach (['settings', 'settings_current'] as $table) {
            if ($this->supportsSettingInsert($schema, $table)) {
                $this->insertToolboxSetting($table);
            }
        }

        /** @var ToolChain $toolChain */
        $toolChain = $this->container->get(ToolChain::class);
        $toolChain->createTools();

        $toolId = $this->entityManager
            ->createQuery('SELECT t.id FROM Chamilo\CoreBundle\Entity\Tool t WHERE t.title = :title')
            ->setParameter('title', self::TOOL_TITLE)
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR)
        ;

        if (null === $toolId) {
            $this->write('Toolbox tool definition was not created; course-tool seeding skipped.');

            return;
        }

        $toolId = (int) $toolId;
        $created = 0;
        $query = $this->entityManager->createQuery('SELECT c.id FROM Chamilo\CoreBundle\Entity\Course c');

        foreach ($query->toIterable([], Query::HYDRATE_SCALAR) as $row) {
            $courseId = (int) $row['id'];
            $courseRef = $this->entityManager->getReference(Course::class, $courseId);

            $exists = (int) $this->entityManager
                ->createQuery(
                    'SELECT COUNT(ct.iid)
                       FROM Chamilo\CourseBundle\Entity\CTool ct
                      WHERE ct.title = :title
                        AND ct.course = :course'
                )
                ->setParameter('title', self::TOOL_TITLE)
                ->setParameter('course', $courseRef)
                ->getSingleScalarResult()
            ;

            if ($exists > 0) {
                continue;
            }

            $toolRef = $this->entityManager->getReference(Tool::class, $toolId);
            $course = $this->entityManager->find(Course::class, $courseId);
            if (!$course instanceof Course) {
                continue;
            }

            $courseTool = (new CTool())
                ->setTool($toolRef)
                ->setTitle(self::TOOL_TITLE)
                ->setCourse($course)
                ->setParent($course)
                ->setCreator($course->getCreator())
                ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_DRAFT)
            ;
            $this->entityManager->persist($courseTool);

            ++$created;
            if (0 === $created % self::FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $tableSchema = $schema->getTable($table);
            if (!$tableSchema->hasColumn('variable') || !$tableSchema->hasColumn('category')) {
                continue;
            }

            $this->addSql(
                \sprintf('DELETE FROM %s WHERE variable = ? AND category = ?', $table),
                [self::SETTING_VARIABLE, 'ai_helpers']
            );
        }

        if ($schema->hasTable('c_toolbox_version')) {
            $this->addSql('DROP TABLE c_toolbox_version');
        }

        if ($schema->hasTable('c_toolbox')) {
            $this->addSql('DROP TABLE c_toolbox');
        }

        // Course-tool/resource-node cleanup is intentionally not automated in down().
    }

    private function supportsSettingInsert(Schema $schema, string $table): bool
    {
        if (!$schema->hasTable($table)) {
            return false;
        }

        $tableSchema = $schema->getTable($table);
        foreach (self::REQUIRED_SETTING_COLUMNS as $column) {
            if (!$tableSchema->hasColumn($column)) {
                return false;
            }
        }

        return true;
    }

    private function insertToolboxSetting(string $table): void
    {
        $this->addSql(
            \sprintf(
                <<<'SQL'
INSERT INTO %1$s (
    access_url,
    variable,
    subkey,
    type,
    category,
    selected_value,
    title,
    comment,
    scope,
    subkeytext,
    access_url_changeable,
    access_url_locked,
    value_template_id
)
SELECT
    source.access_url,
    ?,
    source.subkey,
    source.type,
    ?,
    ?,
    ?,
    ?,
    source.scope,
    source.subkeytext,
    source.access_url_changeable,
    source.access_url_locked,
    source.value_template_id
FROM %1$s source
WHERE source.variable = ?
  AND NOT EXISTS (
      SELECT 1
      FROM %1$s existing
      WHERE existing.variable = ?
        AND (
            existing.access_url = source.access_url
            OR (existing.access_url IS NULL AND source.access_url IS NULL)
        )
        AND (
            existing.subkey = source.subkey
            OR (existing.subkey IS NULL AND source.subkey IS NULL)
        )
  )
SQL,
                $table
            ),
            [
                self::SETTING_VARIABLE,
                'ai_helpers',
                'false',
                'AI Toolbox',
                'Allows teachers to create and version AI-assisted educational applications inside courses.',
                'enable_ai_helpers',
                self::SETTING_VARIABLE,
            ]
        );
    }
}
