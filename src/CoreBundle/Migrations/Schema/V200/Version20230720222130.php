<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20230720222130 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add table social_post_attachments and rename my_files path by resource file path';
    }

    public function up(Schema $schema): void
    {
        if (false === $schema->hasTable('social_post_attachments')) {
            $this->addSql("CREATE TABLE social_post_attachments (id INT AUTO_INCREMENT NOT NULL, social_post_id BIGINT DEFAULT NULL, resource_node_id BIGINT DEFAULT NULL, path VARCHAR(255) NOT NULL, filename LONGTEXT NOT NULL, size INT NOT NULL, sys_insert_user_id INT NOT NULL, sys_insert_datetime DATETIME NOT NULL COMMENT '(DC2Type:datetime)', sys_lastedit_user_id INT DEFAULT NULL, sys_lastedit_datetime DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_DF2A8F34C4F2D6B1 (social_post_id), UNIQUE INDEX UNIQ_DF2A8F341BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
            $this->addSql('ALTER TABLE social_post_attachments ADD CONSTRAINT FK_DF2A8F34C4F2D6B1 FOREIGN KEY (social_post_id) REFERENCES social_post (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE social_post_attachments ADD CONSTRAINT FK_DF2A8F341BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;');
        }
    }
}
