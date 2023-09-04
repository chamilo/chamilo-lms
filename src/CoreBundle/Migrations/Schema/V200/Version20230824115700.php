<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230824115700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Changes in Course dependent tables';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $accessUrlRelCourse = $schema->getTable('access_url_rel_course');

        if ($accessUrlRelCourse->hasForeignKey('FK_8E97FC0891D79BD3')) {
            $this->addSql('ALTER TABLE access_url_rel_course DROP FOREIGN KEY FK_8E97FC0891D79BD3');
        }

        if ($accessUrlRelCourse->hasForeignKey('FK_8E97FC0873444FD5')) {
            $this->addSql('ALTER TABLE access_url_rel_course DROP FOREIGN KEY FK_8E97FC0873444FD5');
        }

        $this->addSql('ALTER TABLE access_url_rel_course CHANGE c_id c_id INT NOT NULL, CHANGE access_url_id access_url_id INT NOT NULL');
        $this->addSql('ALTER TABLE access_url_rel_course ADD CONSTRAINT FK_8E97FC0891D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE access_url_rel_course ADD CONSTRAINT FK_8E97FC0873444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');

        $courseRelUser = $schema->getTable('course_rel_user');

        if ($courseRelUser->hasForeignKey('FK_92CFD9FE91D79BD3')) {
            $this->addSql('ALTER TABLE course_rel_user DROP FOREIGN KEY FK_92CFD9FE91D79BD3');
        }

        $this->addSql('ALTER TABLE course_rel_user ADD CONSTRAINT FK_92CFD9FE91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');

        $cTool = $schema->getTable('c_tool');

        if ($cTool->hasForeignKey('FK_8456658091D79BD3')) {
            $this->addSql('ALTER TABLE c_tool DROP FOREIGN KEY FK_8456658091D79BD3');
        }

        $this->addSql('ALTER TABLE c_tool ADD CONSTRAINT FK_8456658091D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
    }
}
