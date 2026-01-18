<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;

final class Version20251229113500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate legacy c_tool.visibility into resource_link.visibility, then drop c_tool.visibility column.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $cToolCols = $sm->listTableColumns('c_tool');

        if (!isset($cToolCols['visibility'])) {
            $this->write('c_tool.visibility not found, skipping migration.');

            return;
        }

        // Detect the resource node FK column name on c_tool (most commonly "resource_node_id")
        $nodeCol = null;
        foreach (['resource_node_id', 'resourceNode_id'] as $candidate) {
            if (isset($cToolCols[$candidate])) {
                $nodeCol = $candidate;

                break;
            }
        }

        if (null === $nodeCol) {
            $this->write('Could not detect resource node column on c_tool (expected resource_node_id). Aborting.');

            return;
        }

        // Update existing ResourceLinks matching the same context as CTool
        // Mapping: boolean -> int visibility
        $sqlUpdate = \sprintf(
            'UPDATE resource_link rl
             INNER JOIN c_tool ct
                ON rl.resource_node_id = ct.%s
               AND rl.c_id = ct.c_id
               AND (
                    (rl.session_id IS NULL AND ct.session_id IS NULL)
                    OR (rl.session_id = ct.session_id)
               )
               AND rl.user_id IS NULL
               AND rl.usergroup_id IS NULL
               AND rl.group_id IS NULL
               AND rl.deleted_at IS NULL
             SET rl.visibility = CASE
                 WHEN ct.visibility = 1 THEN %d
                 ELSE %d
             END
             WHERE ct.visibility IS NOT NULL',
            $nodeCol,
            ResourceLink::VISIBILITY_PUBLISHED,
            ResourceLink::VISIBILITY_DRAFT
        );

        $affected = $this->connection->executeStatement($sqlUpdate);
        $this->write(\sprintf('Updated resource_link rows: %d', $affected));

        //  Create missing links (rare, but safe)
        $sqlMissing = \sprintf(
            'SELECT ct.iid, ct.c_id, ct.session_id, ct.%s AS node_id, ct.visibility
               FROM c_tool ct
               LEFT JOIN resource_link rl
                 ON rl.resource_node_id = ct.%s
                AND rl.c_id = ct.c_id
                AND (
                     (rl.session_id IS NULL AND ct.session_id IS NULL)
                     OR (rl.session_id = ct.session_id)
                )
                AND rl.user_id IS NULL
                AND rl.usergroup_id IS NULL
                AND rl.group_id IS NULL
                AND rl.deleted_at IS NULL
              WHERE ct.visibility IS NOT NULL
                AND rl.id IS NULL',
            $nodeCol,
            $nodeCol
        );

        $missing = $this->connection->fetchAllAssociative($sqlMissing);

        $created = 0;
        foreach ($missing as $row) {
            $iid = (int) ($row['iid'] ?? 0);
            $courseId = (int) ($row['c_id'] ?? 0);
            $sessionId = isset($row['session_id']) ? (int) $row['session_id'] : 0;
            $legacyVisibility = (int) ($row['visibility'] ?? 1);

            if ($iid <= 0 || $courseId <= 0) {
                continue;
            }

            $ctool = $this->entityManager->find(CTool::class, $iid);
            if (!$ctool) {
                continue;
            }

            $courseRef = $this->entityManager->getReference(Course::class, $courseId);
            $sessionRef = $sessionId > 0
                ? $this->entityManager->getReference(Session::class, $sessionId)
                : null;

            $linkVisibility = $legacyVisibility
                ? ResourceLink::VISIBILITY_PUBLISHED
                : ResourceLink::VISIBILITY_DRAFT;

            // Create link for the exact same context (course + session, group/user null)
            $ctool->addCourseLink($courseRef, $sessionRef, null, $linkVisibility);

            $this->entityManager->persist($ctool);
            $created++;

            if (($created % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->write(\sprintf('Created missing resource_link rows: %d', $created));

        // Drop legacy column
        $this->addSql('ALTER TABLE c_tool DROP COLUMN visibility');
        $this->write('Dropped column c_tool.visibility');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_tool ADD visibility TINYINT(1) DEFAULT 1');
    }
}
