<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230315111019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Change field image of table system template as asset';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('system_template');
        if (false === $table->hasColumn('image_id')) {
            $this->addSql("ALTER TABLE system_template ADD image_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', DROP image;");
            $this->addSql("ALTER TABLE system_template ADD CONSTRAINT FK_FE8AAE013DA5256D FOREIGN KEY (image_id) REFERENCES asset(id) ON DELETE SET NULL;");
            $this->addSql("CREATE INDEX IDX_FE8AAE013DA5256D ON system_template (image_id);");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('system_template');
        if (false !== $table->hasColumn('image_id')) {
            $this->addSql("ALTER TABLE system_template DROP FOREIGN KEY FK_FE8AAE013DA5256D;");
            $this->addSql("ALTER TABLE system_template DROP INDEX IDX_FE8AAE013DA5256D;");
            $this->addSql("ALTER TABLE system_template CHANGE image_id image varchar(250) NOT NULL COMMENT '(DC2Type:uuid)';");
        }
    }
}
