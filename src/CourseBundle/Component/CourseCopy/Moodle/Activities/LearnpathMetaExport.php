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
            // still return 0 after writing (possibly empty) categories.json
            $this->exportScormIndexIfAny($res, $baseDir, $exportDir);
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
        $this->exportScormIndexIfAny($res, $baseDir, $exportDir);

        return $count;
    }

    private function exportScormIndexIfAny(array $res, string $baseDir, string $exportDir): void
    {
        $scormBag =
            ($res[\defined('RESOURCE_SCORM') ? RESOURCE_SCORM : 'scorm'] ?? null)
            ?? ($res['scorm'] ?? []);

        $lpBag =
            ($res[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath'] ?? null)
            ?? ($res['learnpath'] ?? []);

        $docBag =
            ($res[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document'] ?? null)
            ?? ($res['document'] ?? []);

        $hashById = $this->buildExportedFileRelativePathMap($exportDir);

        $out = [];
        $seen = [];

        // 1) Keep existing SCORM bag entries when present.
        if (is_array($scormBag)) {
            foreach ($scormBag as $sid => $swrap) {
                $sobj = $this->unwrapIfObject($swrap);
                $sarr = $this->toArray($sobj);

                $sourceLpId = (int) ($sarr['source_lp_id'] ?? 0);
                $name = trim((string) ($sarr['name'] ?? $sarr['title'] ?? ''));
                $path = trim(str_replace('\\', '/', (string) ($sarr['path'] ?? '')), '/');
                $zip = trim(str_replace('\\', '/', (string) ($sarr['zip'] ?? '')), '/');

                if ('' === $zip && $sourceLpId > 0) {
                    $zip = $this->findScormZipForLearnpath($docBag, $hashById, $sourceLpId, $name, $path);
                }

                $key = $sourceLpId > 0 ? 'lp:'.$sourceLpId : 'scorm:'.(int) ($sarr['id'] ?? $sid);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $raw = $sarr;
                $raw['source_lp_id'] = $sourceLpId;
                if ('' !== $zip) {
                    $raw['zip'] = $zip;
                }

                $out[] = [
                    'id' => (int) ($sarr['id'] ?? $sid),
                    'name' => '' !== $name ? $name : 'SCORM package',
                    'path' => $path,
                    'zip' => $zip,
                    'source_lp_id' => $sourceLpId,
                    'raw' => $raw,
                ];
            }
        }

        // 2) Synthesize SCORM rows from LP type=2 when the builder did not create a scorm bag.
        if (is_array($lpBag)) {
            foreach ($lpBag as $lpId => $lpWrap) {
                $lpObj = $this->unwrapIfObject($lpWrap);
                $lpArr = $this->toArray($lpObj);

                $lpType = (int) ($lpArr['lp_type'] ?? 0);
                if (2 !== $lpType) {
                    continue;
                }

                $sourceLpId = (int) ($lpArr['id'] ?? $lpId);
                if ($sourceLpId <= 0) {
                    continue;
                }

                $key = 'lp:'.$sourceLpId;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $name = trim((string) ($lpArr['title'] ?? $lpArr['name'] ?? ''));
                $path = trim(str_replace('\\', '/', (string) ($lpArr['path'] ?? '')), '/');
                $zip = $this->findScormZipForLearnpath($docBag, $hashById, $sourceLpId, $name, $path);

                $raw = [
                    'id' => $sourceLpId,
                    'name' => '' !== $name ? $name : 'SCORM package',
                    'title' => '' !== $name ? $name : 'SCORM package',
                    'path' => $path,
                    'zip' => $zip,
                    'source_lp_id' => $sourceLpId,
                ];

                $out[] = [
                    'id' => $sourceLpId,
                    'name' => $raw['name'],
                    'path' => $path,
                    'zip' => $zip,
                    'source_lp_id' => $sourceLpId,
                    'raw' => $raw,
                ];
            }
        }

        if (empty($out)) {
            return;
        }

        $this->writeJson($baseDir.'/scorm_index.json', ['scorm' => $out]);
    }

    private function buildExportedFileRelativePathMap(string $exportDir): array
    {
        $filesXml = rtrim($exportDir, '/').'/files.xml';
        if (!is_file($filesXml)) {
            return [];
        }

        $xml = @simplexml_load_file($filesXml);
        if (false === $xml) {
            @error_log('[LearnpathMetaExport] ERROR reading files.xml for SCORM index');

            return [];
        }

        $map = [];

        foreach ($xml->file as $file) {
            $id = (int) ($file['id'] ?? 0);
            $hash = trim((string) ($file->contenthash ?? ''));

            if ($id <= 0 || '' === $hash) {
                continue;
            }

            $map[$id] = 'files/'.substr($hash, 0, 2).'/'.$hash;
        }

        return $map;
    }

    private function findScormZipForLearnpath(
        array $docBag,
        array $hashById,
        int $sourceLpId,
        string $lpTitle = '',
        string $lpPath = ''
    ): string {
        if (empty($docBag)) {
            return '';
        }

        $titleNeedle = mb_strtolower(trim($lpTitle));
        $pathNeedle = mb_strtolower(trim(str_replace('\\', '/', $lpPath), '/'));

        foreach ($docBag as $docId => $dwrap) {
            if (!is_object($dwrap)) {
                continue;
            }

            $dobj = $this->unwrapIfObject($dwrap);
            $darr = $this->toArray($dobj);

            $effectiveId = (int) (
                $darr['iid']
                ?? $darr['id']
                ?? ($dwrap->source_id ?? $docId)
            );

            $fileType = strtolower((string) ($darr['file_type'] ?? $darr['filetype'] ?? $dwrap->file_type ?? $dwrap->filetype ?? ''));
            if ('folder' === $fileType) {
                continue;
            }

            $docTitle = mb_strtolower(trim((string) ($darr['title'] ?? $darr['name'] ?? $dwrap->title ?? '')));
            $docPath = mb_strtolower(trim(str_replace('\\', '/', (string) ($darr['path'] ?? $dwrap->path ?? '')), '/'));
            $comment = mb_strtolower(trim((string) ($darr['comment'] ?? $dwrap->comment ?? '')));

            $looksZip = str_ends_with($docTitle, '.zip') || str_ends_with($docPath, '.zip');
            if (!$looksZip) {
                continue;
            }

            $matches = false;

            if ('' !== $comment && str_contains($comment, 'scorm zip for lp #'.$sourceLpId)) {
                $matches = true;
            }

            if (
                !$matches
                && '' !== $docPath
                && preg_match('~(^|/)scorm\s*-\s*'.preg_quote((string) $sourceLpId, '~').'\s*-~i', $docPath)
            ) {
                $matches = true;
            }

            if (
                !$matches
                && '' !== $pathNeedle
                && '' !== $docPath
                && (
                    str_contains($docPath, $pathNeedle.'/')
                    || basename(dirname($docPath)) === basename($pathNeedle)
                )
            ) {
                $matches = true;
            }

            if (
                !$matches
                && '' !== $titleNeedle
                && (
                    ('' !== $docTitle && str_contains($docTitle, $titleNeedle))
                    || ('' !== $docPath && str_contains($docPath, $titleNeedle))
                    || ('' !== $comment && str_contains($comment, $titleNeedle))
                )
            ) {
                $matches = true;
            }

            if (!$matches) {
                continue;
            }

            return (string) ($hashById[$effectiveId] ?? '');
        }

        return '';
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
