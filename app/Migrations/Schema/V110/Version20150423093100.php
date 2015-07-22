<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150423093100
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20150423093100 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Sequence changes
        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_rule (
            id int unsigned not null auto_increment,
            description TEXT default '',
            PRIMARY KEY (id)
        )");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_condition (
            id int unsigned not null auto_increment,
            description TEXT default '',
            mat_op char(2) not null,
            param float not null,
            act_true int unsigned,
            act_false int unsigned,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_rule_condition (
            id int unsigned not null auto_increment,
            sequence_rule_id int unsigned not null,
            sequence_condition_id int unsigned not null,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_method (
            id int unsigned not null auto_increment,
            description TEXT default '',
            formula TEXT default '',
            assign int unsigned not null,
            met_type varchar(50) default '',
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_rule_method (
            id int unsigned not null auto_increment,
            sequence_rule_id int unsigned not null,
            sequence_method_id int unsigned not null,
            method_order int unsigned not null,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_variable (
            id int unsigned not null auto_increment,
            description TEXT default '',
            name varchar(50),
            default_val varchar(50) default '',
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_formula (
            id int unsigned not null auto_increment,
            sequence_method_id int unsigned not null,
            sequence_variable_id int unsigned not null,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_valid (
            id int unsigned not null auto_increment,
            sequence_variable_id int unsigned not null,
            sequence_condition_id int unsigned not null,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_type_entity (
            id int unsigned not null auto_increment,
            name varchar(50) not null default '',
            description TEXT default '',
            ent_table varchar(50) not null,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_row_entity (
            id int unsigned not null auto_increment,
            sequence_type_entity_id int unsigned not null,
            c_id  int unsigned not null,
            session_id int unsigned not null default 0,
            row_id int unsigned not null,
            name varchar(200) not null default '',
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence (
            id int unsigned not null auto_increment,
            sequence_row_entity_id int unsigned not null,
            sequence_row_entity_id_next int unsigned not null,
            is_part tinyint unsigned not null default 0,
            PRIMARY KEY (id)
        );");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS sequence_value (
            id int unsigned not null auto_increment,
            user_id int unsigned not null,
            sequence_row_entity_id int unsigned not null,
            advance float not null default 0.0,
            complete_items int not null default 0,
            total_items int not null default 1,
            success tinyint not null default 0,
            success_date datetime not null,
            available tinyint not null default 0,
            available_start_date datetime not null,
            available_end_date datetime not null,
            PRIMARY KEY (id)
        );");
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
