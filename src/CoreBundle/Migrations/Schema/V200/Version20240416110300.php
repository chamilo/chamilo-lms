<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\ExtraFieldFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use DateTime;
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

        $protectedFlagsVars = ['my_terms', 'terms_ville', 'terms_villedustage'];

        foreach ($extraFields as $field) {
            $existingField = $this->connection->executeQuery(
                'SELECT * FROM extra_field WHERE variable = :variable AND item_type = :item_type',
                [
                    'variable' => $field['variable'],
                    'item_type' => $field['item_type'],
                ]
            )->fetchAssociative();

            if (!$existingField) {
                // Insert new field if it does not exist
                $this->connection->insert('extra_field', [
                    'item_type' => $field['item_type'],
                    'value_type' => $field['value_type'],
                    'variable' => $field['variable'],
                    'display_text' => $field['display_text'],
                    'visible_to_self' => isset($field['visible_to_self']) ? (int) $field['visible_to_self'] : 0,
                    'visible_to_others' => isset($field['visible_to_others']) ? (int) $field['visible_to_others'] : 0,
                    'changeable' => isset($field['changeable']) ? (int) $field['changeable'] : 0,
                    'filter' => isset($field['filter']) ? (int) $field['filter'] : 0,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ]);

                continue;
            }

            $updateData = [
                'display_text' => $field['display_text'],
            ];

            if (isset($field['filter'])) {
                $updateData['filter'] = (int) $field['filter'];
            }

            if (isset($field['value_type']) && (int) $existingField['value_type'] !== (int) $field['value_type']) {
                $updateData['value_type'] = (int) $field['value_type'];
            }

            if (!\in_array($field['variable'], $protectedFlagsVars, true)) {
                if (\array_key_exists('visible_to_self', $field)) {
                    $updateData['visible_to_self'] = (int) $field['visible_to_self'];
                }
                if (\array_key_exists('visible_to_others', $field)) {
                    $updateData['visible_to_others'] = (int) $field['visible_to_others'];
                }
                if (\array_key_exists('changeable', $field)) {
                    $updateData['changeable'] = (int) $field['changeable'];
                }
            }

            if (!empty($updateData)) {
                $this->connection->update('extra_field', $updateData, [
                    'id' => $existingField['id'],
                ]);
            }
        }
    }

    public function down(Schema $schema): void {}
}
