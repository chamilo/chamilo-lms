<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160603113100
 * Add association mapping for Language class.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160603113100 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE language MODIFY id INT');
        $this->addSql('ALTER TABLE language MODIFY parent_id INT');
        $this->addSql('ALTER TABLE language ADD CONSTRAINT language_parent FOREIGN KEY (parent_id) REFERENCES language (id)');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $languageTable = $schema->getTable('language');
        $languageTable->removeForeignKey('language_parent');
        $languageTable
            ->getColumn('parent_id')
            ->setType(Type::getType(Type::BOOLEAN))
            ->setNotnull(false);
    }
}
