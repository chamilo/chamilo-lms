<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Remove the obsolete document glossary setting while retaining the new selector.
 */
final class Version20261025194500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove the obsolete document glossary setting and add document options to the glossary selector.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings_options WHERE variable = 'show_glossary_in_documents'");
        $this->addSql("DELETE FROM settings WHERE variable = 'show_glossary_in_documents'");

        $this->addSql(
            'UPDATE settings SET comment = ? WHERE variable = ? AND category = ?',
            [
                'Select where glossary terms are highlighted automatically: documents, exercises and learning paths.',
                'show_glossary_in_extra_tools',
                'glossary',
            ]
        );

        $variable = 'show_glossary_in_extra_tools';
        $options = [
            'none' => 'None',
            'exercise' => 'Exercise',
            'lp' => 'LearningPath',
            'exercise_and_lp' => 'ExerciseAndLearningPath',
            'document' => 'Documents',
            'document_and_exercise' => 'Documents and exercise',
            'document_and_lp' => 'Documents and learning path',
            'document_and_exercise_and_lp' => 'Documents, exercise and learning path',
        ];

        foreach ($options as $value => $label) {
            $this->addSql(
                'INSERT INTO settings_options (variable, value, display_text) '
                .'SELECT ?, ?, ? WHERE NOT EXISTS ('
                .'SELECT 1 FROM settings_options WHERE variable = ? AND value = ?)',
                [$variable, $value, $label, $variable, $value]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // The retired setting cannot be restored without its original per-URL values.
    }
}
