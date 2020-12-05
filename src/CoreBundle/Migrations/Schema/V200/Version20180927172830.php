<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Forums.
 */
class Version20180927172830 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_forum_post');
        if (!$table->hasIndex('c_id_visible_post_date')) {
            $this->addSql('CREATE INDEX c_id_visible_post_date ON c_forum_post (c_id, visible, post_date)');
        }

        if (!$table->hasForeignKey('FK_B5BEF559E2904019')) {
            $this->addSql(
                'ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF559E2904019 FOREIGN KEY (thread_id) REFERENCES c_forum_thread (iid)'
            );
        }

        $this->addSql('UPDATE c_forum_post SET thread_id = NULL WHERE thread_id NOT IN (SELECT iid FROM c_forum_thread)');
        $this->addSql('UPDATE c_forum_thread SET forum_id = NULL WHERE forum_id NOT IN (SELECT iid FROM c_forum_forum)');
        $this->addSql('UPDATE c_forum_forum SET forum_category = NULL WHERE forum_category NOT IN (SELECT iid FROM c_forum_category)');

        $table = $schema->getTable('c_forum_forum');
        if (!$table->hasForeignKey('FK_47A9C9921BF9426')) {
            $this->addSql(
                'ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C9921BF9426 FOREIGN KEY (forum_category) REFERENCES c_forum_category (iid)'
            );
        }

        if (!$table->hasIndex('IDX_47A9C9921BF9426')) {
            $this->addSql('CREATE INDEX IDX_47A9C9921BF9426 ON c_forum_forum (forum_category)');
        }

        $table = $schema->getTable('c_forum_thread');
        if (!$table->hasForeignKey('FK_5DA7884C29CCBAD0')) {
            $this->addSql('ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884C29CCBAD0 FOREIGN KEY (forum_id) REFERENCES c_forum_forum (iid)');
        }

    }

    public function down(Schema $schema): void
    {
    }
}
