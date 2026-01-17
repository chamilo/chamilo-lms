<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Command\DoctrineMigrationsMigrateCommandDecorator;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;

final class Version20251001221600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to drop unused tables and ensure schema consistency.';
    }

    public function up(Schema $schema): void
    {
        $skipAttendances = (bool) getenv(DoctrineMigrationsMigrateCommandDecorator::SKIP_ATTENDANCES_FLAG);
        $platform = $this->connection->getDatabasePlatform();

        // MySQL-only FK checks toggle
        if ($platform instanceof MySQLPlatform) {
            $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');
        }

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
            // 'c_item_property' handled separately below
            'c_survey_group',
            'c_permission_group',
            'c_role_group',
            'track_e_open',
        ];

        foreach ($tablesToDrop as $table) {
            if ($schema->hasTable($table)) {
                $this->addSql('DROP TABLE '.$this->quoteIdentifier($platform, $table).';');
            }
        }

        // If skip-attendances is enabled, we keep c_item_property because the post-migration command
        // "chamilo:migration:migrate-attendances-fast" needs it to map attendances to courses and metadata.
        if ($schema->hasTable('c_item_property')) {
            if ($skipAttendances) {
                $this->write('skip-attendances enabled: keeping c_item_property for post-migration attendance fast migration.');
            } else {
                $this->addSql('DROP TABLE '.$this->quoteIdentifier($platform, 'c_item_property').';');
            }
        }

        if ($platform instanceof MySQLPlatform) {
            $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    public function down(Schema $schema): void {}

    private function quoteIdentifier(AbstractPlatform $platform, string $name): string
    {
        return $platform->quoteIdentifier($name);
    }
}
