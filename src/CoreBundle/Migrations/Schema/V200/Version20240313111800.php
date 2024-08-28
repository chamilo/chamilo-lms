<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240313111800 extends AbstractMigrationChamilo
{
    private array $changes = [
        'c_lp' => 'display_order',
        'c_lp_category' => 'position',
        'c_forum_category' => 'cat_order',
        'c_forum_forum' => 'forum_order',
        'c_thematic' => 'display_order',
        'c_announcement' => 'display_order',
        'c_glossary' => 'display_order',
    ];

    public function getDescription(): string
    {
        $tables = array_keys($this->changes);
        $columns = array_values($this->changes);

        return \sprintf(
            'Removing %s columns from %s tables',
            implode(', ', $columns),
            implode(', ', $tables)
        );
    }

    public function up(Schema $schema): void
    {
        foreach ($this->changes as $table => $column) {
            $this->addSql("ALTER TABLE $table DROP $column");
        }
    }
}
