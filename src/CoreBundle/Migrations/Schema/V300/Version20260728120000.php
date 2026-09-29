<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds the generic OAuth 2.1 Authorization Server tables: registered clients
 * (oauth_client), one-time authorization codes (oauth_authorization_code),
 * bearer access tokens (oauth_access_token), and refresh tokens
 * (oauth_refresh_token, which also doubles as the "connected app" grant
 * record). Consumed first by McpBearerAuthenticator for the /mcp resource
 * server; not MCP-specific by design.
 *
 * This migration deliberately does not also touch settings/settings_current
 * — the settings-fixtures-upsert for the new opt-in platform setting lives in
 * its own separate migration file.
 */
final class Version20260728120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add OAuth 2.1 authorization server tables (client, authorization code, access token, refresh token)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('oauth_client')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE oauth_client (
              id INT AUTO_INCREMENT NOT NULL,
              client_id VARCHAR(64) NOT NULL,
              client_secret_hash VARCHAR(64) DEFAULT NULL,
              client_secret_prefix VARCHAR(32) DEFAULT NULL,
              token_endpoint_auth_method VARCHAR(32) NOT NULL,
              client_name VARCHAR(255) DEFAULT NULL,
              client_uri LONGTEXT DEFAULT NULL,
              logo_uri LONGTEXT DEFAULT NULL,
              policy_uri LONGTEXT DEFAULT NULL,
              tos_uri LONGTEXT DEFAULT NULL,
              software_id VARCHAR(255) DEFAULT NULL,
              software_version VARCHAR(64) DEFAULT NULL,
              redirect_uris JSON NOT NULL COMMENT '(DC2Type:json)',
              grant_types JSON NOT NULL COMMENT '(DC2Type:json)',
              response_types JSON NOT NULL COMMENT '(DC2Type:json)',
              scope VARCHAR(255) DEFAULT NULL,
              access_url_id INT DEFAULT NULL,
              registration_ip VARCHAR(45) DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              UNIQUE INDEX uniq_oauth_client_client_id (client_id),
              INDEX idx_oauth_client_url (access_url_id, revoked_at),
              INDEX idx_oauth_client_created (created_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE oauth_client ADD CONSTRAINT FK_OAUTH_CLIENT_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }

        if (!$schema->hasTable('oauth_authorization_code')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE oauth_authorization_code (
              id INT AUTO_INCREMENT NOT NULL,
              code_hash VARCHAR(64) NOT NULL,
              grant_id VARCHAR(36) NOT NULL,
              client_id INT NOT NULL,
              user_id INT NOT NULL,
              access_url_id INT DEFAULT NULL,
              redirect_uri LONGTEXT NOT NULL,
              code_challenge VARCHAR(128) NOT NULL,
              code_challenge_method VARCHAR(10) NOT NULL,
              scope VARCHAR(255) DEFAULT NULL,
              resource LONGTEXT DEFAULT NULL,
              consent_ip VARCHAR(45) DEFAULT NULL,
              consent_user_agent VARCHAR(255) DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              UNIQUE INDEX uniq_oauth_code_hash (code_hash),
              INDEX idx_oauth_code_grant (grant_id),
              INDEX idx_oauth_code_expires (expires_at),
              INDEX idx_oauth_code_user (user_id),
              INDEX idx_oauth_code_client (client_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE oauth_authorization_code ADD CONSTRAINT FK_OAUTH_CODE_CLIENT FOREIGN KEY (client_id) REFERENCES oauth_client (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_authorization_code ADD CONSTRAINT FK_OAUTH_CODE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_authorization_code ADD CONSTRAINT FK_OAUTH_CODE_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }

        if (!$schema->hasTable('oauth_access_token')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE oauth_access_token (
              id INT AUTO_INCREMENT NOT NULL,
              token_hash VARCHAR(64) NOT NULL,
              token_prefix VARCHAR(32) DEFAULT NULL,
              grant_id VARCHAR(36) NOT NULL,
              client_id INT NOT NULL,
              user_id INT NOT NULL,
              access_url_id INT DEFAULT NULL,
              scope VARCHAR(255) DEFAULT NULL,
              resource LONGTEXT DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              UNIQUE INDEX uniq_oauth_access_hash (token_hash),
              INDEX idx_oauth_access_grant (grant_id),
              INDEX idx_oauth_access_user (user_id, revoked_at),
              INDEX idx_oauth_access_expires (expires_at),
              INDEX idx_oauth_access_client (client_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE oauth_access_token ADD CONSTRAINT FK_OAUTH_ACCESS_CLIENT FOREIGN KEY (client_id) REFERENCES oauth_client (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_access_token ADD CONSTRAINT FK_OAUTH_ACCESS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_access_token ADD CONSTRAINT FK_OAUTH_ACCESS_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }

        if (!$schema->hasTable('oauth_refresh_token')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE oauth_refresh_token (
              id INT AUTO_INCREMENT NOT NULL,
              token_hash VARCHAR(64) NOT NULL,
              grant_id VARCHAR(36) NOT NULL,
              client_id INT NOT NULL,
              user_id INT NOT NULL,
              access_url_id INT DEFAULT NULL,
              scope VARCHAR(255) DEFAULT NULL,
              resource LONGTEXT DEFAULT NULL,
              consented_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              consent_ip VARCHAR(45) DEFAULT NULL,
              consent_user_agent VARCHAR(255) DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              absolute_expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              rotated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              replaced_by_id INT DEFAULT NULL,
              revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              revoked_reason VARCHAR(32) DEFAULT NULL,
              last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              UNIQUE INDEX uniq_oauth_refresh_hash (token_hash),
              INDEX idx_oauth_refresh_grant (grant_id),
              INDEX idx_oauth_refresh_user (user_id, revoked_at, rotated_at),
              INDEX idx_oauth_refresh_expires (expires_at),
              INDEX idx_oauth_refresh_client (client_id),
              INDEX idx_oauth_refresh_replaced_by (replaced_by_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_OAUTH_REFRESH_CLIENT FOREIGN KEY (client_id) REFERENCES oauth_client (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_OAUTH_REFRESH_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_OAUTH_REFRESH_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_refresh_token ADD CONSTRAINT FK_OAUTH_REFRESH_REPLACED_BY FOREIGN KEY (replaced_by_id) REFERENCES oauth_refresh_token (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse FK order: tables referencing oauth_client first, then oauth_client itself.
        if ($schema->hasTable('oauth_access_token')) {
            $this->addSql('DROP TABLE oauth_access_token');
        }

        if ($schema->hasTable('oauth_authorization_code')) {
            $this->addSql('DROP TABLE oauth_authorization_code');
        }

        if ($schema->hasTable('oauth_refresh_token')) {
            $this->addSql('DROP TABLE oauth_refresh_token');
        }

        if ($schema->hasTable('oauth_client')) {
            $this->addSql('DROP TABLE oauth_client');
        }
    }
}
