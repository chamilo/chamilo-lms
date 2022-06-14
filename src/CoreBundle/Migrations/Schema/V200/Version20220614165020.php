<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20220614165020 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate track_e_access, track_e_lastaccess, track_e_uploads, track_e_downloads, track_e_links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX access_session_id ON track_e_access');
        $this->addSql('DROP INDEX user_course_session_date ON track_e_access');
        $this->addSql('ALTER TABLE track_e_access CHANGE access_session_id session_id INT NOT NULL');
        $this->addSql('CREATE INDEX session_id ON track_e_access (session_id)');
        $this->addSql('CREATE INDEX user_course_session_date ON track_e_access (access_user_id, c_id, session_id, access_date)');

        $this->addSql('DROP INDEX access_session_id ON track_e_lastaccess');
        $this->addSql('ALTER TABLE track_e_lastaccess CHANGE access_session_id session_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX session_id ON track_e_lastaccess (session_id)');

        $this->addSql('DROP INDEX upload_session_id ON track_e_uploads');
        $this->addSql('ALTER TABLE track_e_uploads CHANGE upload_session_id session_id INT NOT NULL');
        $this->addSql('CREATE INDEX session_id ON track_e_uploads (session_id)');

        $this->addSql('DROP INDEX down_session_id ON track_e_downloads');
        $this->addSql('ALTER TABLE track_e_downloads CHANGE down_session_id session_id INT NOT NULL');
        $this->addSql('CREATE INDEX session_id ON track_e_downloads (session_id)');

        $this->addSql('DROP INDEX links_session_id ON track_e_links');
        $this->addSql('ALTER TABLE track_e_links CHANGE links_session_id session_id INT NOT NULL');
        $this->addSql('CREATE INDEX session_id ON track_e_links (session_id)');
    }

    public function down(Schema $schema): void
    {
    }
}
