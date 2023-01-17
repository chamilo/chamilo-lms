<?php

declare(strict_types = 1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230116184632 extends AbstractMigrationChamilo
{
    /**
     * Return desription of the migration step.
     */
    public function getDescription(): string
    {
        return 'Fix FK on c_quiz_question_rel_category';
    }

    /**
     * Process one step up in the migration
     */
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_quiz_question_rel_category');
        $cQuizQuestionTable = $schema->getTable('c_quiz_question');
        $cQuizQuestionCategoryTable = $schema->getTable('c_quiz_question_category');

        $fks = $table->getForeignKeys();

        if (!empty($fks)) {
            foreach ($fks as $fk) {
                $table->removeForeignKey($fk->getName());
            }
        }

        $table->addForeignKeyConstraint(
            $cQuizQuestionTable,
            ['question_id'],
            ['iid'],
            ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE'],
        );
        $table->addForeignKeyConstraint(
            $cQuizQuestionCategoryTable,
            ['category_id'],
            ['iid'],
            ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE'],
        );
    }
}
