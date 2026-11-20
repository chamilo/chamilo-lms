<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register direct-course student visibility without changing existing subscriptions.
 */
final class Version20261120120000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'teacher_defined_student_access_to_course';
    private const string CATEGORY = 'workflows';

    public function getDescription(): string
    {
        return 'Add optional teacher-managed direct course access for students (GH-3231).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_rel_user') || !$schema->hasTable('settings')) {
            throw new RuntimeException('Course subscriptions and platform settings tables are required.');
        }

        if (!$schema->getTable('course_rel_user')->hasColumn('visibility')) {
            $this->addSql('ALTER TABLE course_rel_user ADD visibility INT NOT NULL DEFAULT 1');
        }

        $metadata = null;
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()[self::CATEGORY] ?? [] as $setting) {
            if (self::VARIABLE === ($setting['name'] ?? null)) {
                $metadata = $setting;

                break;
            }
        }

        if (null === $metadata) {
            throw new RuntimeException('Course access setting metadata is missing from fixtures.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );

        if ($existing > 1) {
            throw new RuntimeException('Duplicate main URL course access settings found.');
        }

        if (1 === $existing) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ?, access_url_changeable = 0 WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                [self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], self::VARIABLE],
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked) VALUES (?, ?, ?, ?, ?, 1, 0, 0)',
            [self::VARIABLE, self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], 'false'],
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve course access state and administrator configuration on rollback.
    }
}
