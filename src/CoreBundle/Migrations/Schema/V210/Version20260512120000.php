<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260512120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add certificate search platform setting.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->upsertCertificateSearchSetting('settings');
        }

        if ($schema->hasTable('settings_current')) {
            $this->upsertCertificateSearchSetting('settings_current');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->deleteCertificateSearchSetting('settings');
        }

        if ($schema->hasTable('settings_current')) {
            $this->deleteCertificateSearchSetting('settings_current');
        }
    }

    private function upsertCertificateSearchSetting(string $table): void
    {
        $this->addSql(
            'INSERT INTO '.$table.' (variable, category, title, comment, selected_value, access_url_changeable) '.
            'SELECT ?, ?, ?, ?, ?, 0 WHERE NOT EXISTS (SELECT 1 FROM '.$table.' WHERE variable = ?)',
            [
                'allow_certificates_search',
                'certificate',
                'Allow certificates search',
                'Allow users and visitors to search generated certificates from the top bar menu.',
                'false',
                'allow_certificates_search',
            ]
        );
    }

    private function deleteCertificateSearchSetting(string $table): void
    {
        $this->addSql(
            'DELETE FROM '.$table.' WHERE variable = ? AND category = ?',
            [
                'allow_certificates_search',
                'certificate',
            ]
        );
    }
}
