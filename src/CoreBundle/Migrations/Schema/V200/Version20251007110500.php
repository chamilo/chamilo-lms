<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251007110500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Make c_blog_task.description nullable and drop invalid default on TEXT/LONGTEXT.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_blog_task MODIFY description LONGTEXT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE c_blog_task SET description = "" WHERE description IS NULL');
        $this->addSql('ALTER TABLE c_blog_task MODIFY description LONGTEXT NOT NULL');
    }
}
