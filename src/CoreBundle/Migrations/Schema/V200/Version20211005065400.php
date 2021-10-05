<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20211005065400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Plugins - bbb';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('plugin_bbb_meeting')) {
            $table = $schema->getTable('plugin_bbb_meeting');
            if (!$table->hasColumn('internal_meeting_id')) {
                //$this->addSql('ALTER TABLE plugin_bbb_meeting ADD COLUMN internal_meeting_id VARCHAR(255) DEFAULT NULL;');
            }
        }

        if ($schema->hasTable('plugin_bbb_room')) {
            $table = $schema->getTable('plugin_bbb_room');
            if (!$table->hasColumn('ALTER TABLE plugin_bbb_room ADD close INT NOT NULL DEFAULT 0;')) {
                $this->addSql('');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
