<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20211008133540 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_tool_intro';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_tool_intro');

        if ($table->hasColumn('c_tool_id')) {
            if (!$table->hasForeignKey('FK_D705267B1DF6B517')) {
                $this->addSql(
                    'ALTER TABLE c_tool_intro ADD CONSTRAINT FK_D705267B1DF6B517 FOREIGN KEY (c_tool_id) REFERENCES c_tool (iid) ON DELETE CASCADE'
                );
            }
            if (!$table->hasIndex('IDX_D705267B1DF6B517')) {
                $this->addSql('CREATE INDEX IDX_D705267B1DF6B517 ON c_tool_intro (c_tool_id);');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
