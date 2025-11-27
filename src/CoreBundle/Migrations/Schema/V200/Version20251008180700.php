<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251008180700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'ExtraField: deduplicate organisationemail/azure_id (USER), fix display_text, and add unique index on (variable, item_type).';
    }

    public function up(Schema $schema): void
    {
        // Normalize labels
        // USER (item_type = 1)
        $this->addSql("UPDATE extra_field SET display_text = 'LinkedIn profile URL' WHERE item_type = 1 AND variable = 'linkedin_url'");
        $this->addSql("UPDATE extra_field SET display_text = 'Tags' WHERE item_type = 1 AND variable = 'tags'");
        $this->addSql("UPDATE extra_field SET display_text = 'Legal agreement accepted' WHERE item_type = 1 AND variable = 'legal_accept'");
        $this->addSql("UPDATE extra_field SET display_text = 'GDPR' WHERE item_type = 1 AND variable = 'gdpr'");

        // COURSE (item_type = 2)
        $this->addSql("UPDATE extra_field SET display_text = 'Video URL' WHERE item_type = 2 AND variable = 'video_url'");

        // Enforce expected labels for Azure-related fields and deduplicate
        // A: organisationemail (USER): enforce correct label and keep a single row
        $this->addSql("UPDATE extra_field SET display_text = 'Organisation e-mail' WHERE item_type = 1 AND variable = 'organisationemail'");
        $this->addSql("
            DELETE FROM extra_field
            WHERE item_type = 1
              AND variable = 'organisationemail'
              AND id NOT IN (
                SELECT id FROM (
                  SELECT MIN(id) AS id
                  FROM extra_field
                  WHERE item_type = 1 AND variable = 'organisationemail'
                ) t
              )
        ");

        // B: azure_id (USER): enforce correct label and keep a single row
        $this->addSql("UPDATE extra_field SET display_text = 'Azure ID (mailNickname)' WHERE item_type = 1 AND variable = 'azure_id'");
        $this->addSql("
            DELETE FROM extra_field
            WHERE item_type = 1
              AND variable = 'azure_id'
              AND id NOT IN (
                SELECT id FROM (
                  SELECT MIN(id) AS id
                  FROM extra_field
                  WHERE item_type = 1 AND variable = 'azure_id'
                ) t
              )
        ");

        // Unique index to prevent future duplicates
        $this->addSql('CREATE UNIQUE INDEX uniq_extra_field_variable_itemtype ON extra_field (variable, item_type)');
    }

    public function down(Schema $schema): void
    {
        // Drop unique index
        $this->addSql('DROP INDEX uniq_extra_field_variable_itemtype ON extra_field');

        // Revert labels (optional). If you prefer not to revert, you can omit these lines.
        $this->addSql("UPDATE extra_field SET display_text = 'LinkedInUrl' WHERE item_type = 1 AND variable = 'linkedin_url'");
        $this->addSql("UPDATE extra_field SET display_text = 'tags' WHERE item_type = 1 AND variable = 'tags'");
        $this->addSql("UPDATE extra_field SET display_text = 'Legal' WHERE item_type = 1 AND variable = 'legal_accept'");
        $this->addSql("UPDATE extra_field SET display_text = 'GDPR compliance' WHERE item_type = 1 AND variable = 'gdpr'");
        $this->addSql("UPDATE extra_field SET display_text = 'VideoUrl' WHERE item_type = 2 AND variable = 'video_url'");
    }
}
