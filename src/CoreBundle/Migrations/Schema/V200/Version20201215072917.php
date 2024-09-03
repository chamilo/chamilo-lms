<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201215072917 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds allow_careers_in_global_agenda setting and updates c_calendar_event table';
    }

    public function up(Schema $schema): void
    {
        $settingExists = $this->connection->fetchOne("SELECT COUNT(*) FROM settings WHERE variable = 'allow_careers_in_global_agenda'");

        $selectedValue = $this->getConfigurationSelectedValue();

        if (0 == $settingExists) {
            $this->addSql(
                "INSERT INTO settings (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, 'allow_careers_in_global_agenda', 'agenda', '$selectedValue', 'Allow careers and promotions in global agenda', 1, 0)"
            );
        } else {
            $this->addSql(
                "UPDATE settings SET selected_value = '$selectedValue' WHERE variable = 'allow_careers_in_global_agenda'"
            );
        }

        // Update c_calendar_event table
        if (!$schema->getTable('c_calendar_event')->hasColumn('career_id')) {
            $this->addSql('ALTER TABLE c_calendar_event ADD career_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_calendar_event ADD CONSTRAINT FK_C_CALENDAR_EVENT_CAREER FOREIGN KEY (career_id) REFERENCES career (id)');
            $this->addSql('CREATE INDEX IDX_C_CALENDAR_EVENT_CAREER ON c_calendar_event (career_id)');
        }

        if (!$schema->getTable('c_calendar_event')->hasColumn('promotion_id')) {
            $this->addSql('ALTER TABLE c_calendar_event ADD promotion_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_calendar_event ADD CONSTRAINT FK_C_CALENDAR_EVENT_PROMOTION FOREIGN KEY (promotion_id) REFERENCES promotion (id)');
            $this->addSql('CREATE INDEX IDX_C_CALENDAR_EVENT_PROMOTION ON c_calendar_event (promotion_id)');
        }
    }

    private function getConfigurationSelectedValue(): string
    {
        global $_configuration;
        $updateRootPath = $this->getUpdateRootPath();
        $oldConfigPath = $updateRootPath.'/app/config/configuration.php';
        if (!\in_array($oldConfigPath, get_included_files(), true)) {
            include_once $oldConfigPath;
        }

        $value = $_configuration['allow_careers_in_global_agenda'] ?? false;
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable = 'allow_careers_in_global_agenda'");

        if ($schema->getTable('c_calendar_event')->hasColumn('career_id')) {
            $this->addSql('ALTER TABLE c_calendar_event DROP FOREIGN KEY FK_C_CALENDAR_EVENT_CAREER');
            $this->addSql('DROP INDEX IDX_C_CALENDAR_EVENT_CAREER ON c_calendar_event');
            $this->addSql('ALTER TABLE c_calendar_event DROP COLUMN career_id');
        }

        if ($schema->getTable('c_calendar_event')->hasColumn('promotion_id')) {
            $this->addSql('ALTER TABLE c_calendar_event DROP FOREIGN KEY FK_C_CALENDAR_EVENT_PROMOTION');
            $this->addSql('DROP INDEX IDX_C_CALENDAR_EVENT_PROMOTION ON c_calendar_event');
            $this->addSql('ALTER TABLE c_calendar_event DROP COLUMN promotion_id');
        }
    }
}
