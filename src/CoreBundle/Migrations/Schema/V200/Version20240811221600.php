<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240811221600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to drop unused tables and ensure schema consistency.';
    }

    public function up(Schema $schema): void
    {
        // Disable foreign key checks to prevent issues during migration
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');

        // Drop tables if they exist
        $tablesToDrop = [
            'page__snapshot',
            'class_item',
            'classification__collection',
            'c_userinfo_def',
            'class_user',
            'faq_question',
            'timeline__component',
            'page__page',
            'c_online_connected',
            'c_permission_task',
            'page__bloc',
            'c_online_link',
            'c_permission_user',
            'c_role_user',
            'c_role',
            'page__site',
            'shared_survey',
            'media__gallery',
            'faq_category',
            'classification__context',
            'timeline__timeline',
            'classification__category',
            'faq_question_translation',
            'c_userinfo_content',
            'contact_category',
            'classification__tag',
            'faq_category_translation',
            'timeline__action_component',
            'media__media',
            'c_role_permissions',
            'shared_survey_question_option',
            'shared_survey_question',
            'timeline__action',
            'contact_category_translation',
            'media__gallery_media',
            'c_item_property',
            'c_survey_group',
            'c_permission_group',
            'c_role_group',
            'track_e_open',
        ];

        foreach ($tablesToDrop as $table) {
            // Check if the table exists before attempting to drop it
            if ($schema->hasTable($table)) {
                $this->addSql("DROP TABLE $table;");
            }
        }

        // Re-enable foreign key checks
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void {}
}
