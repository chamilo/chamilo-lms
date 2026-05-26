<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260223133800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update user_auth_source foreign key to access_url to cascade on delete';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('user_auth_source')) {
            $table = $schema->getTable('user_auth_source');

            if ($table->hasForeignKey('FK_D632110481CFDAE7')) {
                $this->addSql('ALTER TABLE user_auth_source DROP FOREIGN KEY FK_D632110481CFDAE7');
            }

            $this->addSql('ALTER TABLE user_auth_source ADD CONSTRAINT FK_D632110481CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_auth_source DROP FOREIGN KEY FK_D632110481CFDAE7');
        $this->addSql('ALTER TABLE user_auth_source ADD CONSTRAINT FK_D632110481CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id)');
    }
}
