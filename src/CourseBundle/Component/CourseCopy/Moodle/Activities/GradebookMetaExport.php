<?php
/* For licensing terms, see /license.txt */
declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

/**
 * GradebookMetaExport
 *
 * Writes Chamilo-only metadata for the Gradebook into:
 *   {exportDir}/chamilo/gradebook/gradebook_{moduleId}.json
 * and indexes it from {exportDir}/chamilo/manifest.json.
 *
 * Moodle ignores the "chamilo/" directory; this is for Chamilo re-import only.
 *
 * Primary data source (CourseBuilder wrapper):
 *   - GradeBookBackup-like resource exposing a "categories" array (already serialized by the builder).
 */
class GradebookMetaExport extends ActivityExport
{
    /**
     * Export one Gradebook snapshot as JSON + manifest entry.
     *
     * @param int    $activityId Legacy/opaque wrapper id (not strictly required)
     * @param string $exportDir  Absolute temp export directory (root of backup)
     * @param int    $moduleId   Synthetic module id used to name files
     * @param int    $sectionId  Section (topic) id; informative for manifest
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $backup = $this->findGradebookBackup($activityId);
        if ($backup === null) {
            @error_log('[GradebookMetaExport] Skip: gradebook backup not found for activityId=' . $activityId);
            return;
        }

        $payload = $this->buildPayloadFromBackup($backup, $moduleId, $sectionId);

        // Ensure base dir exists: {exportDir}/chamilo/gradebook
        $base = rtrim($exportDir, '/') . '/chamilo/gradebook';
        if (!\is_dir($base)) {
            @mkdir($base, (int)\octdec('0775'), true);
        }

        // Write JSON: chamilo/gradebook/gradebook_{moduleId}.json
        $jsonFile = $base . '/gradebook_' . $moduleId . '.json';
        @file_put_contents(
            $jsonFile,
            \json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT)
        );

        // Append entry to chamilo/manifest.json
        $this->appendToManifest($exportDir, [
            'kind'      => 'gradebook',
            'moduleid'  => $moduleId,
            'sectionid' => $sectionId,
            'title'     => 'Gradebook',
            'path'      => 'chamilo/gradebook/gradebook_' . $moduleId . '.json',
        ]);

        @error_log('[GradebookMetaExport] Exported gradebook meta moduleId=' . $moduleId . ' sectionId=' . $sectionId);
    }

    /**
     * Locate the GradeBookBackup wrapper from the CourseBuilder bag.
     * Robust rules:
     *  - Accept constant or string keys ("gradebook", "Gradebook").
     *  - If there is only ONE entry, return its first value without assuming index 0.
     *  - Otherwise, loosely match by "source_id" or "id" against $iid (string tolerant).
     *  - Finally, return the first object that exposes a "categories" array.
     */
    private function findGradebookBackup(int $iid): ?object
    {
        $resources = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        // Resolve "gradebook" bag defensively
        $bag =
            ($resources[\defined('RESOURCE_GRADEBOOK') ? \constant('RESOURCE_GRADEBOOK') : 'gradebook'] ?? null)
            ?? ($resources['gradebook'] ?? null)
            ?? ($resources['Gradebook'] ?? null)
            ?? [];

        if (!\is_array($bag) || empty($bag)) {
            return null;
        }

        // Fast path: single element but do not assume index 0
        if (\count($bag) === 1) {
            $first = \reset($bag); // returns first value regardless of key
            return \is_object($first) ? $first : null;
        }

        // Try to match loosely by id/source_id (string/numeric tolerant)
        foreach ($bag as $maybe) {
            if (!\is_object($maybe)) {
                continue;
            }
            $sid = null;

            // Many wrappers expose 'source_id'
            if (isset($maybe->source_id)) {
                $sid = (string) $maybe->source_id;
            } elseif (isset($maybe->id)) {
                // Some wrappers store an 'id' field on the object
                $sid = (string) $maybe->id;
            }

            if ($sid !== null && $sid !== '') {
                if ($sid === (string) $iid) {
                    return $maybe;
                }
            }
        }

        // Fallback: pick first object that has a categories array (GradeBookBackup shape)
        foreach ($bag as $maybe) {
            if (\is_object($maybe) && isset($maybe->categories) && \is_array($maybe->categories)) {
                return $maybe;
            }
        }

        return null;
    }

    /**
     * Build JSON payload from GradeBookBackup wrapper.
     * The wrapper already contains the serialized array produced by the builder.
     * We pass it through, applying minimal normalization.
     *
     * Additionally, we compute a best-effort list of "assessed_refs" pointing to
     * referenced activities (e.g., quiz/assign ids) if such hints are present in the categories.
     */
    private function buildPayloadFromBackup(object $backup, int $moduleId, int $sectionId): array
    {
        $categories = $this->readCategories($backup);
        $assessed = $this->computeAssessedRefsFromCategories($categories);

        return [
            'type'          => 'gradebook',
            'moduleid'      => $moduleId,
            'sectionid'     => $sectionId,
            'title'         => 'Gradebook',
            'categories'    => $categories,     // structure as produced by serializeGradebookCategory()
            'assessed_refs' => $assessed,       // best-effort references for Chamilo re-import
            '_exportedAt'   => \date('c'),
        ];
    }

