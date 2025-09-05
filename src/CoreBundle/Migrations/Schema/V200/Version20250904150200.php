<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250904150200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove c_quiz.active; migrate visibility to resource_link.visibility (Draft/Published).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE resource_link rl
            INNER JOIN c_quiz q ON q.resource_node_id = rl.resource_node_id
            INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
            INNER JOIN resource_type rt ON rt.id = rn.resource_type_id AND rt.title = 'exercises'
            SET rl.visibility = CASE WHEN q.active = 1 THEN 2 ELSE 0 END
        ");

        $this->addSql('ALTER TABLE c_quiz DROP COLUMN active');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz ADD active INT NOT NULL DEFAULT 1');

        $this->addSql("
            UPDATE c_quiz q
            LEFT JOIN (
                SELECT rl.resource_node_id,
                       MAX(CASE WHEN rl.visibility = 2 THEN 1 ELSE 0 END) AS is_published
                FROM resource_link rl
                INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
                INNER JOIN resource_type rt ON rt.id = rn.resource_type_id AND rt.title = 'exercises'
                GROUP BY rl.resource_node_id
            ) x ON x.resource_node_id = q.resource_node_id
            SET q.active = IFNULL(x.is_published, 0)
        ");
    }
}
