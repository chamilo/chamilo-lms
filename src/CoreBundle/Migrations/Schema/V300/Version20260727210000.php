<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260727210000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds the language_by_resource platform setting, disabled by default.';
    }

    public function up(Schema $schema): void
    {
        $settingExists = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM settings WHERE variable = :variable LIMIT 1',
            ['variable' => 'language_by_resource'],
        );

        if ($settingExists) {
            return;
        }

        $this->addSettingCurrent(
            'language_by_resource',
            '',
            'checkbox',
            'language',
            'false',
            'Language by resource',
            'Allow assigning a specific language to individual resources.',
            '',
            '',
            1,
            true,
            false,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable = 'language_by_resource'");
    }
}
