<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261014170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Harden OAuth clients with resource allowlists and durable consent history';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('oauth_client')) {
            $table = $schema->getTable('oauth_client');
            if (!$table->hasColumn('allowed_resources')) {
                $this->addSql("ALTER TABLE oauth_client ADD allowed_resources JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
                $this->addSql("UPDATE oauth_client SET allowed_resources = JSON_ARRAY('mcp') WHERE allowed_resources IS NULL");
                $this->addSql("ALTER TABLE oauth_client MODIFY allowed_resources JSON NOT NULL COMMENT '(DC2Type:json)'");
            }

            // Clients created before generic scopes existed were MCP clients.
            $this->addSql("UPDATE oauth_client SET scope = 'mcp' WHERE scope IS NULL OR TRIM(scope) = ''");

            // OAuthGarbageCollectorCommand only revokes clients whose last_used_at
            // is still NULL. Preserve established clients created before
            // last_used_at started being touched at successful consent.
            if ($schema->hasTable('oauth_authorization_code')
                && $schema->hasTable('oauth_access_token')
                && $schema->hasTable('oauth_refresh_token')
            ) {
                $this->addSql(<<<'SQL'
                UPDATE oauth_client client
                JOIN (
                    SELECT usage_rows.client_id, MAX(usage_rows.used_at) AS used_at
                    FROM (
                        SELECT client_id, created_at AS used_at FROM oauth_authorization_code
                        UNION ALL
                        SELECT client_id, COALESCE(last_used_at, created_at) AS used_at FROM oauth_access_token
                        UNION ALL
                        SELECT client_id, COALESCE(last_used_at, created_at, consented_at) AS used_at FROM oauth_refresh_token
                    ) usage_rows
                    GROUP BY usage_rows.client_id
                ) client_usage ON client_usage.client_id = client.id
                SET client.last_used_at = client_usage.used_at
                WHERE client.last_used_at IS NULL
                SQL);
            }
        }

        foreach (['oauth_authorization_code', 'oauth_access_token', 'oauth_refresh_token'] as $tableName) {
            if ($schema->hasTable($tableName) && $schema->getTable($tableName)->hasColumn('scope')) {
                $this->addSql(\sprintf("UPDATE %s SET scope = 'mcp' WHERE scope IS NULL OR TRIM(scope) = ''", $tableName));
            }
        }

        if (!$schema->hasTable('oauth_client_consent')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE oauth_client_consent (
              id INT AUTO_INCREMENT NOT NULL,
              client_id INT NOT NULL,
              user_id INT NOT NULL,
              first_granted_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              UNIQUE INDEX uniq_oauth_client_consent_user_client (user_id, client_id),
              INDEX idx_oauth_client_consent_client_granted (client_id, first_granted_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE oauth_client_consent ADD CONSTRAINT FK_OAUTH_CLIENT_CONSENT_CLIENT FOREIGN KEY (client_id) REFERENCES oauth_client (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE oauth_client_consent ADD CONSTRAINT FK_OAUTH_CLIENT_CONSENT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

            // Preserve as much pre-migration consent history as the retained
            // authorization-code/refresh-token rows can still prove. Older
            // rows already removed by OAuth GC cannot be reconstructed.
            $this->addSql(<<<'SQL'
            INSERT INTO oauth_client_consent (client_id, user_id, first_granted_at)
            SELECT history.client_id, history.user_id, MIN(history.granted_at)
            FROM (
                SELECT client_id, user_id, created_at AS granted_at
                FROM oauth_authorization_code
                UNION ALL
                SELECT client_id, user_id, consented_at AS granted_at
                FROM oauth_refresh_token
            ) history
            GROUP BY history.client_id, history.user_id
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('oauth_client_consent')) {
            $this->addSql('DROP TABLE oauth_client_consent');
        }

        if ($schema->hasTable('oauth_client') && $schema->getTable('oauth_client')->hasColumn('allowed_resources')) {
            $this->addSql('ALTER TABLE oauth_client DROP allowed_resources');
        }
    }
}
