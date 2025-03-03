<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250118000100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add custom_image_id field to c_link table and set up the foreign key to asset table.';
    }

    public function up(Schema $schema): void
    {
        // Add the new column and foreign key
        $this->addSql('
            ALTER TABLE c_link
            ADD custom_image_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\',
            ADD CONSTRAINT FK_9209C2A0D877C209 FOREIGN KEY (custom_image_id) REFERENCES asset (id) ON DELETE SET NULL
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove the custom_image_id column and foreign key
        $this->addSql('
            ALTER TABLE c_link
            DROP FOREIGN KEY FK_9209C2A0D877C209,
            DROP custom_image_id
        ');
    }
}
