<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20211026140820 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Pages';
    }

    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('page')) {
            $this->addSql("CREATE TABLE page (id BIGINT AUTO_INCREMENT NOT NULL, access_url_id INT DEFAULT NULL, creator_id INT DEFAULT NULL, category_id BIGINT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, slug VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, position INT NOT NULL, locale VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_140AB62073444FD5 (access_url_id), INDEX IDX_140AB62061220EA6 (creator_id), INDEX IDX_140AB62012469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
            $this->addSql("CREATE TABLE page_category (id BIGINT AUTO_INCREMENT NOT NULL, creator_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_86D31EE161220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");

            $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB62073444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB62061220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB62012469DE2 FOREIGN KEY (category_id) REFERENCES page_category (id) ON DELETE SET NULL;');
            $this->addSql('ALTER TABLE page_category ADD CONSTRAINT FK_86D31EE161220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE;');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
