<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\v2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Class NotebookBundle
 * @package Chamilo\CoreBundle\Migrations\Schema\v2
 */
class NotebookBundle implements Migration
{
    /**
     * @inheritdoc
     **/
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery("CREATE TABLE c_notebook (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, resource_node_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $queries->addQuery("CREATE TABLE c_notebook_audit (id INT NOT NULL, rev INT NOT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, resource_node_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $queries->addQuery('CREATE TABLE c_item_visibility (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, visibility TINYINT(1) NOT NULL, start_visible DATETIME DEFAULT NULL, end_visible DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $queries->addQuery('CREATE TABLE c_item (id INT AUTO_INCREMENT NOT NULL, to_user_id INT DEFAULT NULL, tool VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, ref INT NOT NULL, user_id INT NOT NULL, INDEX IDX_BBBE7E4F29F6EE60 (to_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $queries->addQuery('ALTER TABLE c_item ADD CONSTRAINT FK_BBBE7E4F29F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        //$queries->addQuery('DROP TABLE oro_migrations');
        //$queries->addQuery('DROP TABLE oro_migrations_data');
        $queries->addQuery('DROP TABLE sylius_settings_parameter');
        $queries->addQuery('ALTER TABLE media__media CHANGE content_type content_type VARCHAR(255) DEFAULT NULL');
        $queries->addQuery('ALTER TABLE media__media_audit CHANGE content_type content_type VARCHAR(255) DEFAULT NULL');
        $queries->addQuery('ALTER TABLE settings_current DROP namespace, DROP name');
        /*$queries->addQuery('DROP INDEX idx_item_property_toolref ON c_item_property');
        $queries->addQuery('DROP INDEX idx_item_property_tooliuid ON c_item_property');
        $queries->addQuery('ALTER TABLE c_item_property DROP PRIMARY KEY');
        $queries->addQuery('ALTER TABLE c_item_property DROP iid, DROP tool, DROP visibility, DROP start_visible, DROP end_visible, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE ref item_id INT NOT NULL');
        $queries->addQuery('ALTER TABLE c_item_property ADD PRIMARY KEY (id)');*/
    }

    public function down(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery("DROP TABLE c_notebook");
        $queries->addQuery("DROP TABLE c_notebook_audit");

        /*$this->addSql('CREATE TABLE oro_migrations (id INT AUTO_INCREMENT NOT NULL, bundle VARCHAR(250) NOT NULL, version VARCHAR(250) NOT NULL, loaded_at DATETIME NOT NULL, INDEX idx_oro_migrations (bundle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oro_migrations_data (id INT AUTO_INCREMENT NOT NULL, class_name VARCHAR(255) NOT NULL, loaded_at DATETIME NOT NULL, version VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_settings_parameter (id INT AUTO_INCREMENT NOT NULL, namespace VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE c_item_visibility');
        $this->addSql('DROP TABLE c_item');
        $this->addSql('ALTER TABLE c_item_property DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_item_property ADD iid INT AUTO_INCREMENT NOT NULL, ADD tool VARCHAR(100) NOT NULL, ADD visibility TINYINT(1) NOT NULL, ADD start_visible DATETIME DEFAULT NULL, ADD end_visible DATETIME DEFAULT NULL, CHANGE id id INT NOT NULL, CHANGE item_id ref INT NOT NULL');
        $this->addSql('CREATE INDEX idx_item_property_toolref ON c_item_property (tool, ref)');
        $this->addSql('CREATE INDEX idx_item_property_tooliuid ON c_item_property (tool, insert_user_id)');
        $this->addSql('ALTER TABLE c_item_property ADD PRIMARY KEY (iid)');
        $this->addSql('ALTER TABLE c_notebook DROP FOREIGN KEY FK_E7EE1CE0A76ED395');
        $this->addSql('ALTER TABLE c_notebook DROP FOREIGN KEY FK_E7EE1CE091D79BD3');
        $this->addSql('DROP INDEX IDX_E7EE1CE0A76ED395 ON c_notebook');
        $this->addSql('DROP INDEX IDX_E7EE1CE091D79BD3 ON c_notebook');
        $this->addSql('ALTER TABLE c_notebook DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_notebook ADD notebook_id INT NOT NULL, ADD course VARCHAR(40) NOT NULL, CHANGE id iid INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE c_notebook ADD PRIMARY KEY (iid)');
        $this->addSql('ALTER TABLE c_notebook_audit DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_notebook_audit ADD notebook_id INT DEFAULT NULL, ADD course VARCHAR(40) DEFAULT NULL, CHANGE id iid INT NOT NULL');
        $this->addSql('ALTER TABLE c_notebook_audit ADD PRIMARY KEY (iid, rev)');
        $this->addSql('ALTER TABLE media__media CHANGE content_type content_type VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE media__media_audit CHANGE content_type content_type VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE settings_current ADD namespace VARCHAR(255) NOT NULL, ADD name VARCHAR(255) NOT NULL');*/
    }
}
