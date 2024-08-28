<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240811221500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to drop unnecessary foreign keys and adjust table structure for data consistency.';
    }

    public function up(Schema $schema): void
    {
        // Disable foreign key checks to prevent issues during migration
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');

        // Drop foreign keys from the specified tables, only if the table exists
        if ($schema->hasTable('page__snapshot')) {
            $this->addSql('ALTER TABLE page__snapshot DROP FOREIGN KEY IF EXISTS FK_3963EF9AC4663E4;');
            $this->addSql('ALTER TABLE page__snapshot DROP FOREIGN KEY IF EXISTS FK_3963EF9AF6BD1646;');
        }

        if ($schema->hasTable('classification__collection')) {
            $this->addSql('ALTER TABLE classification__collection DROP FOREIGN KEY IF EXISTS FK_A406B56AE25D857E;');
            $this->addSql('ALTER TABLE classification__collection DROP FOREIGN KEY IF EXISTS FK_A406B56AEA9FDD75;');
        }

        if ($schema->hasTable('faq_question')) {
            $this->addSql('ALTER TABLE faq_question DROP FOREIGN KEY IF EXISTS FK_4A55B05912469DE2;');
        }

        if ($schema->hasTable('page__page')) {
            $this->addSql('ALTER TABLE page__page DROP FOREIGN KEY IF EXISTS FK_2FAE39ED727ACA70;');
            $this->addSql('ALTER TABLE page__page DROP FOREIGN KEY IF EXISTS FK_2FAE39EDF6BD1646;');
            $this->addSql('ALTER TABLE page__page DROP FOREIGN KEY IF EXISTS FK_2FAE39ED158E0B66;');
        }

        if ($schema->hasTable('page__bloc')) {
            $this->addSql('ALTER TABLE page__bloc DROP FOREIGN KEY IF EXISTS FK_FCDC1A97727ACA70;');
            $this->addSql('ALTER TABLE page__bloc DROP FOREIGN KEY IF EXISTS FK_FCDC1A97C4663E4;');
        }

        if ($schema->hasTable('timeline__timeline')) {
            $this->addSql('ALTER TABLE timeline__timeline DROP FOREIGN KEY IF EXISTS FK_FFBC6AD523EDC87;');
            $this->addSql('ALTER TABLE timeline__timeline DROP FOREIGN KEY IF EXISTS FK_FFBC6AD59D32F035;');
        }

        if ($schema->hasTable('plugin_bbb_room')) {
            $this->addSql('ALTER TABLE plugin_bbb_room DROP FOREIGN KEY IF EXISTS plugin_bbb_room_ibfk_2;');
            $this->addSql('ALTER TABLE plugin_bbb_room DROP FOREIGN KEY IF EXISTS plugin_bbb_room_ibfk_1;');
        }

        if ($schema->hasTable('classification__category')) {
            $this->addSql('ALTER TABLE classification__category DROP FOREIGN KEY IF EXISTS FK_43629B36727ACA70;');
            $this->addSql('ALTER TABLE classification__category DROP FOREIGN KEY IF EXISTS FK_43629B36E25D857E;');
            $this->addSql('ALTER TABLE classification__category DROP FOREIGN KEY IF EXISTS FK_43629B36EA9FDD75;');
        }

        if ($schema->hasTable('faq_question_translation')) {
            $this->addSql('ALTER TABLE faq_question_translation DROP FOREIGN KEY IF EXISTS FK_C2D1A2C2AC5D3;');
        }

        if ($schema->hasTable('classification__tag')) {
            $this->addSql('ALTER TABLE classification__tag DROP FOREIGN KEY IF EXISTS FK_CA57A1C7E25D857E;');
        }

        if ($schema->hasTable('faq_category_translation')) {
            $this->addSql('ALTER TABLE faq_category_translation DROP FOREIGN KEY IF EXISTS FK_5493B0FC2C2AC5D3;');
        }

        if ($schema->hasTable('timeline__action_component')) {
            $this->addSql('ALTER TABLE timeline__action_component DROP FOREIGN KEY IF EXISTS FK_6ACD1B16E2ABAFFF;');
            $this->addSql('ALTER TABLE timeline__action_component DROP FOREIGN KEY IF EXISTS FK_6ACD1B169D32F035;');
        }

        if ($schema->hasTable('media__media')) {
            $this->addSql('ALTER TABLE media__media DROP FOREIGN KEY IF EXISTS FK_5C6DD74E12469DE2;');
        }

        if ($schema->hasTable('contact_category_translation')) {
            $this->addSql('ALTER TABLE contact_category_translation DROP FOREIGN KEY IF EXISTS FK_3E770F302C2AC5D3;');
        }

        if ($schema->hasTable('media__gallery_media')) {
            $this->addSql('ALTER TABLE media__gallery_media DROP FOREIGN KEY IF EXISTS FK_80D4C541EA9FDD75;');
            $this->addSql('ALTER TABLE media__gallery_media DROP FOREIGN KEY IF EXISTS FK_80D4C5414E7AF8F;');
        }

        if ($schema->hasTable('c_blog')) {
            $this->addSql('ALTER TABLE c_blog DROP FOREIGN KEY IF EXISTS FK_64B00A121BAD783F;');
        }

        // Re-enable foreign key checks
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void {}
}
