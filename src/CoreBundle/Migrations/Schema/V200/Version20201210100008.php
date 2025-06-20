<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201210100008 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add parent_media_id column to c_quiz_question for grouping media-related questions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz_question ADD parent_media_id INT DEFAULT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz_question DROP COLUMN parent_media_id;');
    }
}
