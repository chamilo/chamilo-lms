<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20261009010000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Document the Grok text timeout in the ai_providers JSON value template.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings_value_template')) {
            $this->write('Skipped ai_providers template sync: settings_value_template does not exist.');

            return;
        }

        $templates = SettingsValueTemplateFixtures::getTemplatesGrouped()['aihelpers'] ?? [];
        $template = null;

        foreach ($templates as $candidate) {
            if ('ai_providers' === ($candidate['variable'] ?? null)) {
                $template = $candidate;

                break;
            }
        }

        if (null === $template) {
            throw new RuntimeException('The ai_providers value template is missing from SettingsValueTemplateFixtures.');
        }

        $jsonExample = json_encode(
            $template['json_example'] ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if (false === $jsonExample) {
            throw new RuntimeException('Could not encode the ai_providers value template.');
        }

        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            ['ai_providers'],
        );

        if ($templateId) {
            $this->connection->executeStatement(
                'UPDATE settings_value_template SET json_example = ?, updated_at = NOW() WHERE id = ?',
                [$jsonExample, (int) $templateId],
            );
            $this->write('Updated ai_providers JSON value template.');
        } else {
            $this->connection->executeStatement(
                'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                ['ai_providers', $jsonExample],
            );
            $templateId = (int) $this->connection->lastInsertId();
            $this->write('Inserted ai_providers JSON value template.');
        }

        if ($schema->hasTable('settings') && $schema->getTable('settings')->hasColumn('value_template_id')) {
            $linkedRows = $this->connection->executeStatement(
                'UPDATE settings SET value_template_id = ? WHERE variable = ? AND subkey IS NULL AND access_url = 1',
                [(int) $templateId, 'ai_providers'],
            );
            $this->write(\sprintf('Linked %d ai_providers setting row(s) to the JSON value template.', $linkedRows));
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('ai_providers template documentation update is intentionally not reverted.');
    }
}
