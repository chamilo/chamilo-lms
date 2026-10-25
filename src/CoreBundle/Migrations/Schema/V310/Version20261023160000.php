<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Chamilo 1.11 left status 0 on forum posts that never went through moderation, and only applied
 * the status in moderated forums: such a post was shown to learners in a forum that is not
 * moderated, and waiting for moderation in a moderated one. Master has no status 0 and hides it
 * from learners everywhere, so give these posts the status that keeps their 1.11 behaviour: none
 * (shown if visible) outside moderated forums, waiting for moderation in moderated ones.
 */
final class Version20261023160000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Convert the forum posts left with the 1.11 status 0 to the master status matching their 1.11 behaviour.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_forum_post') || !$schema->hasTable('c_forum_forum')) {
            return;
        }

        $this->addSql('UPDATE c_forum_post SET status = 2 WHERE status = 0 AND forum_id IN (SELECT iid FROM c_forum_forum WHERE moderated = 1)');
        $this->addSql('UPDATE c_forum_post SET status = NULL WHERE status = 0');
    }

    public function down(Schema $schema): void
    {
        // The status 0 has no meaning in this version: there is nothing to restore.
    }
}
