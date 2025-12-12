<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251212104300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Prefill resource_node.language_id from course language and copy it to resource_file.language_id when missing.';
    }

    public function up(Schema $schema): void
    {
        // Decide whether we can join directly via resource_node.cid (preferred)
        // or need a fallback via resource_link.c_id (when cid is not present).
        $hasCid = false;

        if ($schema->hasTable('resource_node')) {
            $table = $schema->getTable('resource_node');
            $hasCid = $table->hasColumn('cid');
        }

        if ($hasCid) {
            // Preferred: "created in course" is stored on resource_node.cid
            $sqlNode = <<<SQL
UPDATE resource_node rn
INNER JOIN course c ON c.id = rn.cid
INNER JOIN language l ON l.isocode = c.course_language
SET rn.language_id = l.id
WHERE rn.language_id IS NULL
  AND rn.cid IS NOT NULL
  AND c.course_language IS NOT NULL
  AND c.course_language <> ''
SQL;
            $this->addSql($sqlNode);
        } else {
            // Fallback: infer a course from existing links (best-effort).
            // We pick MIN(c_id) per resource_node_id to have a deterministic choice.
            $sqlNode = <<<SQL
UPDATE resource_node rn
INNER JOIN (
    SELECT rl.resource_node_id, MIN(rl.c_id) AS c_id
    FROM resource_link rl
    WHERE rl.c_id IS NOT NULL
    GROUP BY rl.resource_node_id
) x ON x.resource_node_id = rn.id
INNER JOIN course c ON c.id = x.c_id
INNER JOIN language l ON l.isocode = c.course_language
SET rn.language_id = l.id
WHERE rn.language_id IS NULL
  AND c.course_language IS NOT NULL
  AND c.course_language <> ''
SQL;
            $this->addSql($sqlNode);
        }

        // Copy node language to files when file language is not set yet.
        $sqlFile = <<<SQL
UPDATE resource_file rf
INNER JOIN resource_node rn ON rn.id = rf.resource_node_id
SET rf.language_id = rn.language_id
WHERE rf.language_id IS NULL
  AND rn.language_id IS NOT NULL
SQL;

        $this->addSql($sqlFile);
    }

    public function down(Schema $schema): void
    {
        // remove language inference.
        // Note: if later you start setting languages explicitly, a rollback would also unset them.
        $this->addSql('UPDATE resource_file SET language_id = NULL');
        $this->addSql('UPDATE resource_node SET language_id = NULL');
    }
}
