<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260116085000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix wrong resource type for portfolio comments';
    }

    public function up(Schema $schema): void
    {
        $itemsType = $this->connection->fetchAssociative(
            'SELECT id FROM resource_type WHERE title = ?',
            ['portfolio_items']
        );
        $commentsType = $this->connection->fetchAssociative(
            'SELECT id FROM resource_type WHERE title = ?',
            ['portfolio_comments']
        );

        if (empty($itemsType) || empty($commentsType)) {
            return;
        }

        $this->addSql(\sprintf(
            'UPDATE resource_node rn INNER JOIN portfolio_comment pc ON rn.id = pc.resource_node_id SET rn.resource_type_id = %d WHERE rn.resource_type_id = %d',
            $commentsType['id'],
            $itemsType['id']
        ));
    }

    public function down(Schema $schema): void {}
}
