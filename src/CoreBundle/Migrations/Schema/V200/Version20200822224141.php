<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Messages.
 */
final class Version20200822224141 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        // Portfolio
        if (!$schema->hasTable('portfolio')) {
            $this->addSql(
                'CREATE TABLE portfolio_category (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'CREATE TABLE portfolio (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, category_id INT DEFAULT NULL, title LONGTEXT NOT NULL, content LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, update_date DATETIME NOT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), INDEX course (c_id), INDEX session (session_id), INDEX category (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE portfolio_category ADD CONSTRAINT FK_7AC64359A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106212469DE2 FOREIGN KEY (category_id) REFERENCES portfolio_category (id);'
            );
        } else {
            $this->addSql('ALTER TABLE portfolio_category CHANGE title title LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
