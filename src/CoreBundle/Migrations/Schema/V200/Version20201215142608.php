<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201215142608 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and modify tables for peer assessment, autogroups, learning paths, group relations, and student publications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_quiz
            ADD IF NOT EXISTS display_chart_degree_certainty INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS send_email_chart_degree_certainty INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS not_display_balance_percentage_categorie_question INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS display_chart_degree_certainty_category INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS gather_questions_categories INT DEFAULT 0 NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_quiz
            DROP IF EXISTS display_chart_degree_certainty,
            DROP IF EXISTS send_email_chart_degree_certainty,
            DROP IF EXISTS not_display_balance_percentage_categorie_question,
            DROP IF EXISTS display_chart_degree_certainty_category,
            DROP IF EXISTS gather_questions_categories
        ');
    }
}
