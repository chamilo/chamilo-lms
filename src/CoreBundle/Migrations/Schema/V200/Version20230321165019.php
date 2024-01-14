<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230321165019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Delete track_e_attempt_recording';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('track_e_attempt_recording')) {
            $schema->dropTable('track_e_attempt_recording');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
