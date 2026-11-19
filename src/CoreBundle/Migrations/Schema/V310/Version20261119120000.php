<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register the legacy portfolio sorting preference on upgraded installations.
 * Do not alter other settings or overwrite a previously configured value.
 */
final class Version20261119120000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'portfolio_order_post_by_alphabetical_order';
    private const string CATEGORY = 'platform';

    public function getDescription(): string
    {
        return 'Restore the portfolio alphabetical ordering setting, disabled by default.';
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
            throw new RuntimeException('Portfolio ordering setting metadata is missing.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );
        if ($existing > 1) {
            throw new RuntimeException('Duplicate main URL portfolio ordering settings found.');
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
            [self::VARIABLE, self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], 'false'],
        );
    }

    public function down(Schema $schema): void
    {
        // Keep an administrator's selected value on rollback.
    }
}
