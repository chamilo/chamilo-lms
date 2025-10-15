<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;

final class Version20251014132200 extends AbstractMigrationChamilo
{
    /** Toggle verbose debug logs */
    private const DEBUG = false;

    public function getDescription(): string
    {
        return 'Auto-detect checkbox/multi settings from Schemas and consolidate to single row per (access_url, category, variable) with CSV values built from enabled subkeys.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $this->dbg('--- [START] Checkbox-like settings consolidation ---');

        // Discover checkbox-like variables dynamically (from Schemas)
        $targetVars = $this->discoverCheckboxLikeVariables();
        if (empty($targetVars)) {
            $this->dbg('[INFO] No checkbox-like variables discovered; nothing to do.');
            $this->dbg('--- [END] Checkbox-like settings consolidation ---');
            return;
        }

        // Normalize bracketed arrays -> CSV (e.g. ['a','b'] -> a,b) for those variables
        $this->normalizeBracketedArraysToCsv($conn, $targetVars);

        // Consolidate duplicates by (access_url, category, variable)
        $this->consolidateDuplicatesFor($conn, $targetVars);

        // Safety pass: any remaining duplicates (for any variable)
        $this->consolidateAnyRemainingDuplicates($conn);

        $this->dbg('--- [END] Checkbox-like settings consolidation ---');
    }

    public function down(Schema $schema): void
    {
        $this->dbg('[WARN] Down migration is a no-op.');
    }

    /**
     * Get variables that behave as multi/checkbox: by transformer, allowedTypes=array, or default=array.
     *
     * @return string[]
     */
    private function discoverCheckboxLikeVariables(): array
    {
        /** @var SettingsManager|null $manager */
        $manager = $this->container->get(SettingsManager::class);
        if (!$manager) {
            $this->dbg('[WARN] SettingsManager not available; fallback to empty list.');
            return [];
        }

        $vars = [];
        $schemas = $manager->getSchemas();
        $this->dbg('[INFO] Schemas discovered: '.\count($schemas));

        foreach (array_keys($schemas) as $serviceId) {
            $namespace = $manager->convertServiceToNameSpace($serviceId);
            $this->dbg('[SCAN] Inspecting schema: '.$namespace);

            // Build SettingsBuilder to inspect allowedTypes and transformers
            $sb = new SettingsBuilder();
            $schemas[$serviceId]->buildSettings($sb);

            // Transformer: ArrayToIdentifierTransformer
            foreach ($sb->getTransformers() as $param => $transformer) {
                if ($transformer instanceof ArrayToIdentifierTransformer) {
                    $vars[$param] = true;
                    $this->dbg("[DETECT] '{$param}' flagged by ArrayToIdentifierTransformer");
                }
            }

            // Allowed types contains 'array'
            $allowed = $this->safeGetAllowedTypes($sb);
            foreach ($allowed as $param => $types) {
                if (\in_array('array', (array) $types, true)) {
                    $vars[$param] = true;
                    $this->dbg("[DETECT] '{$param}' flagged by allowedTypes=array");
                }
            }

            // Default value from schema is array
            $bag = $manager->load($namespace);
            $params = method_exists($bag, 'getParameters') ? (array) $bag->getParameters() : [];
            foreach ($params as $param => $defVal) {
                if (\is_array($defVal)) {
                    $vars[$param] = true;
                    $this->dbg("[DETECT] '{$param}' flagged by default=array");
                }
            }
        }

        $list = array_keys($vars);
        sort($list, SORT_STRING | SORT_FLAG_CASE);
        $this->dbg('[INFO] Checkbox-like variables: '.implode(', ', $list));

        return $list;
    }

    private function safeGetAllowedTypes(SettingsBuilder $sb): array
    {
        try {
            if (method_exists($sb, 'getAllowedTypes')) {
                /** @var array<string, string[]> $types */
                $types = $sb->getAllowedTypes();
                return $types ?? [];
            }
        } catch (\Throwable $e) {
            $this->dbg('[WARN] Could not get allowedTypes: '.$e->getMessage());
        }

        return [];
    }

