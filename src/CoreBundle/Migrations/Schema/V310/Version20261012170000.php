<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Convert VARCHAR columns longer than 255 characters to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_lp_item')) {
            $table = $schema->getTable('c_lp_item');

            if ($table->hasColumn('title')) {
                $this->addSql('ALTER TABLE c_lp_item MODIFY title LONGTEXT NOT NULL');
            }

            if ($table->hasColumn('description')) {
                $this->addSql('ALTER TABLE c_lp_item MODIFY description LONGTEXT DEFAULT NULL');
            }
        }

        if ($schema->hasTable('plugin_oel_tools_logs')) {
            $table = $schema->getTable('plugin_oel_tools_logs');

            if ($table->hasColumn('logs')) {
                $this->addSql('ALTER TABLE plugin_oel_tools_logs MODIFY logs LONGTEXT DEFAULT NULL');
            }
        }

        if ($schema->hasTable('plugin_oel_tools_teachdoc')) {
            $table = $schema->getTable('plugin_oel_tools_teachdoc');

            if ($table->hasColumn('options')) {
                $this->addSql('ALTER TABLE plugin_oel_tools_teachdoc MODIFY options LONGTEXT NOT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_lp_item')) {
            $table = $schema->getTable('c_lp_item');

            if ($table->hasColumn('title')) {
                $this->assertMaxLength('c_lp_item', 'title', 511);
                $this->addSql('ALTER TABLE c_lp_item MODIFY title VARCHAR(511) NOT NULL');
            }

            if ($table->hasColumn('description')) {
                $this->assertMaxLength('c_lp_item', 'description', 511);
                $this->addSql('ALTER TABLE c_lp_item MODIFY description VARCHAR(511) DEFAULT NULL');
            }
        }

        if ($schema->hasTable('plugin_oel_tools_logs')) {
            $table = $schema->getTable('plugin_oel_tools_logs');

            if ($table->hasColumn('logs')) {
                $this->assertMaxLength('plugin_oel_tools_logs', 'logs', 1080);
                $this->addSql('ALTER TABLE plugin_oel_tools_logs MODIFY logs VARCHAR(1080) DEFAULT NULL');
            }
        }

        if ($schema->hasTable('plugin_oel_tools_teachdoc')) {
            $table = $schema->getTable('plugin_oel_tools_teachdoc');

            if ($table->hasColumn('options')) {
                $this->assertMaxLength('plugin_oel_tools_teachdoc', 'options', 1080);
                $this->addSql('ALTER TABLE plugin_oel_tools_teachdoc MODIFY options VARCHAR(1080) NOT NULL');
            }
        }
    }

    private function assertMaxLength(string $table, string $column, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(%s)), 0) FROM %s', $column, $table)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.%s to VARCHAR(%d): existing data reaches %d characters.', $table, $column, $maxLength, $actualMaxLength));
        }
    }
}
