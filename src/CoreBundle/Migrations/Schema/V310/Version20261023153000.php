<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261023153000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Register the privacy-first IP visibility setting for reporting.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $variable = 'hide_ip_address';
        $category = 'tracking';
        $title = 'Hide IP address from reports';
        $comment = 'Hide IP addresses in visual reporting and reporting exports. IP addresses remain stored and accessible through authorized personal-data procedures.';

        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = ?',
            [$variable, 1]
        );

        if ($exists > 0) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ? AND access_url = ?',
                [$category, $title, $comment, $variable, 1]
            );

            return;
        }

        // Older synchronization runs could leave a row without its access URL.
        $hasUnassignedRow = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url IS NULL',
            [$variable]
        );

        if ($hasUnassignedRow > 0) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ?, access_url = ? WHERE variable = ? AND access_url IS NULL',
                [$category, $title, $comment, 1, $variable]
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable) VALUES (?, ?, ?, ?, ?, ?, 0)',
            [$variable, $category, $title, $comment, 'true', 1]
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve privacy defaults and administrator-selected values.
    }
}
