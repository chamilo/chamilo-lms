<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo,
    Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Types\Type;

/**
 * Class Version20171127103000
 *
 * Adding cloud files' link document type and enabling/disabling option
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20171127103000 extends AbstractMigrationChamilo
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE c_document CHANGE filetype filetype SET('file','folder','link');");

        $this->addSql("INSERT INTO `settings_options` (`variable`, `value`, `display_text`) VALUES
        ('enable_add_file_link', 'true', 'Yes'),
        ('enable_add_file_link', 'false', 'No');");
        
        $this->addSql("INSERT INTO `settings_current` 
        (`variable`, `subkey`, `type`, `category`, `selected_value`, `title`, `comment`, `scope`, `subkeytext`, `access_url`, `access_url_changeable`, `access_url_locked`)
        VALUES ('enable_add_file_link', NULL, 'radio', 'Tools', 'false', 'enable_add_file_link_title', 'enable_add_file_link_comment', NULL, NULL, 1, 0, 0);");
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE c_document CHANGE filetype filetype SET('file','folder');");

        $this->addSql("DELETE FROM `settings_options` WHERE `variable` = 'enable_add_file_link'");
        $this->addSql("DELETE FROM `settings_current` WHERE `variable` = 'enable_add_file_link'");
    }
}
