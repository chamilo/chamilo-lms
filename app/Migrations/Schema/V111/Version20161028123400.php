<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20161028123400
 * Add primary key as auto increment in c_student_publication_comment
 * @package Application\Migrations\Schema\V111
 */
class Version20161028123400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20161028123400');
        $iidColumn = $schema
            ->getTable('c_student_publication_comment')
            ->getColumn('iid');

        if (!$iidColumn->getAutoincrement()) {
            $iidColumn->setAutoincrement(true);
        }

        // Deleting users that don't exist anymore
        $sql = 'DELETE FROM access_url_rel_user WHERE user_id NOT IN (SELECT user_id from user)';
        $this->addSql($sql);

        $table = $schema->getTable('personal_agenda');
        if ($table->hasIndex('id')) {
            $this->addSql('ALTER TABLE personal_agenda DROP INDEX id');
            $this->addSql('ALTER TABLE personal_agenda DROP INDEX idx_personal_agenda_user');
            $this->addSql('ALTER TABLE personal_agenda DROP INDEX idx_personal_agenda_parent');
            $this->addSql('ALTER TABLE personal_agenda modify id INT NOT NULL');
            if ($table->hasPrimaryKey()) {
                $this->addSql('ALTER TABLE personal_agenda drop primary key ');
            }

            $this->addSql('ALTER TABLE personal_agenda CHANGE id id INT AUTO_INCREMENT NOT NULL PRIMARY KEY');
            $this->addSql('CREATE INDEX idx_personal_agenda_user ON personal_agenda (user)');
            $this->addSql('CREATE INDEX idx_personal_agenda_parent ON personal_agenda (parent_event_id)');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable('c_student_publication_comment')
            ->getColumn('iid')
            ->setAutoincrement(false);
    }
}
