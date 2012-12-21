<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121220233847 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        /*$this->addSql('CREATE TABLE pages (
                    id INTEGER  NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255)  NOT NULL,
                    slug VARCHAR(255)  NOT NULL,
                    content TEXT  NOT NULL,
                    PRIMARY KEY (id)
        )');*/

        $table = $schema->createTable('pages');
        $table->addColumn('id', 'string');
        $table->addColumn('title', 'string');
        $table->addColumn('slug', 'string');
        $table->addColumn('content', 'text');
        $table->addColumn('created', 'datetime');
        $table->addColumn('updated', 'datetime');
        $table->setPrimaryKey('id');
    }

    public function down(Schema $schema)
    {
        //$this->addSql('DROP TABLE pages');
        $schema->dropTable('pages');
    }
}
