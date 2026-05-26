<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20260302200700 extends AbstractMigrationChamilo
{
    /**
     * Known tabs for legacy list semantics conversion.
     * Legacy arrays are treated as "full replacement": everything known is disabled unless listed.
     */
    private static array $knownMenuTabs = [
        'campus_homepage',
        'my_courses',
        'reporting',
        'platform_administration',
        'my_agenda',
        'social',
        'videoconference',
        'diagnostics',
        'catalogue',
        'session_admin',
        'search',
        'question_manager',
    ];

    private static array $knownTopbarTabs = [
        'topbar_certificate',
        'topbar_my_certificates',
        'topbar_my_custom_certificate',
        'topbar_skills',
    ];

    public function getDescription(): string
    {
        return 'Migrate display show_tabs/show_tabs_per_role topbar keys (3 entries), convert per-role legacy arrays to the new object structure, tolerate trailing commas in JSON, update JSON templates, and remove legacy certificates.hide_my_certificate_link.';
    }

    public function up(Schema $schema): void
    {
        $this->removeLegacyMyCertificateSetting();

        // Settings values
        $this->migrateDisplaySettingJson('show_tabs', true);
        $this->migrateDisplaySettingJson('show_tabs_per_role', true);

        // Templates (json_example)
        $this->migrateTemplateJsonExample('show_tabs', true);
        $this->migrateTemplateJsonExample('show_tabs_per_role', true);
    }

    public function down(Schema $schema): void
    {
        $this->migrateDisplaySettingJson('show_tabs', false);
        $this->migrateDisplaySettingJson('show_tabs_per_role', false);

        $this->migrateTemplateJsonExample('show_tabs', false);
        $this->migrateTemplateJsonExample('show_tabs_per_role', false);

        $this->write('Rollback completed (legacy certificates.hide_my_certificate_link not recreated).');
    }

    private function removeLegacyMyCertificateSetting(): void
    {
        $this->addSql("
            DELETE FROM settings
             WHERE category = 'certificates'
               AND variable = 'hide_my_certificate_link'
               AND subkey IS NULL
        ");
        $this->write('Removed legacy setting: certificates.hide_my_certificate_link.');
    }

    private function migrateDisplaySettingJson(string $variable, bool $forward): void
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, access_url, selected_value
               FROM settings
              WHERE category = 'display'
                AND variable = :var
                AND subkey IS NULL",
            ['var' => $variable]
        );

        if (!\is_array($rows) || [] === $rows) {
            $this->write(\sprintf('No rows found for display.%s.', $variable));

            return;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $accessUrl = (int) ($row['access_url'] ?? 0);
            $raw = $row['selected_value'] ?? null;

            if ($id <= 0 || !\is_string($raw) || '' === trim($raw)) {
                continue;
            }

            $data = $this->decodeJsonLoose($raw);
            if (!\is_array($data)) {
                $this->write(\sprintf('Skipping display.%s (id=%d, url=%d): invalid JSON.', $variable, $id, $accessUrl));

                continue;
            }

            $updated = $this->migrateShowTabsData($data, $forward);
            $json = json_encode($updated, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (!\is_string($json) || '' === $json) {
                $this->write(\sprintf('Skipping display.%s (id=%d): failed to encode JSON.', $variable, $id));

                continue;
            }

            $this->connection->executeStatement(
                'UPDATE settings SET selected_value = :val WHERE id = :id',
                ['val' => $json, 'id' => $id]
            );

            $this->write(\sprintf('Updated display.%s (id=%d, url=%d).', $variable, $id, $accessUrl));
        }
    }

    private function migrateTemplateJsonExample(string $variable, bool $forward): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, json_example
               FROM settings_value_template
              WHERE variable = :var',
            ['var' => $variable]
        );

        if (!\is_array($row) || empty($row['id'])) {
            $this->write(\sprintf('No template found for %s (skipping).', $variable));

            return;
        }

        $id = (int) $row['id'];
        $raw = $row['json_example'] ?? null;

        if (!\is_string($raw) || '' === trim($raw)) {
            $this->write(\sprintf('Template %s has empty json_example (skipping).', $variable));

            return;
        }

        $data = $this->decodeJsonLoose($raw);
        if (!\is_array($data)) {
            $this->write(\sprintf('Template %s has invalid JSON (skipping).', $variable));

            return;
        }

        $updated = $this->migrateShowTabsData($data, $forward);

        $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!\is_string($json) || '' === $json) {
            $this->write(\sprintf('Failed to encode template json_example for %s.', $variable));

            return;
        }

        $this->connection->executeStatement(
            'UPDATE settings_value_template SET json_example = :val WHERE id = :id',
            ['val' => $json, 'id' => $id]
        );

        $this->write(\sprintf('Updated settings_value_template.json_example for %s (id=%d).', $variable, $id));
    }

    /**
     * Supported formats:
     * - {"menu":{...},"topbar":{...}}
     * - {"ROLE": {"menu":{...},"topbar":{...}}, ...}
     * - {"ROLE": ["tab1","tab2"], ...}  (legacy list -> converted to object form)
     */
    private function migrateShowTabsData(array $data, bool $forward): array
    {
        // Format A (single config)
        if (isset($data['menu']) || isset($data['topbar'])) {
            return $this->migrateConfigNode($data, $forward);
        }

        // Per-role map
        foreach ($data as $role => $roleConfig) {
            if (!\is_array($roleConfig)) {
                continue;
            }

            // Legacy list: {"ROLE": ["session_admin", ...]}
            if ($this->isLegacyList($roleConfig)) {
                $roleConfig = $this->configFromLegacyList($roleConfig);
                $data[$role] = $this->migrateConfigNode($roleConfig, $forward);

                continue;
            }

            // Object config: {"ROLE": {"menu": {...}, "topbar": {...}}}
            if (isset($roleConfig['menu']) || isset($roleConfig['topbar'])) {
                $data[$role] = $this->migrateConfigNode($roleConfig, $forward);
            }
        }

        return $data;
    }

    private function isLegacyList(array $value): bool
    {
        return array_is_list($value);
    }

    private function configFromLegacyList(array $list): array
    {
        $menu = [];
        $topbar = [];

        foreach (self::$knownMenuTabs as $k) {
            $menu[$k] = false;
        }
        foreach (self::$knownTopbarTabs as $k) {
            $topbar[$k] = false;
        }

        foreach ($list as $key) {
            if (!\is_string($key)) {
                continue;
            }

            if (\in_array($key, self::$knownMenuTabs, true)) {
                $menu[$key] = true;

                continue;
            }

            // Legacy alias: enable both new entries when legacy key is present.
            if ('topbar_certificate' === $key) {
                $topbar['topbar_certificate'] = true;
                $topbar['topbar_my_certificates'] = true;
                $topbar['topbar_my_custom_certificate'] = true;

                continue;
            }

            if (\in_array($key, self::$knownTopbarTabs, true)) {
                $topbar[$key] = true;
            }
        }

        // Ensure alias consistency if only new keys were set.
        if (!isset($topbar['topbar_certificate'])) {
            $topbar['topbar_certificate'] = (true === $topbar['topbar_my_certificates'] || true === $topbar['topbar_my_custom_certificate']);
        }

        return [
            'menu' => $menu,
            'topbar' => $topbar,
        ];
    }

    private function migrateConfigNode(array $config, bool $forward): array
    {
        // Preserve menu exactly as it is.
        // Only migrate topbar keys.
        $topbar = [];
        if (isset($config['topbar']) && \is_array($config['topbar'])) {
            $topbar = $config['topbar'];
        }

        if ($forward) {
            // Forward: topbar_certificate -> topbar_my_certificates + topbar_my_custom_certificate
            // Do not overwrite already-present new keys.
            if (\array_key_exists('topbar_certificate', $topbar)) {
                $enabled = (true === $topbar['topbar_certificate']);

                if (!\array_key_exists('topbar_my_certificates', $topbar)) {
                    $topbar['topbar_my_certificates'] = $enabled;
                }
                if (!\array_key_exists('topbar_my_custom_certificate', $topbar)) {
                    $topbar['topbar_my_custom_certificate'] = $enabled;
                }

                unset($topbar['topbar_certificate']);
            }
        } else {
            // Rollback: rebuild old key, but only if it does not exist.
            if (!\array_key_exists('topbar_certificate', $topbar)) {
                $myCertificates = \array_key_exists('topbar_my_certificates', $topbar) && true === $topbar['topbar_my_certificates'];
                $myCustom = \array_key_exists('topbar_my_custom_certificate', $topbar) && true === $topbar['topbar_my_custom_certificate'];

                $topbar['topbar_certificate'] = ($myCertificates || $myCustom);

                unset($topbar['topbar_my_certificates'], $topbar['topbar_my_custom_certificate']);
            }
        }

        $config['topbar'] = $topbar;

        return $config;
    }

    /**
     * Decode JSON in a tolerant way:
     * - First try regular json_decode()
     * - If it fails, try to remove trailing commas like: { "a": 1, } or [1,2,]
     */
    private function decodeJsonLoose(string $raw): ?array
    {
        $data = json_decode($raw, true);
        if (\is_array($data)) {
            return $data;
        }

        $fixed = preg_replace('/,\s*([}\]])/', '$1', $raw);
        if (\is_string($fixed) && $fixed !== $raw) {
            $data = json_decode($fixed, true);
            if (\is_array($data)) {
                return $data;
            }
        }

        return null;
    }
}
