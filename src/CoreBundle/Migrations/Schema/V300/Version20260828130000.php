<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260828130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix the display text of the city and internship city user extra fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE extra_field SET display_text = 'City of internship' WHERE item_type = 1 AND variable = 'terms_villedustage' AND display_text = 'City of internship''s terms'"
        );
        $this->addSql(
            "UPDATE extra_field SET display_text = 'City' WHERE item_type = 1 AND variable = 'terms_ville' AND display_text = 'City''s terms'"
        );
    }

    public function down(Schema $schema): void {}
}
