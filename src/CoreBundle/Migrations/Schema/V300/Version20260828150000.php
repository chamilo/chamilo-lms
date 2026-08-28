<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds certificate expiry support: an `expiry_date` column on
 * gradebook_certificate (auto-computed from the category's
 * certificateValidityPeriod, or manually set by a teacher when no validity
 * period is configured), and gradebook_certificate_expiry_notification, which
 * tracks reminder emails already sent so the cron and the teacher UI don't
 * double-notify the same learner for the same expiry date.
 */
final class Version20260828150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add expiry_date to gradebook_certificate and the gradebook_certificate_expiry_notification table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('gradebook_certificate')) {
            $table = $schema->getTable('gradebook_certificate');

            if (!$table->hasColumn('expiry_date')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD expiry_date DATE DEFAULT NULL COMMENT \'(DC2Type:date)\';');
            }

            if (!$table->hasIndex('idx_gradebook_certificate_expiry_date')) {
                $this->addSql('CREATE INDEX idx_gradebook_certificate_expiry_date ON gradebook_certificate (expiry_date);');
            }
        }

        if (!$schema->hasTable('gradebook_certificate_expiry_notification')) {
            $this->addSql(<<<'SQL'
            CREATE TABLE gradebook_certificate_expiry_notification (
              id INT AUTO_INCREMENT NOT NULL,
              certificate_id INT NOT NULL,
              notification_type VARCHAR(32) NOT NULL,
              expiry_date_at_send DATE NOT NULL COMMENT '(DC2Type:date)',
              sent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              sent_by_user_id INT DEFAULT NULL,
              INDEX idx_gce_notification_cert_type (certificate_id, notification_type),
              INDEX idx_gce_notification_sent_at (sent_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE gradebook_certificate_expiry_notification ADD CONSTRAINT FK_GCE_NOTIFICATION_CERTIFICATE FOREIGN KEY (certificate_id) REFERENCES gradebook_certificate (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE gradebook_certificate_expiry_notification ADD CONSTRAINT FK_GCE_NOTIFICATION_SENT_BY FOREIGN KEY (sent_by_user_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('gradebook_certificate_expiry_notification')) {
            $this->addSql('DROP TABLE gradebook_certificate_expiry_notification');
        }

        if ($schema->hasTable('gradebook_certificate')) {
            $table = $schema->getTable('gradebook_certificate');

            if ($table->hasIndex('idx_gradebook_certificate_expiry_date')) {
                $this->addSql('DROP INDEX idx_gradebook_certificate_expiry_date ON gradebook_certificate;');
            }

            if ($table->hasColumn('expiry_date')) {
                $this->addSql('ALTER TABLE gradebook_certificate DROP COLUMN expiry_date;');
            }
        }
    }
}
