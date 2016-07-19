<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

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
        if (!$schema->hasTable('sequence_rule')) {
            $sequenceRule = $schema->createTable('sequence_rule');
            $sequenceRule->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceRule->addColumn('text', Type::TEXT);
            $sequenceRule->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_condition')) {
            $sequenceCondition = $schema->createTable('sequence_condition');
            $sequenceCondition->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceCondition->addColumn('description', Type::TEXT);
            $sequenceCondition->addColumn('mat_op', Type::INTEGER);
            $sequenceCondition->addColumn('param', Type::FLOAT);
            $sequenceCondition->addColumn('act_true', Type::INTEGER);
            $sequenceCondition->addColumn('act_false', Type::INTEGER);
            $sequenceCondition->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_rule_condition')) {
            $sequenceRuleCondition = $schema->createTable('sequence_rule_condition');
            $sequenceRuleCondition->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceRuleCondition->addColumn('sequence_rule_id', Type::INTEGER)->setNotnull(false);
            $sequenceRuleCondition->addColumn('sequence_condition_id', Type::INTEGER)->setNotnull(false);
            $sequenceRuleCondition->setPrimaryKey(['id']);
            $sequenceRuleCondition->addIndex(['sequence_rule_id']);
            $sequenceRuleCondition->addIndex(['sequence_condition_id']);
            $sequenceRuleCondition->addForeignKeyConstraint('sequence_condition', ['sequence_condition_id'], ['id']);
            $sequenceRuleCondition->addForeignKeyConstraint('sequence_rule', ['sequence_rule_id'], ['id']);
        }

        if (!$schema->hasTable('sequence_method')) {
            $sequenceMethod = $schema->createTable('sequence_method');
            $sequenceMethod->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceMethod->addColumn('description', Type::TEXT);
            $sequenceMethod->addColumn('formula', Type::TEXT);
            $sequenceMethod->addColumn('assign', Type::INTEGER);
            $sequenceMethod->addColumn('met_type', Type::INTEGER);
            $sequenceMethod->addColumn('act_false', Type::INTEGER);
            $sequenceMethod->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_rule_method')) {
            $sequenceRuleMethod = $schema->createTable('sequence_rule_method');
            $sequenceRuleMethod->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceRuleMethod->addColumn('sequence_rule_id', Type::INTEGER)->setNotnull(false);
            $sequenceRuleMethod->addColumn('sequence_method_id', Type::INTEGER)->setNotnull(false);
            $sequenceRuleMethod->addColumn('method_order', Type::INTEGER);
            $sequenceRuleMethod->setPrimaryKey(['id']);
            $sequenceRuleMethod->addIndex(['sequence_rule_id']);
            $sequenceRuleMethod->addIndex(['sequence_method_id']);
            $sequenceRuleMethod->addForeignKeyConstraint('sequence_method', ['sequence_method_id'], ['id']);
            $sequenceRuleMethod->addForeignKeyConstraint('sequence_rule', ['sequence_rule_id'], ['id']);
        }

        if (!$schema->hasTable('sequence_variable')) {
            $sequenceVariable = $schema->createTable('sequence_variable');
            $sequenceVariable->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceVariable->addColumn('name', Type::STRING)->setLength(255)->setNotnull(false);
            $sequenceVariable->addColumn('description', Type::TEXT)->setNotnull(false);
            $sequenceVariable->addColumn('default_val', Type::STRING)->setLength(255)->setNotnull(false);
            $sequenceVariable->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_formula')) {
            $sequenceFormula = $schema->createTable('sequence_formula');
            $sequenceFormula->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceFormula->addColumn('sequence_method_id', Type::INTEGER)->setNotnull(false);
            $sequenceFormula->addColumn('sequence_variable_id', Type::INTEGER)->setNotnull(false);
            $sequenceFormula->setPrimaryKey(['id']);
            $sequenceFormula->addIndex(['sequence_method_id']);
            $sequenceFormula->addIndex(['sequence_variable_id']);
            $sequenceFormula->addForeignKeyConstraint('sequence_variable', ['sequence_variable_id'], ['id']);
            $sequenceFormula->addForeignKeyConstraint('sequence_method', ['sequence_method_id'], ['id']);
        }

        if (!$schema->hasTable('sequence_valid')) {
            $sequenceValid = $schema->createTable('sequence_valid');
            $sequenceValid->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceValid->addColumn('sequence_variable_id', Type::INTEGER)->setNotnull(false);
            $sequenceValid->addColumn('sequence_condition_id', Type::INTEGER)->setNotnull(false);
            $sequenceValid->setPrimaryKey(['id']);
            $sequenceValid->addIndex(['sequence_variable_id']);
            $sequenceValid->addIndex(['sequence_condition_id']);
            $sequenceValid->addForeignKeyConstraint('sequence_condition', ['sequence_condition_id'], ['id']);
            $sequenceValid->addForeignKeyConstraint('sequence_variable', ['sequence_variable_id'], ['id']);
        }

        if (!$schema->hasTable('sequence_type_entity')) {
            $sequenceTypeEntity = $schema->createTable('sequence_type_entity');
            $sequenceTypeEntity->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceTypeEntity->addColumn('name', Type::STRING)->setLength(255);
            $sequenceTypeEntity->addColumn('description', Type::TEXT);
            $sequenceTypeEntity->addColumn('ent_table', Type::STRING)->setLength(255);
            $sequenceTypeEntity->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_row_entity')) {
            $sequenceRowEntity = $schema->createTable('sequence_row_entity');
            $sequenceRowEntity->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceRowEntity->addColumn('sequence_type_entity_id', Type::INTEGER)->setNotnull(false);
            $sequenceRowEntity->addColumn('c_id', Type::INTEGER);
            $sequenceRowEntity->addColumn('session_id', Type::INTEGER);
            $sequenceRowEntity->addColumn('row_id', Type::INTEGER);
            $sequenceRowEntity->addColumn('name', Type::STRING)->setLength(255);
            $sequenceRowEntity->setPrimaryKey(['id']);
            $sequenceRowEntity->addIndex(['sequence_type_entity_id']);
            $sequenceRowEntity->addForeignKeyConstraint('sequence_type_entity', ['sequence_type_entity_id'], ['id']);
        }

        if (!$schema->hasTable('sequence')) {
            $sequence = $schema->createTable('sequence');
            $sequence->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequence->addColumn('name', Type::STRING)->setLength(255);
            $sequence->addColumn('graph', Type::TEXT)->setNotnull(false);
            $sequence->addColumn('created_at', Type::DATETIME);
            $sequence->addColumn('updated_at', Type::DATETIME);
            $sequence->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('sequence_value')) {
            $sequenceValue = $schema->createTable('sequence_value');
            $sequenceValue->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceValue->addColumn('sequence_row_entity_id', Type::INTEGER)->setNotnull(false);
            $sequenceValue->addColumn('user_id', Type::INTEGER);
            $sequenceValue->addColumn('advance', Type::FLOAT);
            $sequenceValue->addColumn('complete_items', Type::INTEGER);
            $sequenceValue->addColumn('total_items', Type::INTEGER);
            $sequenceValue->addColumn('success', Type::BOOLEAN);
            $sequenceValue->addColumn('success_date', Type::DATETIME)->setNotnull(false);
            $sequenceValue->addColumn('available', Type::BOOLEAN);
            $sequenceValue->addColumn('available_start_date', Type::DATETIME)->setNotnull(false);
            $sequenceValue->addColumn('available_end_date', Type::DATETIME)->setNotnull(false);
            $sequenceValue->setPrimaryKey(['id']);
            $sequenceValue->addIndex(['sequence_row_entity_id']);
            $sequenceValue->addForeignKeyConstraint('sequence_row_entity', ['sequence_row_entity_id'], ['id']);
        }

        if (!$schema->hasTable('sequence_resource')) {
            $sequenceResource = $schema->createTable('sequence_resource');
            $sequenceResource->addColumn('id', Type::INTEGER)->setAutoincrement(true);
            $sequenceResource->addColumn('sequence_id', Type::INTEGER)->setNotnull(false);
            $sequenceResource->addColumn('type', Type::INTEGER);
            $sequenceResource->addColumn('resource_id', Type::INTEGER);
            $sequenceResource->setPrimaryKey(['id']);
            $sequenceResource->addIndex(['sequence_id']);
            $sequenceResource->addForeignKeyConstraint('sequence', ['sequence_id'], ['id']);
        }
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
