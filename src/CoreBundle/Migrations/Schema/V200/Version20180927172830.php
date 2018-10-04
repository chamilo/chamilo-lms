<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20180927172830.
 *
 * Add foreing keys between forum category - forum - forum thread - forum post
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20180927172830 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE c_forum_post SET thread_id = NULL WHERE thread_id NOT IN (SELECT iid FROM c_forum_thread)');
        $this->addSql('UPDATE c_forum_thread SET forum_id = NULL WHERE forum_id NOT IN (SELECT iid FROM c_forum_forum)');
        $this->addSql('UPDATE c_forum_forum SET forum_category = NULL WHERE forum_category NOT IN (SELECT iid FROM c_forum_category)');

        $this->addSql('ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF559E2904019 FOREIGN KEY (thread_id) REFERENCES c_forum_thread (iid)');
        $this->addSql('ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C9921BF9426 FOREIGN KEY (forum_category) REFERENCES c_forum_category (iid)');
        $this->addSql('CREATE INDEX IDX_47A9C9921BF9426 ON c_forum_forum (forum_category)');
        $this->addSql('ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884C29CCBAD0 FOREIGN KEY (forum_id) REFERENCES c_forum_forum (iid)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
