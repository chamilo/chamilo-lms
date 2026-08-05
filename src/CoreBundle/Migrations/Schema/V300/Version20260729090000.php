<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260729090000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create c_lp_iv_comment table for SCORM 2004 cmi.comments_from_learner and cmi.comments_from_lms support.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_lp_iv_comment')) {
            $this->addSql('
                CREATE TABLE c_lp_iv_comment (
                    iid INT AUTO_INCREMENT NOT NULL,
                    c_id INT NOT NULL,
                    lp_iv_id INT NOT NULL,
                    order_id INT NOT NULL,
                    source VARCHAR(16) NOT NULL,
                    comment LONGTEXT NOT NULL,
                    location VARCHAR(255) NOT NULL,
                    comment_timestamp VARCHAR(32) NOT NULL,
                    PRIMARY KEY(iid),
                    INDEX course (c_id),
                    INDEX lp_iv_id (lp_iv_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS c_lp_iv_comment;');
    }
}
