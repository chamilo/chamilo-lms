<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261021160000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add signed certificate snapshots, consent scope, and optional Sepolia testnet anchors';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('gradebook_certificate')) {
            $table = $schema->getTable('gradebook_certificate');
            if (!$table->hasColumn('user_publication_consent_scope')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_consent_scope VARCHAR(40) DEFAULT NULL');
            }
        }

        if (!$schema->hasTable('gradebook_certificate_credential')) {
            $this->addSql(<<<'SQL'
CREATE TABLE gradebook_certificate_credential (
  id INT AUTO_INCREMENT NOT NULL,
  certificate_id INT DEFAULT NULL,
  token VARCHAR(64) NOT NULL,
  payload_json LONGTEXT NOT NULL,
  signature LONGTEXT NOT NULL,
  public_key_hex VARCHAR(64) NOT NULL,
  anchor_hash VARCHAR(64) NOT NULL,
  issued_at DATETIME NOT NULL,
  revoked_at DATETIME DEFAULT NULL,
  chain_id INT DEFAULT NULL,
  tx_hash VARCHAR(66) DEFAULT NULL,
  anchored_at DATETIME DEFAULT NULL,
  UNIQUE INDEX uniq_gcc_token (token),
  UNIQUE INDEX uniq_gcc_tx_hash (tx_hash),
  INDEX idx_gcc_cert (certificate_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
            $this->addSql('ALTER TABLE gradebook_certificate_credential ADD CONSTRAINT FK_GCC_CERT FOREIGN KEY (certificate_id) REFERENCES gradebook_certificate (id) ON DELETE SET NULL');
        }

        // The feature is opt-in and does not touch public certificate visibility.
        if ($schema->hasTable('settings')) {
            $this->addSql(<<<'SQL'
INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable)
SELECT 'blockchain_verification_enabled', 'certificate',
       'Enable signed certificate credentials and Sepolia testnet anchoring',
       'Off by default. This enables manual issuance and optional wallet-signed testnet transactions; no personal data is written on-chain.',
       'false', 1, 0
WHERE NOT EXISTS (
    SELECT 1 FROM settings
    WHERE variable = 'blockchain_verification_enabled'
      AND category = 'certificate'
      AND access_url = 1
      AND subkey IS NULL
)
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // Never erase consent scope, signed credentials, or blockchain audit history.
    }
}
