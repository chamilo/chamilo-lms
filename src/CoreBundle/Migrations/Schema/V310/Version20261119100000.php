<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * The platform setting that lets teachers keep a test closed until its access conditions are met
 * (minimum time in the course, required profile field). It is disabled by default, but enabled
 * when tests already hold such conditions, so they keep applying.
 */
final class Version20261119100000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'allow_final_exam_access_rule';
    private const string CATEGORY = 'exercise';
    private const string RULE_FIELD_VARIABLE = 'final_exam_access_rule';

    public function getDescription(): string
    {
        return 'Add the setting for the access conditions before a test, enabled only when tests already hold some.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $metadata = null;
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()[self::CATEGORY] ?? [] as $candidate) {
            if (self::VARIABLE === ($candidate['name'] ?? null)) {
                $metadata = $candidate;

                break;
            }
        }
        if (null === $metadata) {
            throw new RuntimeException('Metadata of the access conditions setting is missing.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );
        if ($existing > 1) {
            throw new RuntimeException('Duplicate global access conditions settings found.');
        }

        if (1 === $existing) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                [self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], self::VARIABLE],
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked)'
            .' VALUES (?, ?, ?, ?, ?, 1, 0, 0)',
            [
                self::VARIABLE,
                self::CATEGORY,
                (string) $metadata['title'],
                (string) $metadata['comment'],
                $this->hasConfiguredRules($schema) ? 'true' : 'false',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        // Keep the administrator's choice on rollback.
    }

    private function hasConfiguredRules(Schema $schema): bool
    {
        if (!$schema->hasTable('extra_field') || !$schema->hasTable('extra_field_values')) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM extra_field_values v INNER JOIN extra_field f ON f.id = v.field_id'
            ." WHERE f.item_type = ? AND f.variable = ? AND TRIM(COALESCE(v.field_value, '')) <> ''",
            [ExtraField::EXERCISE_FIELD_TYPE, self::RULE_FIELD_VARIABLE],
        ) > 0;
    }
}
