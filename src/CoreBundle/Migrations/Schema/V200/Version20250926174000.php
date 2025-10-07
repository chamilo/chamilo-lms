<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20250926174000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Upsert settings (title/comment/category/value) + settings_value_template.';
    }

    public function up(Schema $schema): void
    {
        // Collect settings (existing + new) from fixtures
        $rows = [];
        $this->collectFromFixtures($rows, (array) SettingsCurrentFixtures::getExistingSettings());
        $this->collectFromFixtures($rows, (array) SettingsCurrentFixtures::getNewConfigurationSettings());

        // Index by variable (last occurrence wins)
        $byVariable = [];
        foreach ($rows as $r) {
            $byVariable[$r['variable']] = $r;
        }

        // Defaults from SettingsManager schemas
        $defaults = $this->buildDefaultsFromSchemas();

        foreach ($byVariable as $variable => $row) {
            if (!$this->isValidIdentifier($variable)) {
                error_log(\sprintf('[SKIP] Invalid variable name: "%s".', $variable));

                continue;
            }
            $category = $row['category'];
            if (!$this->isValidIdentifier($category)) {
                error_log(\sprintf('[SKIP] Invalid category for "%s": "%s".', $variable, $category));

                continue;
            }

            $title = (string) $row['title'];
            $comment = (string) $row['comment'];

            try {
                $exists = (int) $this->connection->fetchOne(
                    'SELECT COUNT(*) FROM settings WHERE variable = ?',
                    [$variable]
                );
            } catch (Throwable $e) {
                error_log(\sprintf('[ERROR] Existence check failed for "%s": %s', $variable, $e->getMessage()));

                continue;
            }

            if ($exists > 0) {
                $sql = 'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ?';
                $params = [$category, $title, $comment, $variable];
                $this->addSql($sql, $params);
            } else {
                $defaultValue = $defaults[$variable] ?? '';
                $sql = 'INSERT INTO settings (variable, category, title, comment, selected_value, access_url_changeable) VALUES (?, ?, ?, ?, ?, 0)';
                $params = [$variable, $category, $title, $comment, (string) $defaultValue];
                $this->addSql($sql, $params);
            }
        }

        $this->dryRunTemplatesUpsertAndLink();
    }

    public function down(Schema $schema): void
    {
        error_log('[MIGRATION] Down is a no-op (dry-run migration).');
    }

    /**
     * Read fixtures and normalize to: [variable, category, title, comment].
     */
    private function collectFromFixtures(array &$out, array $byCategory): void
    {
        foreach ($byCategory as $categoryKey => $settings) {
            $category = strtolower((string) $categoryKey);

            foreach ((array) $settings as $setting) {
                $variable = (string) ($setting['name'] ?? $setting['variable'] ?? '');
                if ('' === $variable) {
                    error_log(\sprintf('[WARN] Missing "name" in fixture entry for category "%s" - skipping.', $category));

                    continue;
                }

                $title = (string) ($setting['title'] ?? $variable);
                $comment = (string) ($setting['comment'] ?? '');

                $out[] = [
                    'variable' => $variable,
                    'category' => $category,
                    'title' => $title,
                    'comment' => $comment,
                ];
            }
        }
    }

    /**
     * Build default values map by scanning SettingsManager schemas.
     * Returns: [ variable => defaultValueAsString, ... ].
     */
    private function buildDefaultsFromSchemas(): array
    {
        $map = [];

        $manager = $this->container->get(SettingsManager::class);
        if (!$manager) {
            error_log('[WARN] SettingsManager not found; defaults map will be empty.');

            return $map;
        }

        try {
            $schemas = $manager->getSchemas();
        } catch (Throwable $e) {
            error_log('[WARN] getSchemas() failed on SettingsManager: '.$e->getMessage());

            return $map;
        }

        foreach (array_keys($schemas) as $serviceIdOrAlias) {
            $namespace = str_replace('chamilo_core.settings.', '', (string) $serviceIdOrAlias);

            try {
                $settingsBag = $manager->load($namespace);
            } catch (Throwable $e) {
                error_log(\sprintf('[WARN] load("%s") failed: %s', $namespace, $e->getMessage()));

                continue;
            }

            $parameters = [];

            try {
                if (method_exists($settingsBag, 'getParameters')) {
                    $parameters = (array) $settingsBag->getParameters();
                } elseif (method_exists($settingsBag, 'all')) {
                    $parameters = (array) $settingsBag->all();
                } elseif (method_exists($settingsBag, 'toArray')) {
                    $parameters = (array) $settingsBag->toArray();
                }
            } catch (Throwable $e) {
                error_log(\sprintf('[WARN] Could not extract parameters for "%s": %s', $namespace, $e->getMessage()));
                $parameters = [];
            }

            if (empty($parameters)) {
                try {
                    $keys = [];
                    if (method_exists($settingsBag, 'keys')) {
                        $keys = (array) $settingsBag->keys();
                    } elseif (method_exists($settingsBag, 'getIterator')) {
                        $keys = array_keys(iterator_to_array($settingsBag->getIterator()));
                    }
                    foreach ($keys as $k) {
                        try {
                            $parameters[$k] = $settingsBag->get($k);
                        } catch (Throwable $e) {
                            // ignore
                        }
                    }
                } catch (Throwable $e) {
                    error_log(\sprintf('[WARN] Parameter keys iteration failed for "%s": %s', $namespace, $e->getMessage()));
                }
            }

            foreach ($parameters as $var => $val) {
                $var = (string) $var;
                if ('' === $var) {
                    continue;
                }
                if (!\array_key_exists($var, $map)) {
                    $map[$var] = \is_scalar($val) ? (string) $val : (string) json_encode($val);
                }
            }
        }

        return $map;
    }

    /**
     * Upsert settings_value_template and preview linking to settings.value_template_id.
     * For safety, we:
     *  - validate variable
     *  - JSON-encode examples with proper flags
     *  - do existence checks with SELECTs.
     */
    private function dryRunTemplatesUpsertAndLink(): void
    {
        $grouped = [];

        try {
            $grouped = (array) SettingsValueTemplateFixtures::getTemplatesGrouped();
        } catch (Throwable $e) {
            error_log('[WARN] Unable to load SettingsValueTemplateFixtures::getTemplatesGrouped(): '.$e->getMessage());

            return;
        }

        foreach ($grouped as $category => $items) {
            foreach ((array) $items as $setting) {
                $variable = (string) ($setting['variable'] ?? $setting['name'] ?? '');
                $jsonExample = $setting['json_example'] ?? null;

                if ('' === $variable || !$this->isValidIdentifier($variable)) {
                    error_log(\sprintf('[SKIP] Invalid or empty template variable in category "%s".', (string) $category));

                    continue;
                }

                // Serialize JSON example safely (string for SQL param preview)
                try {
                    $jsonEncoded = json_encode($jsonExample, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } catch (Throwable $e) {
                    error_log(\sprintf('[WARN] JSON encoding failed for variable "%s": %s', $variable, $e->getMessage()));
                    $jsonEncoded = 'null';
                }

                // Check if template exists
                try {
                    $templateId = $this->connection->fetchOne(
                        'SELECT id FROM settings_value_template WHERE variable = ?',
                        [$variable]
                    );
                } catch (Throwable $e) {
                    error_log(\sprintf('[ERROR] Failed to check template existence for "%s": %s', $variable, $e->getMessage()));

                    continue;
                }

                if ($templateId) {
                    // UPDATE PREVIEW on existing template
                    $sql = 'UPDATE settings_value_template SET json_example = ?, updated_at = NOW() WHERE id = ?';
                    $params = [$jsonEncoded, $templateId];
                    $this->addSql($sql, $params);
                } else {
                    // INSERT PREVIEW new template
                    $sql = 'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) VALUES (?, ?, NOW(), NOW())';
                    $params = [$variable, $jsonEncoded];
                    $this->addSql($sql, $params);

                    // Try to discover what ID it would be (optional best-effort)
                    try {
                        // We DO NOT insert, so we cannot call lastInsertId().
                        // Instead, try to SELECT id if it exists already after a previous run; otherwise log NULL.
                        $templateId = $this->connection->fetchOne(
                            'SELECT id FROM settings_value_template WHERE variable = ?',
                            [$variable]
                        );
                    } catch (Throwable $e) {
                        $templateId = false;
                    }
                }

                // Link PREVIEW: settings.value_template_id = $templateId
                if ($templateId) {
                    $sql = 'UPDATE settings SET value_template_id = ? WHERE variable = ?';
                    $params = [$templateId, $variable];
                    $this->addSql($sql, $params);
                } else {
                    error_log(\sprintf('[INFO] Skipping link preview for "%s" (no template id available in dry-run).', $variable));
                }
            }
        }
    }

    /**
     * Allow letters, numbers, underscore, dash and dot.
     */
    private function isValidIdentifier(string $s): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_.-]+$/', $s);
    }
}