    private function normalizeBracketedArraysToCsv(Connection $conn, array $targetVars): void
    {
        $this->dbg('[STEP] Normalizing bracketed arrays to CSV');

        // Count rows starting with '[' after trimming left spaces
        $count = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM settings
             WHERE variable IN (?)
               AND selected_value IS NOT NULL
               AND LTRIM(selected_value) LIKE '[%'",
            [$targetVars],
            [ArrayParameterType::STRING]
        );
        $this->dbg("[INFO] Rows to normalize (bracketed arrays): {$count}");

        // Strip [ ] " ' and trim extra commas at ends
        $sql = <<<SQL
UPDATE settings
   SET selected_value = TRIM(BOTH ',' FROM
                             REPLACE(REPLACE(REPLACE(REPLACE(selected_value,'[',''),']',''),'"',''),'''',''))
 WHERE variable IN (?)
   AND selected_value IS NOT NULL
   AND LTRIM(selected_value) LIKE '[%'
SQL;

        $affected = $conn->executeStatement($sql, [$targetVars], [ArrayParameterType::STRING]);
        $this->dbg("[DONE] Normalized rows: {$affected}");
    }

    private function consolidateDuplicatesFor(Connection $conn, array $targetVars): void
    {
        $this->dbg('[STEP] Consolidating duplicates for detected variables (grouped by access_url, category, variable)');

        $dups = $conn->fetchAllAssociative(
            "SELECT access_url, category, variable, COUNT(*) c
               FROM settings
              WHERE variable IN (?)
              GROUP BY access_url, category, variable
             HAVING c > 1",
            [$targetVars],
            [ArrayParameterType::STRING]
        );

        $this->dbg('[INFO] Duplicate groups found: '.\count($dups));

        foreach ($dups as $row) {
            $accessUrl = $row['access_url']; // can be null
            $category  = (string) $row['category'];
            $var       = (string) $row['variable'];
            $cnt       = (int) $row['c'];

            $this->dbg("[GROUP] Consolidating variable='{$var}' category='{$category}' access_url=".
                ($accessUrl === null ? 'NULL' : (string) $accessUrl)." (rows={$cnt})");

            $this->consolidateOne($conn, $accessUrl, $category, $var);
        }
    }

    private function consolidateAnyRemainingDuplicates(Connection $conn): void
    {
        $this->dbg('[STEP] Safety pass: consolidating remaining duplicates (any variable)');

        $dups = $conn->fetchAllAssociative(
            "SELECT access_url, category, variable, COUNT(*) c
               FROM settings
              GROUP BY access_url, category, variable
             HAVING c > 1"
        );

        $this->dbg('[INFO] Remaining duplicate groups: '.\count($dups));

        foreach ($dups as $row) {
            $accessUrl = $row['access_url'];
            $category  = (string) $row['category'];
            $var       = (string) $row['variable'];
            $cnt       = (int) $row['c'];

            $this->dbg("[GROUP] (safety) Consolidating variable='{$var}' category='{$category}' access_url=".
                ($accessUrl === null ? 'NULL' : (string) $accessUrl)." (rows={$cnt})");

            $this->consolidateOne($conn, $accessUrl, $category, $var);
        }
    }

    /**
     * Consolidate one (access_url, category, variable) group into a single row.
     * Correct rule:
     *  - If rows have subkey: CSV = list of subkeys whose value is "truthy" (1/true/yes/on) OR equals the subkey (e.g. showonline: subkey=course, value='course').
     *  - If no subkey rows: CSV from selected_value tokens (cleaned).
     *
     * @param int|string|null $accessUrl
     */
    private function consolidateOne(Connection $conn, $accessUrl, string $category, string $variable): void
    {
        // Build SELECT based on nullability of access_url
        if ($accessUrl === null) {
            $items = $conn->fetchAllAssociative(
                "SELECT id, selected_value, subkey
                   FROM settings
                  WHERE access_url IS NULL AND category = ? AND variable = ?
                  ORDER BY id ASC",
                [$category, $variable]
            );
        } else {
            $items = $conn->fetchAllAssociative(
                "SELECT id, selected_value, subkey
                   FROM settings
                  WHERE access_url = ? AND category = ? AND variable = ?
                  ORDER BY id ASC",
                [$accessUrl, $category, $variable]
            );
        }

        if (empty($items)) {
            $this->dbg("[SKIP] No rows for variable='{$variable}' category='{$category}' access_url=".
                ($accessUrl === null ? 'NULL' : (string) $accessUrl));
            return;
        }

        $this->dbg("[WORK] Processing variable='{$variable}' category='{$category}' access_url=".
            ($accessUrl === null ? 'NULL' : (string) $accessUrl)." (row_count=".count($items).')');

        // Determine if there is at least one subkey among rows
        $hasSubkey = false;
        foreach ($items as $it) {
            if (!empty($it['subkey'])) { $hasSubkey = true; break; }
        }

        // Build final token set (preserve first-seen order)
        $enabled = [];

        if ($hasSubkey) {
            // Multi-row with subkeys: include subkey when value is truthy OR equal to subkey
            foreach ($items as $it) {
                $id     = (int) $it['id'];
                $rawVal = (string) ($it['selected_value'] ?? '');
                $subkey = $it['subkey'] ?? null;

                $clean = str_replace(['[',']','"',"'", ' '], '', $rawVal);
                $this->dbg("[TOKENIZE] id={$id} subkey=".
                    ($subkey === null ? 'NULL' : "'".$subkey."'").
                    " raw='{$rawVal}' cleaned='{$clean}'");

                if ($subkey !== null && $subkey !== '') {
                    $v = strtolower(trim($rawVal));
                    if ($this->isTruthy($v) || $v === strtolower($subkey)) {
                        if (!isset($enabled[$subkey])) { $enabled[$subkey] = true; }
                    }
                } else {
                    // Defensive: a row without subkey inside a subkey group -> parse as CSV
                    foreach ($this->splitCsvTokens($clean) as $tok) {
                        if (!isset($enabled[$tok])) { $enabled[$tok] = true; }
                    }
                }
            }
        } else {
            // No subkeys at all: merge CSV tokens from all rows
            foreach ($items as $it) {
                $id     = (int) $it['id'];
                $rawVal = (string) ($it['selected_value'] ?? '');
                $clean = str_replace(['[',']','"',"'", ' '], '', $rawVal);

                $this->dbg("[TOKENIZE] id={$id} subkey=NULL raw='{$rawVal}' cleaned='{$clean}'");

                foreach ($this->splitCsvTokens($clean) as $tok) {
                    if (!isset($enabled[$tok])) { $enabled[$tok] = true; }
                }
            }
        }

        $finalTokens = array_keys($enabled);
        $csv = implode(',', $finalTokens);
        $keepId = (int) $items[0]['id'];

        $this->dbg("[UPDATE] KEEP id={$keepId} variable='{$variable}' category='{$category}' access_url=".
            ($accessUrl === null ? 'NULL' : (string) $accessUrl)." final_csv='{$csv}'");

        $conn->executeStatement(
            "UPDATE settings SET selected_value = ? WHERE id = ?",
            [$csv, $keepId]
        );

        // Delete the rest
        $idsToDelete = array_map(static fn($it) => (int) $it['id'], array_slice($items, 1));
        if ($idsToDelete) {
            $this->dbg("[DELETE] variable='{$variable}' category='{$category}' access_url=".
                ($accessUrl === null ? 'NULL' : (string) $accessUrl).' deleting_ids='.
                json_encode($idsToDelete));

            $in = implode(',', array_fill(0, count($idsToDelete), '?'));
            $conn->executeStatement("DELETE FROM settings WHERE id IN ($in)", $idsToDelete);
        } else {
            $this->dbg("[KEEPONLY] Only one row existed; nothing deleted.");
        }
    }

    private function isTruthy(string $v): bool
    {
        $v = strtolower(trim($v));
        return $v === '1' || $v === 'true' || $v === 'yes' || $v === 'on';
    }

    private function splitCsvTokens(string $clean): array
    {
        if ($clean === '') { return []; }
        $parts = array_map('trim', explode(',', $clean));
        $parts = array_filter($parts, static fn($x) => $x !== '');
        return array_values($parts);
    }

    private function dbg(string $msg): void
    {
        if (self::DEBUG) {
            error_log('[MIG][CheckboxFix] '.$msg);
        }
    }
}
