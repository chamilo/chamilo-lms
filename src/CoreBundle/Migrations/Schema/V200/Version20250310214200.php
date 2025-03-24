<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250310214200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Creates tables conference_meeting, conference_recording, and conference_activity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE conference_meeting (
                id INT AUTO_INCREMENT NOT NULL,
                c_id INT DEFAULT NULL,
                session_id INT DEFAULT NULL,
                access_url_id INT DEFAULT NULL,
                group_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,
                calendar_id INT DEFAULT NULL,
                service_provider VARCHAR(20) NOT NULL,
                remote_id VARCHAR(255) DEFAULT NULL,
                internal_meeting_id VARCHAR(255) DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                attendee_pw VARCHAR(255) DEFAULT NULL,
                moderator_pw VARCHAR(255) DEFAULT NULL,
                record TINYINT(1) NOT NULL DEFAULT 0,
                status INT NOT NULL DEFAULT 0,
                welcome_msg TEXT DEFAULT NULL,
                visibility INT NOT NULL DEFAULT 1,
                voice_bridge INT DEFAULT NULL,
                video_url VARCHAR(255) DEFAULT NULL,
                has_video_m4v TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                closed_at DATETIME DEFAULT NULL,
                meeting_list_item TEXT DEFAULT NULL,
                meeting_info_get TEXT DEFAULT NULL,
                sign_attendance TINYINT(1) NOT NULL DEFAULT 0,
                reason_to_sign_attendance TEXT DEFAULT NULL,
                account_email VARCHAR(255) DEFAULT NULL,
                webinar_schema TEXT DEFAULT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_EE87E8191D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE,
                CONSTRAINT FK_EE87E81613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE,
                CONSTRAINT FK_EE87E8173444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE,
                CONSTRAINT FK_EE87E81FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE,
                CONSTRAINT FK_EE87E81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ');

        $this->addSql('
            CREATE TABLE conference_recording (
                id INT AUTO_INCREMENT NOT NULL,
                meeting_id INT NOT NULL,
                format_type VARCHAR(50) NOT NULL,
                resource_url TEXT NOT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_F7FF7ACB67433D9C FOREIGN KEY (meeting_id) REFERENCES conference_meeting (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ');

        $this->addSql('
            CREATE TABLE conference_activity (
                id INT AUTO_INCREMENT NOT NULL,
                meeting_id INT NOT NULL,
                participant_id INT NOT NULL,
                in_at DATETIME DEFAULT NULL,
                out_at DATETIME DEFAULT NULL,
                close TINYINT(1) NOT NULL DEFAULT 0,
                type VARCHAR(50) NOT NULL,
                event VARCHAR(255) NOT NULL,
                activity_data LONGTEXT DEFAULT NULL,
                signature_file VARCHAR(255) DEFAULT NULL,
                signed_at DATETIME DEFAULT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_6935CF7B67433D9C FOREIGN KEY (meeting_id) REFERENCES conference_meeting (id) ON DELETE CASCADE,
                CONSTRAINT FK_6935CF7B9D1C3019 FOREIGN KEY (participant_id) REFERENCES user (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS conference_activity;');
        $this->addSql('DROP TABLE IF EXISTS conference_recording;');
        $this->addSql('DROP TABLE IF EXISTS conference_meeting;');
    }
}
