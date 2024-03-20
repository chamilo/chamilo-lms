<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230904173401 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Calendar: Cleanup about invitations/subscriptions';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('agenda_event_invitation')) {

            $this->addSql('ALTER TABLE personal_agenda DROP FOREIGN KEY FK_D8612460AF68C6B');
            $this->addSql("DROP INDEX UNIQ_D8612460AF68C6B ON personal_agenda");

            $this->addSql("ALTER TABLE personal_agenda DROP agenda_event_invitation_id, DROP collective, DROP subscription_visibility, DROP subscription_item_id");

            $this->addSql("ALTER TABLE agenda_event_invitation DROP FOREIGN KEY FK_52A2D5E161220EA6");
            $this->addSql("DROP TABLE agenda_event_invitation");

            $this->addSql("ALTER TABLE agenda_event_invitee DROP FOREIGN KEY FK_4F5757FEA76ED395");
            $this->addSql("DROP TABLE agenda_event_invitee");
        }
    }
}
