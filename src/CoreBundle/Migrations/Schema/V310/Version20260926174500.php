<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20260926174500 extends AbstractMigrationChamilo
{
    private const string RESOURCE_NODE_FK = 'FK_GRADEBOOK_LINK_RESOURCE_NODE';
    private const string RESOURCE_NODE_INDEX = 'idx_gl_ref';

    /**
     * @var array<string, list<int>>
     */
    private const array RESOURCE_TYPE_MAP = [
        'c_quiz' => [1, 9],
        'c_student_publication' => [3],
        'c_lp' => [4],
        'c_forum_thread' => [5, 11],
        'c_attendance' => [7],
        'c_survey' => [8],
    ];

    public function getDescription(): string
    {
        return 'Convert gradebook_link.ref_id from tool iid values to resource_node.id and add the foreign key.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('gradebook_link') || !$schema->hasTable('resource_node')) {
            return;
        }

        $orphanLinkIds = $this->assertLegacyLinksCanBeConverted($schema);
        if ([] !== $orphanLinkIds) {
            // These links point to a tool item deleted in 1.11.x: they can never be displayed nor
            // scored, and would break the foreign key added below.
            $this->write(\sprintf(
                'Deleting %d orphan gradebook link(s) whose tool item no longer exists: gradebook_link.id %s.',
                \count($orphanLinkIds),
                implode(', ', $orphanLinkIds),
            ));
            $this->addSql('DELETE FROM gradebook_link WHERE id IN ('.implode(', ', $orphanLinkIds).')');
        }

        foreach (self::RESOURCE_TYPE_MAP as $resourceTable => $types) {
            $typeList = implode(', ', $types);
            $this->addSql(
                \sprintf(
                    'UPDATE gradebook_link gl INNER JOIN %s resource ON resource.iid = gl.ref_id SET gl.ref_id = resource.resource_node_id WHERE gl.type IN (%s)',
                    $resourceTable,
                    $typeList,
                ),
            );
        }

        $table = $schema->getTable('gradebook_link');
        if (!$table->hasIndex(self::RESOURCE_NODE_INDEX)) {
            $this->addSql('CREATE INDEX '.self::RESOURCE_NODE_INDEX.' ON gradebook_link (ref_id)');
        }
        if (!$table->hasForeignKey(self::RESOURCE_NODE_FK)) {
            $this->addSql(
                'ALTER TABLE gradebook_link ADD CONSTRAINT '.self::RESOURCE_NODE_FK.' FOREIGN KEY (ref_id) REFERENCES resource_node (id) ON DELETE CASCADE',
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('gradebook_link')) {
            return;
        }

        $table = $schema->getTable('gradebook_link');
        if ($table->hasForeignKey(self::RESOURCE_NODE_FK)) {
            $this->addSql('ALTER TABLE gradebook_link DROP FOREIGN KEY '.self::RESOURCE_NODE_FK);
        }
        if ($table->hasIndex(self::RESOURCE_NODE_INDEX)) {
            $this->addSql('DROP INDEX '.self::RESOURCE_NODE_INDEX.' ON gradebook_link');
        }

        foreach (self::RESOURCE_TYPE_MAP as $resourceTable => $types) {
            if (!$schema->hasTable($resourceTable)) {
                continue;
            }

            $typeList = implode(', ', $types);
            $this->addSql(
                \sprintf(
                    'UPDATE gradebook_link gl INNER JOIN %s resource ON resource.resource_node_id = gl.ref_id SET gl.ref_id = resource.iid WHERE gl.type IN (%s)',
                    $resourceTable,
                    $typeList,
                ),
            );
        }
    }

    /**
     * @return list<int> IDs of links whose tool item no longer exists, to delete before converting
     */
    private function assertLegacyLinksCanBeConverted(Schema $schema): array
    {
        $orphanLinkIds = [];
        $supportedTypes = [];
        foreach (self::RESOURCE_TYPE_MAP as $types) {
            $supportedTypes = [...$supportedTypes, ...$types];
        }

        $unsupportedTypes = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT type FROM gradebook_link WHERE type NOT IN ('.implode(', ', $supportedTypes).') ORDER BY type',
        );
        if ([] !== $unsupportedTypes) {
            throw new RuntimeException('Cannot convert gradebook_link.ref_id because unsupported legacy link types exist: '.implode(', ', array_map('strval', $unsupportedTypes)).'. No data was changed.');
        }

        foreach (self::RESOURCE_TYPE_MAP as $resourceTable => $types) {
            $typeList = implode(', ', $types);
            $linkCount = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM gradebook_link WHERE type IN ('.$typeList.')',
            );
            if (0 === $linkCount) {
                continue;
            }

            if (!$schema->hasTable($resourceTable)) {
                throw new RuntimeException(\sprintf('Cannot convert gradebook_link.ref_id because required table "%s" is missing. No data was changed.', $resourceTable));
            }

            $orphanLinkIds = [
                ...$orphanLinkIds,
                ...array_map('intval', $this->connection->fetchFirstColumn(
                    \sprintf(
                        'SELECT gl.id FROM gradebook_link gl LEFT JOIN %1$s resource ON resource.iid = gl.ref_id WHERE gl.type IN (%2$s) AND resource.iid IS NULL',
                        $resourceTable,
                        $typeList,
                    ),
                )),
            ];

            $invalidCount = (int) $this->connection->fetchOne(
                \sprintf(
                    'SELECT COUNT(*) FROM gradebook_link gl INNER JOIN %1$s resource ON resource.iid = gl.ref_id LEFT JOIN resource_node rn ON rn.id = resource.resource_node_id WHERE gl.type IN (%2$s) AND (resource.resource_node_id IS NULL OR rn.id IS NULL)',
                    $resourceTable,
                    $typeList,
                ),
            );
            if ($invalidCount > 0) {
                throw new RuntimeException(\sprintf('Cannot convert %d gradebook link(s) for table "%s" because the tool item exists but has no existing resource_node. No data was changed.', $invalidCount, $resourceTable));
            }
        }

        sort($orphanLinkIds);

        return $orphanLinkIds;
    }
}
