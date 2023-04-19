<?php
declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230216122950 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Alter tables required in configuration';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('ticket_ticket')) {
            $this->addSql(
                'ALTER TABLE ticket_ticket ADD exercise_id INT DEFAULT NULL, ADD lp_id INT DEFAULT NULL'
            );
        }

        if ($schema->hasTable('c_quiz_question_rel_category')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD COLUMN mandatory INT DEFAULT 0'
            );
        }

        if (!$schema->hasTable('c_plagiarism_compilatio_docs')) {
            $this->addSql(
                'CREATE TABLE c_plagiarism_compilatio_docs (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, document_id INT NOT NULL, compilatio_id VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if (!$schema->hasTable('notification_event')) {
            $this->addSql(
                'CREATE TABLE notification_event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, link LONGTEXT DEFAULT NULL, persistent INT DEFAULT NULL, day_diff INT DEFAULT NULL, event_type VARCHAR(255) NOT NULL, event_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }
    }

    public function down(Schema $schema): void
    {

        if ($schema->hasTable('notification_event')) {
            $this->addSql(
                'DROP TABLE notification_event'
            );
        }

        if ($schema->hasTable('c_plagiarism_compilatio_docs')) {
            $this->addSql(
                'DROP TABLE c_plagiarism_compilatio_docs'
            );
        }

        $table = $schema->getTable('ticket_ticket');
        if ($table->hasColumn('exercise_id')) {
            $this->addSql('ALTER TABLE ticket_ticket DROP exercise_id');
        }
        if ($table->hasColumn('lp_id')) {
            $this->addSql('ALTER TABLE ticket_ticket DROP lp_id');
        }

        $table = $schema->getTable('c_quiz_question_rel_category');
        if ($table->hasColumn('mandatory')) {
            $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP mandatory');
        }

    }
}
