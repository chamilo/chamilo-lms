<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230215062918 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Learnpath subscription changes';
    }

    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('c_lp_rel_user')) {
            $this->addSql(
                "CREATE TABLE c_lp_rel_user (iid INT AUTO_INCREMENT NOT NULL, lp_id INT DEFAULT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, user_id INT NOT NULL, creator_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_AD97516E68DFD1EF (lp_id), INDEX IDX_AD97516E91D79BD3 (c_id), INDEX IDX_AD97516E613FECDF (session_id), INDEX IDX_AD97516EA76ED395 (user_id), INDEX IDX_AD97516E61220EA6 (creator_id), PRIMARY KEY(iid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
            $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516E68DFD1EF FOREIGN KEY (lp_id) REFERENCES c_lp (iid);');
            $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
            $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516E613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
            $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
            $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516E61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
