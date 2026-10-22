<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261022150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add the transcript table holding the signed official transcripts of users.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('transcript')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE transcript (
  id INT AUTO_INCREMENT NOT NULL,
  user_id INT NOT NULL,
  issued_by_id INT DEFAULT NULL,
  access_url_id INT NOT NULL,
  token VARCHAR(64) NOT NULL,
  payload_json LONGTEXT NOT NULL,
  signature LONGTEXT NOT NULL,
  public_key_hex VARCHAR(64) NOT NULL,
  issued_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  UNIQUE INDEX uniq_transcript_token (token),
  INDEX idx_transcript_user (user_id),
  INDEX IDX_A8F617C3784BB717 (issued_by_id),
  INDEX IDX_A8F617C373444FD5 (access_url_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
        $this->addSql('ALTER TABLE transcript ADD CONSTRAINT FK_TRANSCRIPT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transcript ADD CONSTRAINT FK_TRANSCRIPT_ISSUED_BY FOREIGN KEY (issued_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE transcript ADD CONSTRAINT FK_TRANSCRIPT_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Issued transcripts must not be silently destroyed during an application rollback.
    }
}
