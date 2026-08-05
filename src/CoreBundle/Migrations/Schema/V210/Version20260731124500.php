<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260731124500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add configurable session expiration warning settings with safe defaults.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $settings = [
            [
                'variable' => 'session_expiration_warning_enabled',
                'category' => 'security',
                'title' => 'Enable session expiration warning',
                'comment' => 'Show a warning to authenticated users before their server-side session expires.',
                'selected_value' => 'false',
            ],
            [
                'variable' => 'session_expiration_warning_seconds',
                'category' => 'security',
                'title' => 'Session expiration warning time',
                'comment' => 'Number of seconds before session expiration when the warning is displayed. The default is 180 seconds.',
                'selected_value' => '180',
            ],
        ];

        foreach ($settings as $setting) {
            $exists = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings WHERE variable = ?',
                [$setting['variable']]
            );

            if ($exists > 0) {
                $this->addSql(
                    'UPDATE settings
                     SET category = ?, title = ?, comment = ?
                     WHERE variable = ?',
                    [
                        $setting['category'],
                        $setting['title'],
                        $setting['comment'],
                        $setting['variable'],
                    ]
                );

                continue;
            }

            $this->addSql(
                'INSERT INTO settings (
                    variable,
                    category,
                    title,
                    comment,
                    selected_value,
                    access_url_changeable
                 ) VALUES (?, ?, ?, ?, ?, 0)',
                [
                    $setting['variable'],
                    $setting['category'],
                    $setting['title'],
                    $setting['comment'],
                    $setting['selected_value'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Keep administrator choices and existing installation data.
    }
}
