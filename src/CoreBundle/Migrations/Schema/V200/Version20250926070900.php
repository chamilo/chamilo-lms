<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250926070900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Purge orphan user extra fields (values + tags) where user no longer exists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE v FROM extra_field_values v
            INNER JOIN extra_field f ON f.id = v.field_id
            LEFT JOIN `user` u ON u.id = v.item_id
            WHERE f.item_type = 1 AND u.id IS NULL
        ");

        $this->addSql("
            DELETE r FROM extra_field_rel_tag r
            INNER JOIN extra_field f ON f.id = r.field_id
            LEFT JOIN `user` u ON u.id = r.item_id
            WHERE f.item_type = 1  AND u.id IS NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // No-op (data purge).
    }
}
