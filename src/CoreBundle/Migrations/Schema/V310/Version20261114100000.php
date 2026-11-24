<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Registers registration.firstname_required (#1783) on existing portals, enabled, so the first name stays required
 * until an administrator decides otherwise.
 */
final class Version20261114100000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'firstname_required';

    public function getDescription(): string
    {
        return 'Add the registration.firstname_required setting (enabled by default).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked) '
            .'SELECT ?, ?, ?, ?, ?, 1, 1, 0 FROM (SELECT 1 AS one) AS dummy '
            .'WHERE NOT EXISTS (SELECT 1 FROM settings WHERE variable = ? AND category = ? AND subkey IS NULL)',
            [
                self::VARIABLE,
                'registration',
                'First name required',
                'When disabled, users can register, edit their profile and be created by administrators without a first name, for people who only have one name. The last name stays required.',
                'true',
                self::VARIABLE,
                'registration',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DELETE FROM settings WHERE variable = ? AND category = ?',
            [self::VARIABLE, 'registration']
        );
    }
}
