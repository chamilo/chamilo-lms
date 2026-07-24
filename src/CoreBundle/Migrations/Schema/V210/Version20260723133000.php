<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260723133000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Secure personal MCP API keys by portal and add revocation and usage metadata.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user_api_key')) {
            return;
        }

        $table = $schema->getTable('user_api_key');

        $this->addSql('ALTER TABLE user_api_key CHANGE api_key api_key VARCHAR(64) NOT NULL');

        if (!$table->hasColumn('access_url_id')) {
            $this->addSql('ALTER TABLE user_api_key ADD access_url_id INT DEFAULT NULL');
        }
        if (!$table->hasColumn('key_prefix')) {
            $this->addSql('ALTER TABLE user_api_key ADD key_prefix VARCHAR(32) DEFAULT NULL');
        }
        if (!$table->hasColumn('last_used_at')) {
            $this->addSql("ALTER TABLE user_api_key ADD last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");
        }
        if (!$table->hasColumn('revoked_at')) {
            $this->addSql("ALTER TABLE user_api_key ADD revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");
        }

        if (!$table->hasIndex('idx_user_api_key_mcp_lookup')) {
            $this->addSql('CREATE INDEX idx_user_api_key_mcp_lookup ON user_api_key (api_service, api_key, access_url_id, revoked_at)');
        }
        if (!$table->hasIndex('uniq_user_api_key_service_url')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_user_api_key_service_url ON user_api_key (user_id, api_service, access_url_id)');
        }
        if (!$table->hasForeignKey('FK_USER_API_KEY_ACCESS_URL')) {
            $this->addSql('ALTER TABLE user_api_key ADD CONSTRAINT FK_USER_API_KEY_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user_api_key')) {
            return;
        }

        $table = $schema->getTable('user_api_key');

        if ($table->hasForeignKey('FK_USER_API_KEY_ACCESS_URL')) {
            $this->addSql('ALTER TABLE user_api_key DROP FOREIGN KEY FK_USER_API_KEY_ACCESS_URL');
        }
        if ($table->hasIndex('uniq_user_api_key_service_url')) {
            $this->addSql('DROP INDEX uniq_user_api_key_service_url ON user_api_key');
        }
        if ($table->hasIndex('idx_user_api_key_mcp_lookup')) {
            $this->addSql('DROP INDEX idx_user_api_key_mcp_lookup ON user_api_key');
        }
        if ($table->hasColumn('revoked_at')) {
            $this->addSql('ALTER TABLE user_api_key DROP revoked_at');
        }
        if ($table->hasColumn('last_used_at')) {
            $this->addSql('ALTER TABLE user_api_key DROP last_used_at');
        }
        if ($table->hasColumn('key_prefix')) {
            $this->addSql('ALTER TABLE user_api_key DROP key_prefix');
        }
        if ($table->hasColumn('access_url_id')) {
            $this->addSql('ALTER TABLE user_api_key DROP access_url_id');
        }

        // Keep VARCHAR(64): legacy key generation already creates 64-character values.
    }
}
