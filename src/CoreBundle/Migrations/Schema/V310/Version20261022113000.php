<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261022113000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Register administrator-controlled confidential welfare reporting settings';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        foreach ([
            ['confidential_reporting_enabled', 'Enable confidential welfare reporting', 'Enables confidential incident reporting for signed-in users.', 'false'],
            ['confidential_reporting_min_reporters', 'Minimum distinct reporters for an investigation', 'Between 2 and 10 distinct reporters are required to unlock an investigation.', '2'],
            ['confidential_reporting_attachments_enabled', 'Allow confidential evidence attachments', 'Permit encrypted image, audio and video evidence. Existing evidence remains readable if disabled.', 'true'],
            ['confidential_reporting_notifications_enabled', 'Show confidential investigation notifications', 'Display internal alerts for eligible investigations; no email or external notifications.', 'false'],
        ] as [$name, $title, $comment, $default]) {
            $this->addSql(<<<'SQL'
INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable)
SELECT ?, 'security', ?, ?, ?, 1, 0
WHERE NOT EXISTS (
    SELECT 1 FROM settings
    WHERE variable = ? AND category = 'security' AND access_url = 1 AND subkey IS NULL
)
SQL, [$name, $title, $comment, $default, $name]);
        }
    }

    public function down(Schema $schema): void
    {
        // Preserve each institution's chosen configuration and all encrypted reports.
    }
}
