<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261019120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace the document visibility settings defined in course by the default visibility on creation settings.';
    }

    public function up(Schema $schema): void
    {
        // Only courses of a platform that let them choose made a choice that was applied: the
        // value every course received when it was created is not one.
        $courseChoiceWasApplied = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM settings WHERE variable = 'documents_default_visibility_defined_in_course' AND selected_value = 'true'"
        );

        // The settings upsert may already have added the new setting with its default value:
        // the administrator's value of the old one replaces it.
        $this->addSql(
            "DELETE new_setting FROM settings new_setting
            INNER JOIN settings old_setting
                ON old_setting.access_url = new_setting.access_url
                AND old_setting.subkey <=> new_setting.subkey
                AND old_setting.variable = 'documents_default_visibility_defined_in_course'
            WHERE new_setting.variable = 'document_default_visibility_can_be_modified_in_course'"
        );
        $this->addSql(
            "UPDATE settings
            SET variable = 'document_default_visibility_can_be_modified_in_course',
                category = 'document',
                title = 'Default visibility of new documents can be changed by course',
                comment = 'When enabled, teachers can choose in the course settings the visibility given to new documents of their course, instead of the platform default.'
            WHERE variable = 'documents_default_visibility_defined_in_course'"
        );

        if ($courseChoiceWasApplied) {
            $this->addSql(
                "UPDATE c_course_setting
                SET variable = 'document_default_visibility_on_creation', title = 'document_default_visibility_on_creation'
                WHERE variable = 'documents_default_visibility'"
            );
        } else {
            $this->addSql("DELETE FROM c_course_setting WHERE variable = 'documents_default_visibility'");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE settings
            SET variable = 'documents_default_visibility_defined_in_course',
                title = 'Document visibility defined in course',
                comment = 'The default document visibility for all courses'
            WHERE variable = 'document_default_visibility_can_be_modified_in_course'"
        );
        $this->addSql(
            "UPDATE c_course_setting
            SET variable = 'documents_default_visibility', title = 'documents_default_visibility'
            WHERE variable = 'document_default_visibility_on_creation'"
        );
    }
}
