<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260114180500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix duplicated setting with wrong selected value';
    }

    public function up(Schema $schema): void
    {
        $replaceValues = [
            'catalog_show_courses_sessions' => 'show_courses_sessions',
        ];

        foreach ($replaceValues as $oldVariable => $newVariable) {
            $settingInfo = $this->connection->fetchAssociative(
                'SELECT selected_value FROM settings WHERE variable = ?',
                [$oldVariable]
            );

            if (!$settingInfo) {
                continue;
            }

            $selectedValue = $settingInfo['selected_value'];

            $this->addSql(
                "UPDATE settings SET selected_value = \"$selectedValue\" WHERE variable = \"$newVariable\""
            );
            $this->addSql('DELETE FROM settings WHERE variable = "$oldVariable"');
        }
    }

    public function down(Schema $schema): void
    {
        // Recover data from deleted settings ?
    }
}
