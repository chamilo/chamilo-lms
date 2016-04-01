<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160331103600
 */
class Version20160331103600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSettingCurrent(
            'teacher_can_select_course_template',
            null,
            'radio',
            'Course',
            'true',
            'TeacherCanSelectCourseTemplateTitle',
            'TeacherCanSelectCourseTemplateComment',
            null,
            '',
            1,
            true,
            false,
            [
                ['value' => 'true', 'text' => 'Yes'],
                ['value' => 'false', 'text' => 'No'],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        
    }
}
