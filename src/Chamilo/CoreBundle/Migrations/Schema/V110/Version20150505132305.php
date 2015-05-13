<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Database;
use Doctrine\DBAL\Schema\Schema;

/**
 * Moves course, session, user extra field into one table.
 */
class Version20150505132305 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Extra fields
        $extraFieldTables = [
            ExtraField::USER_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_USER_FIELD),
            ExtraField::COURSE_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_COURSE_FIELD),
            //ExtraField::LP_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_LP_FIELD),
            ExtraField::SESSION_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_SESSION_FIELD),
            //ExtraField::CALENDAR_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_CALENDAR_EVENT_FIELD),
            //ExtraField::QUESTION_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_CALENDAR_EVENT_FIELD),
            //ExtraField::USER_FIELD_TYPE => //Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD),
        ];

        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        foreach ($extraFieldTables as $type => $table) {
            //continue;
            $sql = "SELECT * FROM $table ";
            $result = $connection->query($sql);
            $fields = $result->fetchAll();

            foreach ($fields as $field) {
                $originalId = $field['id'];
                $extraField = new ExtraField();
                $extraField
                    ->setExtraFieldType($type)
                    ->setVariable($field['field_variable'])
                    ->setFieldType($field['field_type'])
                    ->setDisplayText($field['field_display_text'])
                    ->setDefaultValue($field['field_default_value'])
                    ->setFieldOrder($field['field_order'])
                    ->setVisible($field['field_visible'])
                    ->setChangeable($field['field_changeable'])
                    ->setFilter($field['field_filter']);

                $em->persist($extraField);
                $em->flush();

                $values = array();
                switch ($type) {
                    case ExtraField::USER_FIELD_TYPE:
                        $optionTable = Database::get_main_table(
                            TABLE_MAIN_USER_FIELD_OPTIONS
                        );
                        $valueTable = Database::get_main_table(
                            TABLE_MAIN_USER_FIELD_VALUES
                        );
                        $handlerId = 'user_id';
                        break;
                    case ExtraField::COURSE_FIELD_TYPE:
                        $optionTable = Database::get_main_table(
                            TABLE_MAIN_COURSE_FIELD_OPTIONS
                        );
                        $valueTable = Database::get_main_table(
                            TABLE_MAIN_COURSE_FIELD_VALUES
                        );
                        $handlerId = 'c_id';
                        break;
                    case ExtraField::SESSION_FIELD_TYPE:
                        $optionTable = Database::get_main_table(
                            TABLE_MAIN_SESSION_FIELD_OPTIONS
                        );
                        $valueTable = Database::get_main_table(
                            TABLE_MAIN_SESSION_FIELD_VALUES
                        );
                        $handlerId = 'session_id';
                        break;
                }

                if (!empty($optionTable)) {

                    $sql = "SELECT * FROM $optionTable WHERE field_id = $originalId ";
                    $result = $connection->query($sql);
                    $options = $result->fetchAll();

                    foreach ($options as $option) {
                        $extraFieldOption = new ExtraFieldOptions();
                        $extraFieldOption
                            ->setDisplayText($option['option_display_text'])
                            ->setField($extraField)
                            ->setOptionOrder($option['option_order'])
                            ->setValue($option['option_value']);
                        $em->persist($extraFieldOption);
                        $em->flush();
                    }

                    $sql = "SELECT * FROM $valueTable WHERE field_id = $originalId ";
                    $result = $connection->query($sql);
                    $values = $result->fetchAll();
                }

                if (!empty($values)) {
                    foreach ($values as $value) {
                        $extraFieldValue = new ExtraFieldValues();
                        $extraFieldValue
                            ->setValue($value['field_value'])
                            ->setField($extraField)
                            ->setItemId($value[$handlerId]);
                        $em->persist($extraFieldValue);
                        $em->flush();
                    }
                }
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE extra_field_options DROP FOREIGN KEY FK_A572E3AE443707B0');
        $this->addSql('DROP TABLE extra_field_option_rel_field_option');
        $this->addSql('DROP TABLE extra_field_options');
        $this->addSql('DROP TABLE extra_field');
        $this->addSql('DROP TABLE extra_field_values');
    }
}
