<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260731200000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'quiz_result_pdf_export_include_official_code_in_file_name';

    public function getDescription(): string
    {
        return 'Add the exercise PDF official-code filename setting to existing installations.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            'INSERT INTO settings ('
            .'access_url, variable, category, selected_value, title, comment, '
            .'access_url_changeable, access_url_locked'
            .') SELECT 1, ?, ?, ?, ?, ?, 1, 0 '
            .'WHERE NOT EXISTS ('
            .'SELECT 1 FROM settings WHERE variable = ? AND access_url = 1'
            .')',
            [
                self::VARIABLE,
                'exercise',
                'false',
                'Include official code in exported quiz result PDF file name',
                "Whether to include the student's official code in the file name when exporting a quiz result to PDF",
                self::VARIABLE,
            ],
        );

        $this->addSql(
            'UPDATE settings SET category = ?, title = ?, comment = ?, '
            .'access_url_changeable = 1, access_url_locked = 0 '
            .'WHERE variable = ? AND access_url = 1',
            [
                'exercise',
                'Include official code in exported quiz result PDF file name',
                "Whether to include the student's official code in the file name when exporting a quiz result to PDF",
                self::VARIABLE,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            'DELETE FROM settings WHERE variable = ?',
            [self::VARIABLE],
        );
    }
}
