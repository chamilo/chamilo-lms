<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260805123000 extends AbstractMigrationChamilo
{
    private const VARIABLE = 'add_fullname_in_file_download';
    private const CATEGORY = 'work';
    private const TITLE = 'Add student fullname in file download name';
    private const COMMENT = 'Add the student full name to assignment file names when downloading individual submissions or assignment packages.';
    private const DEFAULT_VALUE = 'false';

    public function getDescription(): string
    {
        return 'Add setting to include student full name in assignment download file names.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND subkey IS NULL AND access_url = 1',
            [self::VARIABLE]
        );

        if ($exists > 0) {
            $this->addSql(
                'UPDATE settings
                 SET category = ?, title = ?, comment = ?, access_url_changeable = 1, access_url_locked = 0
                 WHERE variable = ? AND subkey IS NULL AND access_url = 1',
                [
                    self::CATEGORY,
                    self::TITLE,
                    self::COMMENT,
                    self::VARIABLE,
                ]
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (
                access_url,
                variable,
                subkey,
                category,
                selected_value,
                title,
                comment,
                access_url_changeable,
                access_url_locked
             ) VALUES (1, ?, NULL, ?, ?, ?, ?, 1, 0)',
            [
                self::VARIABLE,
                self::CATEGORY,
                self::DEFAULT_VALUE,
                self::TITLE,
                self::COMMENT,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // Keep administrator choices and existing installation data.
    }
}
