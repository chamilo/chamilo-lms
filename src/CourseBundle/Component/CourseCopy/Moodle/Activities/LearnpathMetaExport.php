<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use function is_array;

/**
 * Dumps raw Learnpath (lessons) metadata as JSON sidecars under chamilo/learnpath/.
 * No mapping: we persist exactly the payload produced by build_learnpaths() and build_learnpath_category().
 */
class LearnpathMetaExport extends ActivityExport
{
    /** Guard to run exportAll() only once even if export() is called multiple times. */
    private bool $ran = false;

    /**
     * Entry point required by ActivityExport.
     * This meta-export is not per-activity; we dump the whole learnpath corpus once.
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        if ($this->ran) {
            return; // Already exported; keep it idempotent.
        }
        $this->ran = true;
        $this->exportAll($exportDir);
    }

    /**
     * Export categories, an index for all present learnpaths, and one folder per LP with raw JSON.
     * Returns the number of learnpaths exported.
     */
    public function exportAll(string $exportDir): int
    {
        $baseDir = rtrim($exportDir, '/').'/chamilo/learnpath';
        $this->ensureDir($baseDir);

        // Resolve resources bag defensively
        $res = is_array($this->course->resources ?? null) ? $this->course->resources : [];

        // ---- Categories (optional but recommended) ----
        $catBag =
            ($res[\defined('RESOURCE_LEARNPATH_CATEGORY') ? RESOURCE_LEARNPATH_CATEGORY : 'learnpath_category'] ?? null)
            ?? ($res['learnpath_category'] ?? [])
        ;

        $categories = [];
        if (is_array($catBag)) {
            foreach ($catBag as $cid => $cwrap) {
                $cobj = $this->unwrapIfObject($cwrap);
                $carr = $this->toArray($cobj);
                // Normalize minimal shape (id, title) if present
                $categories[] = [
                    'id'    => (int) ($carr['id']    ?? $cid),
                    'title' => (string) ($carr['title'] ?? ($carr['name'] ?? '')),
                    'raw'   => $carr, // keep full raw payload as well
                ];
            }
        }
        $this->writeJson($baseDir.'/categories.json', ['categories' => $categories]);

        // Build a map id→title for quick lookup
        $catTitle = [];
        foreach ($categories as $c) {
            $catTitle[(int) $c['id']] = (string) $c['title'];
        }

        // ---- Learnpaths ----
        $lpBag =
            ($res[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath'] ?? null)
            ?? ($res['learnpath'] ?? [])
        ;

        if (!is_array($lpBag) || empty($lpBag)) {
            @error_log('[LearnpathMetaExport] No learnpaths present in resources; skipping.');
            // still return 0 after writing (possibly empty) categories.json
            $this->exportScormIndexIfAny($res, $baseDir);
            $this->writeJson($baseDir.'/index.json', ['learnpaths' => []]);
            return 0;
        }

        $index = [];
        $count = 0;

        foreach ($lpBag as $lpId => $lpWrap) {
            $lpObj = $this->unwrapIfObject($lpWrap); // stdClass payload from builder
            $lpArr = $this->toArray($lpObj);         // full raw payload

            $lpDir = $baseDir.'/lp_'.((int) $lpArr['id'] ?: (int) $lpId);
            $this->ensureDir($lpDir);

            // Resolve category label if possible
            $cid = (int) ($lpArr['category_id'] ?? 0);
            $lpArr['_context'] = [
                'lp_id'         => (int) ($lpArr['id'] ?? $lpId),
                'lp_type'       => (int) ($lpArr['lp_type'] ?? 0), // 1=LP,2=SCORM,3=AICC
                'category_id'   => $cid,
                'category_name' => $catTitle[$cid] ?? null,
            ];

            // Persist learnpath.json (complete raw payload + _context)
            $this->writeJson($lpDir.'/learnpath.json', ['learnpath' => $lpArr]);

            // Persist items.json as a separate, ordered list (if provided)
            $items = [];
            if (isset($lpArr['items']) && is_array($lpArr['items'])) {
                $items = $lpArr['items'];
                // Stable sort by display_order if present, otherwise keep builder order
                usort($items, static function (array $a, array $b): int {
                    return (int) ($a['display_order'] ?? 0) <=> (int) ($b['display_order'] ?? 0);
                });
            }
            $this->writeJson($lpDir.'/items.json', ['items' => $items]);

            // Add to index
            $index[] = [
                'id'            => (int) ($lpArr['id'] ?? $lpId),
                'title'         => (string) ($lpArr['title'] ?? ''),
                'lp_type'       => (int)   ($lpArr['lp_type'] ?? 0),
                'category_id'   => $cid,
                'category_name' => $catTitle[$cid] ?? null,
                'dir'           => 'lp_'.((int) $lpArr['id'] ?: (int) $lpId),
            ];

            $count++;
        }

        // Persist learnpaths index
        $this->writeJson($baseDir.'/index.json', ['learnpaths' => $index]);

        // Optional SCORM index (if present in resources)
        $this->exportScormIndexIfAny($res, $baseDir);

        @error_log('[LearnpathMetaExport] Exported learnpaths='.$count.' categories='.count($categories));
        return $count;
    }

    /** If builder added "scorm" bag, also dump a simple index for reference. */
    private function exportScormIndexIfAny(array $res, string $baseDir): void
    {
        $scormBag = $res['scorm'] ?? null;
        if (!is_array($scormBag) || empty($scormBag)) {
            return;
        }
        $out = [];
        foreach ($scormBag as $sid => $swrap) {
            $sobj = $this->unwrapIfObject($swrap);
            $sarr = $this->toArray($sobj);
            $out[] = [
                'id'   => (int) ($sarr['id'] ?? $sid),
                'name' => (string) ($sarr['name'] ?? ''),
                'path' => (string) ($sarr['path'] ?? ''),
                'raw'  => $sarr,
            ];
        }
        $this->writeJson($baseDir.'/scorm_index.json', ['scorm' => $out]);
    }

    /** Ensure directory exists (recursive). */
    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, api_get_permissions_for_new_directories(), true)) {
            @error_log('[LearnpathMetaExport] ERROR mkdir failed: '.$dir);
        }
    }

    /** Write pretty JSON with utf8/slashes preserved. */
    private function writeJson(string $file, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (false === @file_put_contents($file, (string) $json)) {
            @error_log('[LearnpathMetaExport] ERROR writing file: '.$file);
        }
    }

    /** Unwrap builder wrappers (->obj) into the raw stdClass payload. */
    private function unwrapIfObject($wrap)
    {
        if (\is_object($wrap) && isset($wrap->obj) && \is_object($wrap->obj)) {
            return $wrap->obj;
        }
        return $wrap;
    }

    /** Deep convert stdClass/objects to arrays. */
    private function toArray($value)
    {
        if (\is_array($value)) {
            return array_map([$this, 'toArray'], $value);
        }
        if (\is_object($value)) {
            return array_map([$this, 'toArray'], get_object_vars($value));
        }
        return $value;
    }
}
