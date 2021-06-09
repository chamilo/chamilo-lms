<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170625144000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_student_publication changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_student_publication');

        $this->addSql('UPDATE c_student_publication SET user_id = NULL WHERE user_id = 0');
        $this->addSql('ALTER TABLE c_student_publication CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('UPDATE c_student_publication SET parent_id = NULL WHERE parent_id = 0 OR parent_id = "" ');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication');
        }

        if ($table->hasForeignKey('FK_5246F746613FECDF')) {
            $this->addSql('ALTER TABLE c_student_publication DROP FOREIGN KEY FK_5246F746613FECDF');
        }

        if ($table->hasIndex('idx_csp_u')) {
            $this->addSql('DROP INDEX idx_csp_u ON c_student_publication');
        }

        if (false === $table->hasForeignKey('FK_5246F746A76ED395')) {
            $this->addSql(
                'ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
        }

        if (false === $table->hasIndex('IDX_5246F746A76ED395')) {
            $this->addSql('CREATE INDEX IDX_5246F746A76ED395 ON c_student_publication (user_id);');
        }

        if (false === $table->hasForeignKey('FK_5246F746727ACA70')) {
            $this->addSql(
                'ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F746727ACA70 FOREIGN KEY (parent_id) REFERENCES c_student_publication (iid);'
            );
        }

        if (false === $table->hasIndex('IDX_5246F746727ACA70')) {
            $this->addSql('CREATE INDEX IDX_5246F746727ACA70 ON c_student_publication (parent_id)');
        }

        if (false === $table->hasColumn('filesize')) {
            $this->addSql('ALTER TABLE c_student_publication ADD filesize INT DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE c_student_publication CHANGE url url VARCHAR(500) DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE c_student_publication CHANGE url_correction url_correction VARCHAR(500) DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE c_student_publication CHANGE active active INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F7461BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_5246F7461BAD783F ON c_student_publication (resource_node_id)');
        }

        $table = $schema->getTable('c_student_publication_assignment');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication_assignment;');
        }

        /*
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication_assignment ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication_assignment ADD CONSTRAINT FK_25687EB81BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_25687EB81BAD783F ON c_student_publication_assignment (resource_node_id)'
            );
        }
        */

        $this->addSql('UPDATE c_student_publication_assignment SET publication_id = NULL WHERE publication_id = 0');
        $this->addSql(
            'ALTER TABLE c_student_publication_assignment CHANGE publication_id publication_id INT DEFAULT NULL'
        );

        if (false === $table->hasForeignKey('FK_25687EB838B217A7')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_assignment ADD CONSTRAINT FK_25687EB838B217A7 FOREIGN KEY (publication_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE;'
            );
        }

        if (false === $table->hasIndex('UNIQ_25687EB838B217A7')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_assignment ADD UNIQUE INDEX UNIQ_25687EB838B217A7 (publication_id)'
            );
        }

        if (false === $schema->hasTable('c_student_publication_correction')) {
            $this->addSql(
                'CREATE TABLE c_student_publication_correction (id INT AUTO_INCREMENT NOT NULL, resource_node_id BIGINT DEFAULT NULL, title VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B7309BBA1BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_student_publication_correction ADD CONSTRAINT FK_B7309BBA1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        $table = $schema->getTable('c_student_publication_comment');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_student_publication_comment ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F61BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX UNIQ_35C509F61BAD783F ON c_student_publication_comment (resource_node_id)'
            );
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication_comment');
        }

        $this->addSql('UPDATE c_student_publication_comment SET work_id = NULL WHERE work_id = 0');
        $this->addSql('UPDATE c_student_publication_comment SET user_id = NULL WHERE user_id = 0');

        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE work_id work_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE user_id user_id INT DEFAULT NULL');

        $this->addSql('DELETE FROM c_student_publication_comment WHERE work_id NOT IN (SELECT iid FROM c_student_publication)');

        if ($table->hasIndex('work')) {
            $this->addSql('DROP INDEX work ON c_student_publication_comment');
        }

        if ($table->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_student_publication_comment');
        }

        if (!$table->hasForeignKey('FK_35C509F6BB3453DB')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F6BB3453DB FOREIGN KEY (work_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE;'
            );
        }

        if (!$table->hasForeignKey('FK_35C509F6A76ED395')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_comment ADD CONSTRAINT FK_35C509F6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }

        if (!$table->hasIndex('IDX_35C509F6BB3453DB')) {
            $this->addSql('CREATE INDEX IDX_35C509F6BB3453DB ON c_student_publication_comment (work_id);');
        }

        if (!$table->hasIndex('IDX_35C509F6A76ED395')) {
            $this->addSql('CREATE INDEX IDX_35C509F6A76ED395 ON c_student_publication_comment (user_id);');
        }

        $table = $schema->getTable('c_student_publication_rel_document');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication_rel_document;');
        }
        if ($table->hasIndex('work')) {
            $this->addSql('DROP INDEX work ON c_student_publication_rel_document;');
        }
        if ($table->hasIndex('document')) {
            $this->addSql('DROP INDEX document ON c_student_publication_rel_document;');
        }

        $this->addSql('ALTER TABLE c_student_publication_rel_document CHANGE work_id work_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_rel_document CHANGE document_id document_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_BD6672A5BB3453DB')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_rel_document ADD CONSTRAINT FK_BD6672A5BB3453DB FOREIGN KEY (work_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_BD6672A5C33F7837')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_rel_document ADD CONSTRAINT FK_BD6672A5C33F7837 FOREIGN KEY (document_id) REFERENCES c_document (iid) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_BD6672A5BB3453DB')) {
            $this->addSql('CREATE INDEX IDX_BD6672A5BB3453DB ON c_student_publication_rel_document (work_id);');
        }

        if (!$table->hasIndex('IDX_BD6672A5C33F7837')) {
            $this->addSql('CREATE INDEX IDX_BD6672A5C33F7837 ON c_student_publication_rel_document (document_id);');
        }

        $table = $schema->getTable('c_student_publication_rel_user');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_student_publication_rel_user');
        }
        if ($table->hasIndex('work')) {
            $this->addSql('DROP INDEX work ON c_student_publication_rel_user');
        }
        if ($table->hasIndex('user')) {
            $this->addSql('DROP INDEX user ON c_student_publication_rel_user');
        }

        $this->addSql('ALTER TABLE c_student_publication_rel_user CHANGE work_id work_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_rel_user CHANGE user_id user_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_2B007FA9BB3453DB')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_rel_user ADD CONSTRAINT FK_2B007FA9BB3453DB FOREIGN KEY (work_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE;'
            );
        }
        if (!$table->hasForeignKey('FK_2B007FA9A76ED395')) {
            $this->addSql(
                'ALTER TABLE c_student_publication_rel_user ADD CONSTRAINT FK_2B007FA9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }
        if (!$table->hasIndex('IDX_2B007FA9BB3453DB')) {
            $this->addSql('CREATE INDEX IDX_2B007FA9BB3453DB ON c_student_publication_rel_user (work_id)');
        }
        if (!$table->hasIndex('IDX_2B007FA9A76ED395')) {
            $this->addSql('CREATE INDEX IDX_2B007FA9A76ED395 ON c_student_publication_rel_user (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
