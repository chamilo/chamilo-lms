<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250706203800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create tables page_layout_template and page_layout for page layout templates feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE page_layout_template (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                layout LONGTEXT NOT NULL COMMENT 'JSON structure describing the layout template',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ");

        $this->addSql("
            CREATE TABLE page_layout (
                id INT AUTO_INCREMENT NOT NULL,
                page_layout_template_id INT DEFAULT NULL,
                created_by INT DEFAULT NULL,
                updated_by INT DEFAULT NULL,
                url TEXT NOT NULL COMMENT 'URL or page identifier where the layout applies',
                roles TEXT DEFAULT NULL COMMENT 'Comma-separated list of role identifiers',
                layout LONGTEXT NOT NULL COMMENT 'JSON describing the final layout with blocks and structure',
                created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                INDEX IDX_PAGE_LAYOUT_TEMPLATE_ID (page_layout_template_id),
                INDEX IDX_PAGE_LAYOUT_CREATED_BY (created_by),
                INDEX IDX_PAGE_LAYOUT_UPDATED_BY (updated_by),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ");

        $this->addSql("
            ALTER TABLE page_layout
                ADD CONSTRAINT FK_PAGE_LAYOUT_TEMPLATE
                    FOREIGN KEY (page_layout_template_id) REFERENCES page_layout_template (id) ON DELETE SET NULL;
        ");

        $this->addSql("
            ALTER TABLE page_layout
                ADD CONSTRAINT FK_PAGE_LAYOUT_CREATED_BY
                    FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE SET NULL;
        ");

        $this->addSql("
            ALTER TABLE page_layout
                ADD CONSTRAINT FK_PAGE_LAYOUT_UPDATED_BY
                    FOREIGN KEY (updated_by) REFERENCES user (id) ON DELETE SET NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE page_layout DROP FOREIGN KEY FK_PAGE_LAYOUT_TEMPLATE;");
        $this->addSql("ALTER TABLE page_layout DROP FOREIGN KEY FK_PAGE_LAYOUT_CREATED_BY;");
        $this->addSql("ALTER TABLE page_layout DROP FOREIGN KEY FK_PAGE_LAYOUT_UPDATED_BY;");
        $this->addSql("DROP TABLE IF EXISTS page_layout;");
        $this->addSql("DROP TABLE IF EXISTS page_layout_template;");
    }
}
