<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Registers the optional "send personal data to a URL" setting (off by default)
 * and a dedicated HMAC secret for signing those transfers.
 */
final class Version20261115184500 extends AbstractMigrationChamilo
{
    private const string ALLOW_VARIABLE = 'allow_personal_data_export_to_url';
    private const string SECRET_VARIABLE = 'personal_data_export_hmac_secret';
    private const string SOURCE_VARIABLE = 'disable_gdpr';

    public function getDescription(): string
    {
        return 'Register personal data export-to-URL settings and a dedicated HMAC secret.';
    }

    public function up(Schema $schema): void
    {
        $metadata = $this->privacyMetadata();
        $secret = bin2hex(random_bytes(32));

        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $this->insertSetting(
                $schema,
                $table,
                self::ALLOW_VARIABLE,
                $metadata[self::ALLOW_VARIABLE],
                'false',
                false
            );
            $this->insertSetting(
                $schema,
                $table,
                self::SECRET_VARIABLE,
                $metadata[self::SECRET_VARIABLE],
                $secret,
                true
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $this->addSql(
                "DELETE FROM $table WHERE variable IN (?, ?) AND access_url = 1 AND subkey IS NULL",
                [self::ALLOW_VARIABLE, self::SECRET_VARIABLE]
            );
        }
    }

    /**
     * @return array<string, array{title: string, comment: string}>
     */
    private function privacyMetadata(): array
    {
        $found = [];
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()['privacy'] ?? [] as $setting) {
            $name = $setting['name'] ?? null;
            if (self::ALLOW_VARIABLE === $name || self::SECRET_VARIABLE === $name) {
                $found[$name] = [
                    'title' => (string) $setting['title'],
                    'comment' => (string) $setting['comment'],
                ];
            }
        }

        if (!isset($found[self::ALLOW_VARIABLE], $found[self::SECRET_VARIABLE])) {
            throw new RuntimeException('Personal data export setting metadata is missing.');
        }

        return $found;
    }

    /**
     * @param array{title: string, comment: string} $metadata
     */
    private function insertSetting(
        Schema $schema,
        string $table,
        string $variable,
        array $metadata,
        string $value,
        bool $confidential,
    ): void {
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM $table WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
            [$variable],
        );
        if ($count > 1) {
            throw new RuntimeException(\sprintf('Duplicate setting "%s" for the main access URL.', $variable));
        }

        $hasConfidential = $schema->getTable($table)->hasColumn('confidential');

        if (1 === $count) {
            if ($hasConfidential && $confidential) {
                $this->addSql(
                    "UPDATE $table SET title = ?, comment = ?, category = 'privacy', confidential = 1 WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
                    [$metadata['title'], $metadata['comment'], $variable]
                );
            } else {
                $this->addSql(
                    "UPDATE $table SET title = ?, comment = ?, category = 'privacy' WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
                    [$metadata['title'], $metadata['comment'], $variable]
                );
            }

            return;
        }

        $sourceCount = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM $table WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
            [self::SOURCE_VARIABLE],
        );
        if (1 !== $sourceCount) {
            throw new RuntimeException('A single existing privacy setting is required to copy the settings row format.');
        }

        $confidentialSelect = $hasConfidential ? (', '.($confidential ? '1' : '0')) : '';
        $confidentialColumn = $hasConfidential ? ', confidential' : '';

        $this->addSql(
            "INSERT INTO $table (
                access_url, variable, subkey, type, category, selected_value,
                title, comment, scope, subkeytext, access_url_changeable,
                access_url_locked, value_template_id$confidentialColumn
            ) SELECT
                source.access_url, ?, source.subkey, source.type, 'privacy', ?,
                ?, ?, source.scope, source.subkeytext, 1, 0, NULL$confidentialSelect
            FROM $table source
            WHERE source.variable = ?
              AND source.access_url = 1 AND source.subkey IS NULL",
            [$variable, $value, $metadata['title'], $metadata['comment'], self::SOURCE_VARIABLE],
        );
    }
}
