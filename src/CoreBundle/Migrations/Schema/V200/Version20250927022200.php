<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20250927022200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove deprecated settings';
    }

    public function up(Schema $schema): void
    {
        // Settings to remove everywhere
        $vars = [
            'bug_report_link',
            'enable_webcam_clip',
            'hide_dltt_markup',
            'lp_category_accordion',
            'enable_bootstrap_in_documents_html',
        ];

        // Remove potential templates (no-op if none exist)
        $this->addSql(
            "DELETE FROM settings_value_template WHERE variable IN (?)",
            [$vars],
            [Connection::PARAM_STR_ARRAY]
        );

        // Remove catalog definitions
        $this->addSql(
            "DELETE FROM settings WHERE variable IN (?)",
            [$vars],
            [Connection::PARAM_STR_ARRAY]
        );
    }
}
