<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\DataFixtures\ExtraFieldFixtures;
use Doctrine\DBAL\Schema\Schema;

class Version20240416110300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds missing extra fields to the database based on the predefined list';
    }

    public function up(Schema $schema): void
    {
        $extraFields = ExtraFieldFixtures::getExtraFields();

        foreach ($extraFields as $field) {
            $count = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM extra_field WHERE variable = :variable AND item_type = :item_type",
                [
                    'variable' => $field['variable'],
                    'item_type' => $field['item_type'],
                ]
            )->fetchOne();

            if ($count == 0) {
                $this->connection->insert('extra_field', [
                    'item_type' => $field['item_type'],
                    'value_type' => $field['value_type'],
                    'variable' => $field['variable'],
                    'display_text' => $field['display_text'],
                    'visible_to_self' => $field['visible_to_self'] ? 1 : 0,
                    'changeable' => $field['changeable'] ? 1 : 0,
                    'filter' => 1,
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down(Schema $schema): void {}
}