    /**
     * Read and normalize categories from the wrapper.
     * Accepts arrays, Traversables and shallow objects of arrays.
     */
    private function readCategories(object $backup): array
    {
        // Direct property first
        if (isset($backup->categories)) {
            return $this->deepArray($backup->categories);
        }

        // Common getters
        foreach (['getCategories', 'get_categories'] as $m) {
            if (\is_callable([$backup, $m])) {
                try {
                    $v = $backup->{$m}();
                    return $this->deepArray($v);
                } catch (\Throwable) {
                    // ignore and continue
                }
            }
        }

        // Nothing found
        return [];
    }

    /**
     * Convert input into a JSON-safe array recursively.
     * - Arrays are copied deeply
     * - Traversables become arrays
     * - StdClass/DTOs with public props are cast to (array) and normalized
     * Note: we intentionally DO NOT traverse Doctrine entities here; the builder already serialized them.
     */
    private function deepArray(mixed $value): array
    {
        if (\is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                // inner values may be arrays or scalars; recurse only for arrays/objects/traversables
                if (\is_array($v) || $v instanceof \Traversable || \is_object($v)) {
                    $out[$k] = $this->deepArray($v);
                } else {
                    $out[$k] = $v;
                }
            }
            return $out;
        }

        if ($value instanceof \Traversable) {
            return $this->deepArray(\iterator_to_array($value));
        }

        if (\is_object($value)) {
            // Cast public properties, then normalize
            return $this->deepArray((array) $value);
        }

        // If a scalar reaches here at the top-level, normalize to array
        return [$value];
    }

    /**
     * Attempt to derive a minimal set of references to assessed activities
     * from the categories structure. This is *best-effort* and will only
     * collect what is already serialized by the builder.
     *
     * Output example:
     * [
     *   {"type":"quiz","id":123},
     *   {"type":"assign","id":45}
     * ]
     */
    private function computeAssessedRefsFromCategories(array $categories): array
    {
        $out = [];
        $seen = [];

        $push = static function (string $type, int $id) use (&$out, &$seen): void {
            if ($id <= 0 || $type === '') {
                return;
            }
            $key = $type . ':' . $id;
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $out[] = ['type' => $type, 'id' => $id];
        };

        $walk = function ($node) use (&$walk, $push): void {
            if (\is_array($node)) {
                // Heuristic: look for common keys that the builder might have serialized
                $typeKeys = ['item_type', 'resource_type', 'tool', 'type', 'modulename'];
                $idKeys   = ['item_id', 'resource_id', 'source_id', 'ref_id', 'id', 'iid', 'moduleid'];

                $type = '';
                foreach ($typeKeys as $k) {
                    if (isset($node[$k]) && \is_string($node[$k]) && $node[$k] !== '') {
                        $type = \strtolower(\trim((string) $node[$k]));
                        break;
                    }
                }

                $id = 0;
                foreach ($idKeys as $k) {
                    if (isset($node[$k]) && \is_numeric($node[$k])) {
                        $id = (int) $node[$k];
                        break;
                    }
                }

                // Allow a few known aliases
                $aliases = [
                    'exercise' => 'quiz',
                    'work'     => 'assign',
                ];
                if (isset($aliases[$type])) {
                    $type = $aliases[$type];
                }

                // Only record reasonable pairs (e.g., quiz/assign/wiki/resource/url)
                if ($type !== '' && $id > 0) {
                    $push($type, $id);
                }

                // Recurse into children/columns/items if present
                foreach ($node as $v) {
                    if (\is_array($v)) {
                        $walk($v);
                    }
                }
            }
        };

        $walk($categories);

        return $out;
    }

    /**
     * Append record to chamilo/manifest.json (create if missing).
     */
    private function appendToManifest(string $exportDir, array $record): void
    {
        $dir = rtrim($exportDir, '/') . '/chamilo';
        if (!\is_dir($dir)) {
            @mkdir($dir, (int)\octdec('0775'), true);
        }

        $manifestFile = $dir . '/manifest.json';
        $manifest = [
            'version'     => 1,
            'exporter'    => 'C2-MoodleExport',
            'generatedAt' => \date('c'),
            'items'       => [],
        ];

        if (\is_file($manifestFile)) {
            $decoded = \json_decode((string) \file_get_contents($manifestFile), true);
            if (\is_array($decoded)) {
                // Merge with defaults but preserve existing 'items'
                $manifest = \array_replace_recursive($manifest, $decoded);
            }
            if (!isset($manifest['items']) || !\is_array($manifest['items'])) {
                $manifest['items'] = [];
            }
        }

        $manifest['items'][] = $record;

        @file_put_contents(
            $manifestFile,
            \json_encode($manifest, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT)
        );
    }
}
