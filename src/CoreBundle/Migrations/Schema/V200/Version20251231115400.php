<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251231115400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add extra field for SCIM integration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO extra_field (item_type, value_type, variable, display_text, created_at) VALUES (1, 1, 'scim_external_id', 'SCIM external ID', NOW())"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM extra_field WHERE variable = 'scim_external_id'");
    }
}
