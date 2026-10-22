<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261022123000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add configurable remember-me duration, preserving the existing 14-day default.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ?',
            ['remember_me_days']
        );

        if ($exists > 0) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ?',
                [
                    'security',
                    'Remember me duration (days)',
                    'Number of days to keep a user signed in when Remember me is selected. Set to 0 to disable. Requires HTTPS. Allowed range: 0 to 90. The normal session timeout is configured separately in PHP/Symfony.',
                    'remember_me_days',
                ]
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url_changeable) VALUES (?, ?, ?, ?, ?, 0)',
            [
                'remember_me_days',
                'security',
                'Remember me duration (days)',
                'Number of days to keep a user signed in when Remember me is selected. Set to 0 to disable. Requires HTTPS. Allowed range: 0 to 90. The normal session timeout is configured separately in PHP/Symfony.',
                '14',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve the administrator's chosen value and existing tokens.
    }
}
