<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251229165400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add new AI helper settings: glossary terms generator, video generator, and course analyser.';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'variable' => 'glossary_terms_generator',
                'selected_value' => 'false',
                'title' => 'Glossary terms generator',
                'comment' => 'Allow teachers to ask for AI-generated glossary terms in their course. This will generate 20 terms based on the course title and the general description in the course description tool. If used more than once, it will exclude terms already present in that glossary (make sure content can be shared with the configured AI services).',
                'category' => 'ai_helpers',
            ],
            [
                'variable' => 'video_generator',
                'selected_value' => 'false',
                'title' => 'Video generator',
                'comment' => 'Generates videos based on prompts or content using AI (this might consume many tokens).',
                'category' => 'ai_helpers',
            ],
            [
                'variable' => 'course_analyser',
                'selected_value' => 'false',
                'title' => 'Course analyser',
                'comment' => 'Analyses all resources in one or many courses and pre-trains the AI model to answer any question on this or these courses (make sure content can be shared with the configured AI services).',
                'category' => 'ai_helpers',
            ],
        ];

        foreach ($settings as $setting) {
            $sqlCheck = \sprintf(
                "SELECT COUNT(*) as count
                 FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                addslashes($setting['variable'])
            );

            $stmt = $this->connection->executeQuery($sqlCheck);
            $result = $stmt->fetchAssociative();
            $exists = $result && (int) ($result['count'] ?? 0) > 0;

            if ($exists) {
                $this->addSql(\sprintf(
                    "UPDATE settings
                     SET selected_value = '%s',
                         title = '%s',
                         comment = '%s',
                         category = '%s'
                     WHERE variable = '%s'
                       AND subkey IS NULL
                       AND access_url = 1",
                    addslashes($setting['selected_value']),
                    addslashes($setting['title']),
                    addslashes($setting['comment']),
                    addslashes($setting['category']),
                    addslashes($setting['variable'])
                ));
                $this->write(\sprintf('Updated setting: %s', $setting['variable']));
            } else {
                $this->addSql(\sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, NULL, '%s', '%s', '%s', '%s', 1, 0, 1)",
                    addslashes($setting['variable']),
                    addslashes($setting['category']),
                    addslashes($setting['selected_value']),
                    addslashes($setting['title']),
                    addslashes($setting['comment'])
                ));
                $this->write(\sprintf('Inserted setting: %s', $setting['variable']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $variables = [
            'glossary_terms_generator',
            'video_generator',
            'course_analyser',
        ];

        foreach ($variables as $variable) {
            $this->addSql(\sprintf(
                "DELETE FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                addslashes($variable)
            ));
            $this->write(\sprintf('Removed setting: %s', $variable));
        }
    }
}
