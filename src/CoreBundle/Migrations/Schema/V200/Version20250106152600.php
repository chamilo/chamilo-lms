<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\LanguageFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250106152600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Set parent as null for initial lenguages.';
    }

    public function up(Schema $schema): void
    {
        $languageNameList = array_column(
            LanguageFixtures::getLanguages(),
            'english_name',
        );

        $this->addSql(
            'UPDATE language SET parent_id = NULL WHERE english_name IN ("'.implode('", "', $languageNameList).'")'
        );
    }
}
