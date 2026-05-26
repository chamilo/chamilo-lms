<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class Version20260118121300 extends AbstractMigrationChamilo
{
    private const DEBUG = true;

    /**
     * All known menu keys that should be configurable via display.show_tabs JSON.
     * NOTE: Add new keys here in the future without needing DB schema changes.
     */
    private const MENU_KEYS = [
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

    /**
     * All known topbar keys that should be configurable via display.show_tabs JSON.
     */
    private const TOPBAR_KEYS = [
        'topbar_certificate',
        'topbar_skills',
    ];

    public function getDescription(): string
    {
        return 'Migrate display.show_tabs from legacy CSV to JSON and link it to a SettingsValueTemplate example.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        // Update show_tabs metadata (title/comment/category).
        $affectedMeta = $conn->executeStatement(
            "UPDATE settings
                SET title = ?, comment = ?, category = 'display'
              WHERE variable = 'show_tabs'",
            [
                'Main menu entries',
                'Define menu/topbar entries visibility as JSON.',
            ]
        );

        $this->dbg(\sprintf('[META] Updated show_tabs metadata (rows=%d).', $affectedMeta));

        // Ensure SettingsValueTemplate exists/updated for show_tabs.
        $templateId = $this->ensureSettingsValueTemplateForShowTabs();

        // Link settings.value_template_id to the template (only show_tabs).
        if (null !== $templateId) {
            $affectedLink = $conn->executeStatement(
                "UPDATE settings
                    SET value_template_id = ?
                  WHERE variable = 'show_tabs'",
                [(int) $templateId]
            );

            $this->dbg(\sprintf('[LINK] Linked show_tabs to template_id=%d (rows=%d).', (int) $templateId, $affectedLink));
        }

        // Migrate selected_value from CSV to JSON for every access_url row.
        $rows = $conn->fetchAllAssociative(
            "SELECT id, access_url, selected_value
               FROM settings
              WHERE variable = 'show_tabs'"
        );

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $accessUrl = (int) $row['access_url'];
            $raw = (string) ($row['selected_value'] ?? '');
            $rawTrim = trim($raw);

            if ('' === $rawTrim) {
                // If empty, apply a safe default JSON.
                $json = $this->buildDefaultShowTabsJson();
                $this->updateShowTabsRow($id, $accessUrl, $json, 'empty');

                continue;
            }

            // If already JSON, normalize/complete missing keys safely.
            if ($this->looksLikeJson($rawTrim)) {
                $normalized = $this->normalizeExistingJson($rawTrim);
                if (null !== $normalized && $normalized !== $rawTrim) {
                    $this->updateShowTabsRow($id, $accessUrl, $normalized, 'normalize');
                } else {
                    $this->dbg(\sprintf('[SKIP] show_tabs id=%d access_url=%d already JSON (no changes).', $id, $accessUrl));
                }

                continue;
            }

            // Otherwise treat as legacy CSV list.
            $json = $this->convertLegacyCsvToJson($rawTrim);
            $this->updateShowTabsRow($id, $accessUrl, $json, 'csv->json');
        }
    }

    private function ensureSettingsValueTemplateForShowTabs(): ?int
    {
        $conn = $this->connection;

        // Adjust table name if your installation differs.
        $table = 'settings_value_template';

        $example = [
            'menu' => $this->buildDefaultMenuArray(),
            'topbar' => $this->buildDefaultTopbarArray(),
        ];

        $jsonExample = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!\is_string($jsonExample) || '' === $jsonExample) {
            $this->dbg('[WARN] Failed to encode JSON example for show_tabs.');
            $jsonExample = '{"menu":{},"topbar":{}}';
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $existingId = $conn->fetchOne(
            "SELECT id FROM {$table} WHERE variable = ? LIMIT 1",
            ['show_tabs']
        );

        if ($existingId) {
            $conn->executeStatement(
                "UPDATE {$table}
                    SET json_example = ?, updated_at = ?
                  WHERE id = ?",
                [$jsonExample, $now, (int) $existingId]
            );

            $this->dbg(\sprintf('[TPL] Updated template for show_tabs (id=%d).', (int) $existingId));

            return (int) $existingId;
        }

        $conn->executeStatement(
            "INSERT INTO {$table} (variable, json_example, created_at, updated_at)
             VALUES (?, ?, ?, ?)",
            ['show_tabs', $jsonExample, $now, $now]
        );

        $newId = $conn->fetchOne(
            "SELECT id FROM {$table} WHERE variable = ? LIMIT 1",
            ['show_tabs']
        );

        if (!$newId) {
            $this->dbg('[WARN] Could not retrieve inserted template id for show_tabs.');

            return null;
        }

        $this->dbg(\sprintf('[TPL] Inserted template for show_tabs (id=%d).', (int) $newId));

        return (int) $newId;
    }

    private function updateShowTabsRow(int $id, int $accessUrl, string $json, string $mode): void
    {
        $affected = $this->connection->executeStatement(
            "UPDATE settings
                SET selected_value = ?, category = 'display'
              WHERE id = ?",
            [$json, $id]
        );

        $this->dbg(\sprintf('[MIG] mode=%s id=%d access_url=%d updated=%d', $mode, $id, $accessUrl, $affected));
    }

    private function looksLikeJson(string $value): bool
    {
        $v = ltrim($value);

        return '' !== $v && ('{' === $v[0] || '[' === $v[0]);
    }

    private function normalizeExistingJson(string $rawJson): ?string
    {
        $decoded = json_decode($rawJson, true);

        if (!\is_array($decoded)) {
            // If JSON is invalid, fallback to default JSON to avoid breaking the UI.
            $this->dbg('[WARN] show_tabs JSON is invalid. Falling back to default JSON.');

            return $this->buildDefaultShowTabsJson();
        }

        $menu = (isset($decoded['menu']) && \is_array($decoded['menu'])) ? $decoded['menu'] : [];
        $topbar = (isset($decoded['topbar']) && \is_array($decoded['topbar'])) ? $decoded['topbar'] : [];

        // Ensure all known keys exist (missing -> keep safe defaults).
        $defaultMenu = $this->buildDefaultMenuArray();
        foreach (self::MENU_KEYS as $k) {
            if (!\array_key_exists($k, $menu)) {
                // Preserve behavior: default values apply when missing.
                $menu[$k] = $defaultMenu[$k] ?? false;
            }
        }

        $defaultTopbar = $this->buildDefaultTopbarArray();
        foreach (self::TOPBAR_KEYS as $k) {
            if (!\array_key_exists($k, $topbar)) {
                $topbar[$k] = $defaultTopbar[$k] ?? true;
            }
        }

        $normalized = [
            'menu' => $menu,
            'topbar' => $topbar,
        ];

        $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!\is_string($json) || '' === $json) {
            return $this->buildDefaultShowTabsJson();
        }

        return $json;
    }

    private function convertLegacyCsvToJson(string $csv): string
    {
        $enabled = array_values(array_filter(array_map('trim', explode(',', $csv)), static function ($v) {
            return '' !== $v;
        }));

        $defaultMenu = $this->buildDefaultMenuArray();
        $defaultTopbar = $this->buildDefaultTopbarArray();

        $menu = [];
        foreach (self::MENU_KEYS as $k) {
            if (\in_array($k, $enabled, true)) {
                $menu[$k] = true;

                continue;
            }

            // Preserve behavior for keys that were never configurable in legacy CSV:
            // If not present in CSV, use default (typically true for new entries).
            $menu[$k] = $defaultMenu[$k] ?? false;
        }

        $topbar = [];
        foreach (self::TOPBAR_KEYS as $k) {
            // Topbar was not part of the old CSV, so preserve behavior using defaults.
            $topbar[$k] = $defaultTopbar[$k] ?? true;
        }

        $data = [
            'menu' => $menu,
            'topbar' => $topbar,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!\is_string($json) || '' === $json) {
            return $this->buildDefaultShowTabsJson();
        }

        return $json;
    }

    private function buildDefaultShowTabsJson(): string
    {
        $data = [
            'menu' => $this->buildDefaultMenuArray(),
            'topbar' => $this->buildDefaultTopbarArray(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!\is_string($json) || '' === $json) {
            return '{"menu":{},"topbar":{}}';
        }

        return $json;
    }

    private function buildDefaultMenuArray(): array
    {
        // Preserve current platform behavior as much as possible:
        // - videoconference + diagnostics disabled by default
        // - search + question_manager enabled by default (they currently appear when available)
        $defaults = [
            'campus_homepage' => true,
            'my_courses' => true,
            'reporting' => true,
            'platform_administration' => true,
            'my_agenda' => true,
            'social' => true,
            'videoconference' => false,
            'diagnostics' => false,
            'catalogue' => true,
            'session_admin' => true,
            'search' => true,
            'question_manager' => true,
        ];

        // Ensure all keys exist.
        foreach (self::MENU_KEYS as $k) {
            if (!\array_key_exists($k, $defaults)) {
                $defaults[$k] = false;
            }
        }

        return $defaults;
    }

    private function buildDefaultTopbarArray(): array
    {
        // Preserve current behavior: keep these visible unless admin disables them.
        $defaults = [
            'topbar_certificate' => true,
            'topbar_skills' => true,
        ];

        foreach (self::TOPBAR_KEYS as $k) {
            if (!\array_key_exists($k, $defaults)) {
                $defaults[$k] = true;
            }
        }

        return $defaults;
    }

    private function dbg(string $msg): void
    {
        if (self::DEBUG) {
            error_log('[MIG][show_tabs_only] '.$msg);
        }
    }
}
