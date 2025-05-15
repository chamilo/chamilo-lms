<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201210100007 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Move adaptive destination from c_quiz_answer to c_quiz_rel_question.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz_rel_question ADD COLUMN destination TEXT DEFAULT NULL;');
        $this->addSql('ALTER TABLE c_quiz_answer DROP COLUMN destination;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz_rel_question DROP COLUMN destination;');
        $this->addSql('ALTER TABLE c_quiz_answer ADD COLUMN destination TEXT DEFAULT NULL;');
    }
}
