<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250402110000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix default values for profile changeable_options and visible_options';
    }

    public function up(Schema $schema): void
    {
        $defaultValues = 'name,officialcode,email,picture,login,password,language,phone,theme';

        $allChangeableOptions = $this
            ->connection
            ->executeQuery("SELECT id, selected_value FROM settings WHERE variable = 'changeable_options'")
            ->fetchAllAssociative()
        ;

        foreach ($allChangeableOptions as $changeableOptions) {
            if ($changeableOptions && empty($changeableOptions['selected_value'])) {
                $this->addSql("UPDATE settings SET selected_value = '$defaultValues' WHERE id = {$changeableOptions['id']}");
            }
        }

        $allVisibleOptions = $this
            ->connection
            ->executeQuery("SELECT id, selected_value FROM settings WHERE variable = 'visible_options'")
            ->fetchAllAssociative()
        ;

        foreach ($allVisibleOptions as $visibleOptions) {
            if ($visibleOptions && empty($visibleOptions['selected_value'])) {
                $this->addSql("UPDATE settings SET selected_value = '$defaultValues' WHERE id = {$visibleOptions['id']}");
            }
        }
    }
}
