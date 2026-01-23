<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use __PHP_Incomplete_Class;
use Chamilo\CourseBundle\Component\CourseCopy\Course;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use ZipArchive;

use const JSON_PARTIAL_OUTPUT_ON_ERROR;

/**
 * Base controller for Course Maintenance endpoints.
 * Holds shared helpers (debug/log, resource tree builder, selection filtering,
 * backup diagnosis, tolerant unserialize, etc.).
 */
abstract class AbstractCourseMaintenanceController extends AbstractController
{
    /**
     * Debug flag (true by default). Toggle via ?debug=0|1 or X-Debug: 0|1.
     */
    protected bool $debug = true;

    protected function setDebugFromRequest(Request $req): void
    {
        // Header has priority
        $hdr = $req->headers->get('X-Debug');
        if (null !== $hdr && '' !== trim($hdr)) {
            $this->debug = $this->toBool($hdr, $this->debug);

            return;
        }

        // Then query param
        $q = $req->query->get('debug');
        if (null !== $q && '' !== trim((string) $q)) {
            $this->debug = $this->toBool((string) $q, $this->debug);
        }
    }

    protected function logDebug(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }

        $ctx = $context;
        // Avoid dumping huge objects
        foreach ($ctx as $k => $v) {
            if (\is_object($v)) {
                $ctx[$k] = ['_object' => $v::class];
            }
            if (\is_array($v) && \count($v) > 2000) {
                $ctx[$k] = ['_array_count' => \count($v)];
            }
        }

        $payload = $ctx ? ' '.json_encode($ctx, JSON_PARTIAL_OUTPUT_ON_ERROR) : '';
        error_log('[CourseMaintenance] '.$message.$payload);
    }

    protected static function getPhpUploadLimitBytes(): int
    {
        $toBytes = static function (?string $v): int {
            $v = trim((string) $v);
            if ('' === $v) {
                return 0;
            }
            if (is_numeric($v)) {
                return (int) $v;
            }

            $unit = strtolower(substr($v, -1));
            $num = (int) substr($v, 0, -1);

            return match ($unit) {
                'g' => $num * 1024 * 1024 * 1024,
                'm' => $num * 1024 * 1024,
                'k' => $num * 1024,
                default => (int) $v,
            };
        };

        $u1 = $toBytes(\ini_get('upload_max_filesize') ?: null);
        $u2 = $toBytes(\ini_get('post_max_size') ?: null);

        if ($u1 <= 0 && $u2 <= 0) {
            return 0;
        }
        if ($u1 <= 0) {
            return $u2;
        }
        if ($u2 <= 0) {
            return $u1;
        }

        return min($u1, $u2);
    }

    private function toBool(string $raw, bool $default = false): bool
    {
        $v = strtolower(trim($raw));
        if (\in_array($v, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (\in_array($v, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    // -----------------------------------------------------------------------------
    // Tools normalization helpers
    // -----------------------------------------------------------------------------

    /**
     * Normalize tool list to supported ones and add implied dependencies.
     *
     * @param array<int,string>|null $tools
     *
     * @return string[]
     */
    protected function normalizeSelectedTools(?array $tools): array
    {
        // Single list of supported tool buckets (must match CourseBuilder/exporters)
        $all = [
            'documents', 'links', 'quizzes', 'quiz_questions', 'surveys', 'survey_questions',
            'announcements', 'events', 'course_descriptions', 'glossary', 'wiki', 'thematic',
            'attendance', 'works', 'gradebook', 'learnpath_category', 'learnpaths', 'tool_intro', 'forums',
        ];

        // Implied dependencies
        $deps = [
            'quizzes' => ['quiz_questions'],
            'surveys' => ['survey_questions'],
            'learnpaths' => ['learnpath_category'],
        ];

        $sel = \is_array($tools) ? array_values(array_intersect($tools, $all)) : [];

        foreach ($sel as $t) {
            foreach ($deps[$t] ?? [] as $d) {
                $sel[] = $d;
            }
        }

        // Unique and preserve a sane order based on $all
        $sel = array_values(array_unique($sel));
        usort($sel, static function ($a, $b) use ($all) {
            return array_search($a, $all, true) <=> array_search($b, $all, true);
        });

        return $sel;
    }

    /**
     * Infer tools from a selection map (type => [id => true]).
     *
     * @param array<string,mixed> $selected
     *
     * @return string[]
     */
    protected function inferToolsFromSelection(array $selected): array
    {
        $types = array_map('strval', array_keys($selected));

        $map = [
            'document' => 'documents',
            'documents' => 'documents',
            'link' => 'links',
            'links' => 'links',
            'forum' => 'forums',
            'forums' => 'forums',
            'quiz' => 'quizzes',
            'quizzes' => 'quizzes',
            'exercise' => 'quizzes',
            'survey' => 'surveys',
            'surveys' => 'surveys',
            'learnpath' => 'learnpaths',
            'learnpaths' => 'learnpaths',
            'work' => 'works',
            'works' => 'works',
            'glossary' => 'glossary',
            'tool_intro' => 'tool_intro',
            'course_description' => 'course_descriptions',
            'course_descriptions' => 'course_descriptions',
        ];

        $out = [];
        foreach ($types as $t) {
            $k = strtolower((string) $t);
            if (isset($map[$k])) {
                $out[] = $map[$k];
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Build selection map from selected types (select all items in those types).
     *
     * @param string[] $selectedTypes
     *
     * @return array<string,array<string,bool>>
     */
    protected function buildSelectionFromTypes(object $course, array $selectedTypes): array
    {
        $out = [];

        $res = (isset($course->resources) && \is_array($course->resources)) ? $course->resources : [];
        $wanted = array_fill_keys(array_map('strtolower', array_map('strval', $selectedTypes)), true);

        foreach ($res as $rawType => $items) {
            if (!\is_array($items) || empty($items)) {
                continue;
            }

            $typeKey = $this->normalizeTypeKey($rawType);

            if (!isset($wanted[strtolower($typeKey)])) {
                continue;
            }

            $out[$typeKey] = [];
            foreach ($items as $id => $_obj) {
                if (\is_int($id) || is_numeric($id)) {
                    $out[$typeKey][(string) (int) $id] = true;
                } else {
                    $idStr = trim((string) $id);
                    if ('' !== $idStr) {
                        $out[$typeKey][$idStr] = true;
                    }
                }
            }
        }

        return $out;
    }

    // -----------------------------------------------------------------------------
    // Diagnose helpers (course_info.dat reader + tolerant decode)
    // -----------------------------------------------------------------------------

    /**
     * Replace any __PHP_Incomplete_Class instances with stdClass (deep).
     * Also traverses arrays and objects (diagnostics-only).
     */
    protected function deincomplete(mixed $v): mixed
    {
        if ($v instanceof __PHP_Incomplete_Class) {
            $o = new stdClass();
            foreach (get_object_vars($v) as $k => $vv) {
                $o->{$k} = $this->deincomplete($vv);
            }

            return $o;
        }
        if (\is_array($v)) {
            foreach ($v as $k => $vv) {
                $v[$k] = $this->deincomplete($vv);
            }

            return $v;
        }
        if (\is_object($v)) {
            foreach (get_object_vars($v) as $k => $vv) {
                $v->{$k} = $this->deincomplete($vv);
            }

            return $v;
        }

        return $v;
    }

    /**
     * Return [ok, name, index, size, data] for the first matching entry of course_info.dat (case-insensitive).
     * Also tries common subpaths, e.g., "course/course_info.dat".
     */
    protected function readCourseInfoFromZip(string $zipPath): array
    {
        $candidates = [
            'course_info.dat',
            'course/course_info.dat',
            'backup/course_info.dat',
        ];

        $zip = new ZipArchive();
        if (true !== ($err = $zip->open($zipPath))) {
            return ['ok' => false, 'error' => 'Failed to open ZIP (ZipArchive::open error '.$err.')'];
        }

        // First: direct scan (case-insensitive base name)
        $foundIdx = null;
        $foundName = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            if (!$st || !isset($st['name'])) {
                continue;
            }
            $name = (string) $st['name'];
            $base = strtolower(basename($name));
            if ('course_info.dat' === $base) {
                $foundIdx = $i;
                $foundName = $name;

                break;
            }
        }

        // Try specific candidate paths if direct scan failed
        if (null === $foundIdx) {
            foreach ($candidates as $cand) {
                $idx = $zip->locateName($cand, ZipArchive::FL_NOCASE);
                if (false !== $idx) {
                    $foundIdx = $idx;
                    $foundName = $zip->getNameIndex($idx);

                    break;
                }
            }
        }

        if (null === $foundIdx) {
            $list = [];
            $limit = min($zip->numFiles, 200);
            for ($i = 0; $i < $limit; $i++) {
                $n = $zip->getNameIndex($i);
                if (false !== $n) {
                    $list[] = $n;
                }
            }
            $zip->close();

            return [
                'ok' => false,
                'error' => 'course_info.dat not found in archive',
                'zip_list_sample' => $list,
                'num_files' => $zip->numFiles,
            ];
        }

        $stat = $zip->statIndex($foundIdx);
        $size = (int) ($stat['size'] ?? 0);
        $fp = $zip->getStream((string) $foundName);
        if (!$fp) {
            $zip->close();

            return ['ok' => false, 'error' => 'Failed to open stream for course_info.dat (getStream)'];
        }

        $data = stream_get_contents($fp);
        fclose($fp);
        $zip->close();

        if (!\is_string($data)) {
            return ['ok' => false, 'error' => 'Failed to read course_info.dat contents'];
        }

        return [
            'ok' => true,
            'name' => $foundName,
            'index' => $foundIdx,
            'size' => $size,
            'data' => $data,
        ];
    }

    /**
     * Try to detect and decode course_info.dat content.
     * Hardened: preprocess typed-prop numeric strings and register legacy aliases
     * before attempting unserialize. Falls back to relaxed mode to avoid typed
     * property crashes during diagnosis.
     */
    protected function decodeCourseInfo(string $raw): array
    {
        $r = [
            'encoding' => 'raw',
            'decoded_len' => \strlen($raw),
            'magic_hex' => bin2hex(substr($raw, 0, 8)),
            'magic_ascii' => preg_replace('/[^\x20-\x7E]/', '.', substr($raw, 0, 16)),
            'steps' => [],
            'decoded' => null,
            'is_serialized' => false,
            'is_json' => false,
            'json_preview' => null,
        ];

        $isJson = static function (string $s): bool {
            $t = ltrim($s);

            return '' !== $t && ('{' === $t[0] || '[' === $t[0]);
        };

        // Centralized tolerant unserialize with typed-props preprocessing
        $tryUnserializeTolerant = function (string $s, string $label) use (&$r) {
            $ok = false;
            $val = null;
            $err = null;
            $relaxed = false;

            // Ensure legacy aliases and coerce numeric strings before unserialize
            try {
                CourseArchiver::ensureLegacyAliases();
            } catch (Throwable) {
                // ignore
            }

            try {
                $s = CourseArchiver::preprocessSerializedPayloadForTypedProps($s);
            } catch (Throwable) {
                // ignore
            }

            // Strict mode
            set_error_handler(static function (): void {});

            try {
                $val = @unserialize($s, ['allowed_classes' => true]);
                $ok = (false !== $val) || ('b:0;' === trim($s));
            } catch (Throwable $e) {
                $err = $e->getMessage();
                $ok = false;
            } finally {
                restore_error_handler();
            }
            $r['steps'][] = ['action' => "unserialize[$label][strict]", 'ok' => $ok, 'error' => $err];

            // Relaxed fallback (no class instantiation) + deincomplete to stdClass
            if (!$ok) {
                $err2 = null;
                set_error_handler(static function (): void {});

                try {
                    $tmp = @unserialize($s, ['allowed_classes' => false]);
                    if (false !== $tmp || 'b:0;' === trim($s)) {
                        $val = $this->deincomplete($tmp);
                        $ok = true;
                        $relaxed = true;
                        $err = null;
                    }
                } catch (Throwable $e2) {
                    $err2 = $e2->getMessage();
                } finally {
                    restore_error_handler();
                }
                $r['steps'][] = ['action' => "unserialize[$label][relaxed]", 'ok' => $ok, 'error' => $err2];
            }

            if ($ok) {
                $r['is_serialized'] = true;
                $r['decoded'] = null; // keep payload minimal
                $r['used_relaxed'] = $relaxed;

                return $val;
            }

            return null;
        };

        // 0) JSON as-is?
        if ($isJson($raw)) {
            $r['encoding'] = 'json';
            $r['is_json'] = true;
            $r['json_preview'] = json_decode($raw, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);

            return $r;
        }

        // Direct PHP serialize (strict then relaxed, after preprocessing)
        if (($u = $tryUnserializeTolerant($raw, 'raw')) !== null) {
            $r['encoding'] = 'php-serialize';

            return $r + ['value' => $u];
        }

        // GZIP
        if (0 === strncmp($raw, "\x1F\x8B", 2)) {
            $dec = @gzdecode($raw);
            $r['steps'][] = ['action' => 'gzdecode', 'ok' => false !== $dec];
            if (false !== $dec) {
                if ($isJson($dec)) {
                    $r['encoding'] = 'gzip+json';
                    $r['is_json'] = true;
                    $r['json_preview'] = json_decode($dec, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);

                    return $r;
                }
                if (($u = $tryUnserializeTolerant($dec, 'gzip')) !== null) {
                    $r['encoding'] = 'gzip+php-serialize';

                    return $r + ['value' => $u];
                }
            }
        }

        // ZLIB/DEFLATE
        $z2 = substr($raw, 0, 2);
        if ("\x78\x9C" === $z2 || "\x78\xDA" === $z2) {
            $dec = @gzuncompress($raw);
            $r['steps'][] = ['action' => 'gzuncompress', 'ok' => false !== $dec];
            if (false !== $dec) {
                if ($isJson($dec)) {
                    $r['encoding'] = 'zlib+json';
                    $r['is_json'] = true;
                    $r['json_preview'] = json_decode($dec, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);

                    return $r;
                }
                if (($u = $tryUnserializeTolerant($dec, 'zlib')) !== null) {
                    $r['encoding'] = 'zlib+php-serialize';

                    return $r + ['value' => $u];
                }
            }

            $dec2 = @gzinflate($raw);
            $r['steps'][] = ['action' => 'gzinflate', 'ok' => false !== $dec2];
            if (false !== $dec2) {
                if ($isJson($dec2)) {
                    $r['encoding'] = 'deflate+json';
                    $r['is_json'] = true;
                    $r['json_preview'] = json_decode($dec2, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);

                    return $r;
                }
                if (($u = $tryUnserializeTolerant($dec2, 'deflate')) !== null) {
                    $r['encoding'] = 'deflate+php-serialize';

                    return $r + ['value' => $u];
                }
            }
        }

        // BASE64
        if (preg_match('~^[A-Za-z0-9+/=\r\n]+$~', $raw)) {
            $dec = base64_decode($raw, true);
            $r['steps'][] = ['action' => 'base64_decode', 'ok' => false !== $dec];
            if (false !== $dec) {
                if ($isJson($dec)) {
                    $r['encoding'] = 'base64(json)';
                    $r['is_json'] = true;
                    $r['json_preview'] = json_decode($dec, true, 512, JSON_PARTIAL_OUTPUT_ON_ERROR);

                    return $r;
                }
                if (($u = $tryUnserializeTolerant($dec, 'base64')) !== null) {
                    $r['encoding'] = 'base64(php-serialize)';

                    return $r + ['value' => $u];
                }

                // base64 + gzip nested
                if (0 === strncmp($dec, "\x1F\x8B", 2)) {
                    $dec2 = @gzdecode($dec);
                    $r['steps'][] = ['action' => 'base64+gzdecode', 'ok' => false !== $dec2];
                    if (false !== $dec2 && ($u = $tryUnserializeTolerant($dec2, 'base64+gzip')) !== null) {
                        $r['encoding'] = 'base64(gzip+php-serialize)';

                        return $r + ['value' => $u];
                    }
                }
            }
        }

        // Nested ZIP?
        if (0 === strncmp($raw, "PK\x03\x04", 4)) {
            $r['encoding'] = 'nested-zip';
        }

        return $r;
    }

    // -----------------------------------------------------------------------------
    // Resource tree builder (Vue-friendly) + selection filtering
    // -----------------------------------------------------------------------------

    /**
     * Copies the dependencies (document, link, quiz, etc.) to $course->resources
     * that reference the selected LearnPaths, taking the items from the full snapshot.
     *
     * Defensive by design: it won't break if something is missing or in a different format.
     *
     * @param array<string,mixed> $snapshot
     */
    protected function hydrateLpDependenciesFromSnapshot(object $course, array $snapshot): void
    {
        if (empty($course->resources['learnpath']) || !\is_array($course->resources['learnpath'])) {
            return;
        }

        $need = [];
        $addNeed = function (string $type, $id) use (&$need): void {
            $t = (string) $type;
            $i = is_numeric($id) ? (int) $id : (string) $id;
            if ('' === (string) $i || 0 === (int) $i) {
                return;
            }
            $need[$t] ??= [];
            $need[$t][$i] = true;
        };

        foreach ($course->resources['learnpath'] as $lpWrap) {
            $lp = \is_object($lpWrap) && isset($lpWrap->obj) ? $lpWrap->obj : $lpWrap;

            if (\is_object($lpWrap) && !empty($lpWrap->linked_resources) && \is_array($lpWrap->linked_resources)) {
                foreach ($lpWrap->linked_resources as $t => $ids) {
                    if (!\is_array($ids)) {
                        continue;
                    }
                    foreach ($ids as $rid) {
                        $addNeed((string) $t, $rid);
                    }
                }
            }

            $items = [];
            if (\is_object($lp) && !empty($lp->items) && \is_array($lp->items)) {
                $items = $lp->items;
            } elseif (\is_object($lpWrap) && !empty($lpWrap->items) && \is_array($lpWrap->items)) {
                $items = $lpWrap->items;
            }

            foreach ($items as $it) {
                $ito = \is_object($it) ? $it : (object) $it;

                if (!empty($ito->linked_resources) && \is_array($ito->linked_resources)) {
                    foreach ($ito->linked_resources as $t => $ids) {
                        if (!\is_array($ids)) {
                            continue;
                        }
                        foreach ($ids as $rid) {
                            $addNeed((string) $t, $rid);
                        }
                    }
                }

                foreach (['document_id' => 'document', 'doc_id' => 'document', 'link_id' => 'link', 'quiz_id' => 'quiz', 'work_id' => 'work'] as $field => $typeGuess) {
                    if (isset($ito->{$field}) && '' !== (string) $ito->{$field} && null !== $ito->{$field}) {
                        $rid = is_numeric($ito->{$field}) ? (int) $ito->{$field} : (string) $ito->{$field};
                        $t = $typeGuess ?: (string) ($ito->type ?? '');
                        if ('' !== $t) {
                            $addNeed($t, $rid);
                        }
                    }
                }

                if (!empty($ito->type) && isset($ito->ref)) {
                    $addNeed((string) $ito->type, $ito->ref);
                }
            }
        }

        if (empty($need)) {
            return;
        }

        foreach ($need as $type => $idMap) {
            if (empty($snapshot[$type]) || !\is_array($snapshot[$type])) {
                continue;
            }

            $course->resources[$type] ??= [];

            foreach (array_keys($idMap) as $rid) {
                $src = $snapshot[$type][$rid]
                    ?? $snapshot[$type][(string) $rid]
                    ?? null;

                if (!$src) {
                    continue;
                }

                if (!isset($course->resources[$type][$rid]) && !isset($course->resources[$type][(string) $rid])) {
                    $course->resources[$type][$rid] = $src;
                }
            }
        }

        $this->logDebug('[LP-deps] hydrated', [
            'types' => array_keys($need),
            'counts' => array_map(
                fn ($t) => isset($course->resources[$t]) && \is_array($course->resources[$t]) ? \count($course->resources[$t]) : 0,
                array_keys($need)
            ),
        ]);
    }

    /**
     * Build a Vue-friendly tree from legacy Course.
     */
    protected function buildResourceTreeForVue(object $course): array
    {
        if ($this->debug) {
            $this->logDebug('[buildResourceTreeForVue] start');
        }

        $resources = \is_object($course) && isset($course->resources) && \is_array($course->resources)
            ? $course->resources
            : [];

        $legacyTitles = [];
        if (class_exists(CourseSelectForm::class) && method_exists(CourseSelectForm::class, 'getResourceTitleList')) {
            /** @var array<string,string> $legacyTitles */
            $legacyTitles = CourseSelectForm::getResourceTitleList();
        }
        $fallbackTitles = $this->getDefaultTypeTitles();
        $skipTypes = $this->getSkipTypeKeys();

        $tree = [];

        // Documents: build nested folder tree (children)
        if (!empty($resources['document']) && \is_array($resources['document'])) {
            $docs = $resources['document'];

            $normalize = function (string $rawPath, string $title, string $filetype): string {
                $p = trim($rawPath, '/');
                $p = (string) preg_replace('~^(?:document/)+~i', '', $p);
                $parts = array_values(array_filter(explode('/', $p), 'strlen'));

                // Host segment
                if (!empty($parts) && ('localhost' === $parts[0] || str_contains($parts[0], '.'))) {
                    array_shift($parts);
                }
                // Course code segment
                if (!empty($parts) && preg_match('~^[A-Z0-9_-]{6,}$~', $parts[0])) {
                    array_shift($parts);
                }

                $clean = implode('/', $parts);
                if ('' === $clean && 'folder' !== $filetype) {
                    $clean = $title;
                }
                if ('folder' === $filetype) {
                    $clean = rtrim($clean, '/').'/';
                }

                return $clean;
            };

            $folderIdByPath = [];
            foreach ($docs as $obj) {
                if (!\is_object($obj)) {
                    continue;
                }
                $ft = (string) ($obj->filetype ?? $obj->file_type ?? '');
                if ('folder' !== $ft) {
                    continue;
                }
                $rel = $normalize((string) $obj->path, (string) $obj->title, $ft);
                $key = rtrim($rel, '/');
                if ('' !== $key) {
                    $folderIdByPath[strtolower($key)] = (int) $obj->source_id;
                }
            }

            $docRoot = [];
            $findChild = static function (array &$children, string $label): ?int {
                foreach ($children as $i => $n) {
                    if ((string) ($n['label'] ?? '') === $label) {
                        return $i;
                    }
                }

                return null;
            };

            foreach ($docs as $obj) {
                if (!\is_object($obj)) {
                    continue;
                }

                $title = (string) $obj->title;
                $filetype = (string) ($obj->filetype ?? $obj->file_type ?? '');
                $rel = $normalize((string) $obj->path, $title, $filetype);
                $parts = array_values(array_filter(explode('/', trim($rel, '/')), 'strlen'));

                $cursor = &$docRoot;
                $soFar = '';
                $total = \count($parts);

                for ($i = 0; $i < $total; $i++) {
                    $seg = $parts[$i];
                    $isLast = ($i === $total - 1);
                    $isFolder = (!$isLast) || ('folder' === $filetype);

                    $soFar = ltrim($soFar.'/'.$seg, '/');
                    $label = $seg.($isFolder ? '/' : '');

                    $idx = $findChild($cursor, $label);
                    if (null === $idx) {
                        if ($isFolder) {
                            $folderId = $folderIdByPath[strtolower($soFar)] ?? null;
                            $node = [
                                'id' => $folderId ?? ('dir:'.$soFar),
                                'label' => $label,
                                'selectable' => true,
                                'children' => [],
                            ];
                        } else {
                            $node = [
                                'id' => (int) $obj->source_id,
                                'label' => $label,
                                'selectable' => true,
                            ];
                        }
                        $cursor[] = $node;
                        $idx = \count($cursor) - 1;
                    }

                    if ($isFolder) {
                        if (!isset($cursor[$idx]['children']) || !\is_array($cursor[$idx]['children'])) {
                            $cursor[$idx]['children'] = [];
                        }
                        $cursor = &$cursor[$idx]['children'];
                    }
                }
            }

            $sortTree = null;
            $sortTree = function (array &$nodes) use (&$sortTree): void {
                usort($nodes, static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
                foreach ($nodes as &$n) {
                    if (isset($n['children']) && \is_array($n['children'])) {
                        $sortTree($n['children']);
                    }
                }
            };
            $sortTree($docRoot);

            $tree[] = [
                'type' => 'document',
                'title' => $legacyTitles['document'] ?? ($fallbackTitles['document'] ?? 'Documents'),
                'children' => $docRoot,
            ];

            $skipTypes['document'] = true;
        }

        // Forums block
        $hasForumData =
            (!empty($resources['forum']) || !empty($resources['Forum']))
            || (!empty($resources['forum_category']) || !empty($resources['Forum_Category']))
            || (!empty($resources['forum_topic']) || !empty($resources['ForumTopic']))
            || (!empty($resources['thread']) || !empty($resources['post']) || !empty($resources['forum_post']));

        if ($hasForumData) {
            $tree[] = $this->buildForumTreeForVue(
                $course,
                $legacyTitles['forum'] ?? ($fallbackTitles['forum'] ?? 'Forums')
            );
            $skipTypes['forum'] = true;
            $skipTypes['forum_category'] = true;
            $skipTypes['forum_topic'] = true;
            $skipTypes['forum_post'] = true;
            $skipTypes['thread'] = true;
            $skipTypes['post'] = true;
        }

        // Links block (Category → Link)
        $hasLinkData =
            (!empty($resources['link']) || !empty($resources['Link']))
            || (!empty($resources['link_category']) || !empty($resources['Link_Category']));

        if ($hasLinkData) {
            $tree[] = $this->buildLinkTreeForVue(
                $course,
                $legacyTitles['link'] ?? ($fallbackTitles['link'] ?? 'Links')
            );
            $skipTypes['link'] = true;
            $skipTypes['link_category'] = true;
        }

        // Generic buckets
        foreach ($resources as $rawType => $items) {
            if (!\is_array($items) || empty($items)) {
                continue;
            }
            $typeKey = $this->normalizeTypeKey($rawType);
            if (isset($skipTypes[$typeKey])) {
                continue;
            }

            $groupTitle = $legacyTitles[$typeKey] ?? ($fallbackTitles[$typeKey] ?? ucfirst($typeKey));
            $group = [
                'type' => $typeKey,
                'title' => (string) $groupTitle,
                'items' => [],
            ];

            if ('gradebook' === $typeKey) {
                $group['items'][] = [
                    'id' => 'all',
                    'label' => 'Gradebook (all)',
                    'extra' => new stdClass(),
                    'selectable' => true,
                ];
                $tree[] = $group;

                continue;
            }

            foreach ($items as $id => $obj) {
                if (!\is_object($obj)) {
                    continue;
                }

                $idKey = is_numeric($id) ? (int) $id : (string) $id;
                if ((\is_int($idKey) && $idKey <= 0) || (\is_string($idKey) && '' === $idKey)) {
                    continue;
                }

                if (!$this->isSelectableItem($typeKey, $obj)) {
                    continue;
                }

                $label = $this->resolveItemLabel($typeKey, $obj, \is_int($idKey) ? $idKey : 0);

                if ('tool_intro' === $typeKey && '#0' === $label && \is_string($idKey)) {
                    $label = $idKey;
                }

                $extra = $this->buildExtra($typeKey, $obj);

                $group['items'][] = [
                    'id' => $idKey,
                    'label' => $label,
                    'extra' => $extra ?: new stdClass(),
                    'selectable' => true,
                ];
            }

            if (!empty($group['items'])) {
                usort(
                    $group['items'],
                    static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label'])
                );
                $tree[] = $group;
            }
        }

        // Preferred order
        $preferredOrder = [
            'announcement', 'document', 'course_description', 'learnpath', 'quiz', 'forum', 'glossary', 'link',
            'survey', 'thematic', 'work', 'attendance', 'wiki', 'calendar_event', 'tool_intro', 'gradebook',
        ];
        usort($tree, static function ($a, $b) use ($preferredOrder) {
            $ia = array_search($a['type'], $preferredOrder, true);
            $ib = array_search($b['type'], $preferredOrder, true);
            if (false !== $ia && false !== $ib) {
                return $ia <=> $ib;
            }
            if (false !== $ia) {
                return -1;
            }
            if (false !== $ib) {
                return 1;
            }

            return strcasecmp((string) $a['title'], (string) $b['title']);
        });

        if ($this->debug) {
            $this->logDebug(
                '[buildResourceTreeForVue] end groups',
                array_map(
                    fn ($g) => [
                        'type' => $g['type'],
                        'items' => \count($g['items'] ?? []),
                        'children' => \count($g['children'] ?? []),
                    ],
                    $tree
                )
            );
        }

        return $tree;
    }

    /**
     * Build forum tree (Category → Forum → Topic) for the UI.
     * Uses only "items" (no "children") and sets UI hints (has_children, item_count).
     */
    protected function buildForumTreeForVue(object $course, string $groupTitle): array
    {
        $this->logDebug('[buildForumTreeForVue] start');

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        // Buckets (defensive: accept legacy casings / aliases)
        $catRaw = $res['forum_category'] ?? $res['Forum_Category'] ?? [];
        $forumRaw = $res['forum'] ?? $res['Forum'] ?? [];
        $topicRaw = $res['forum_topic'] ?? $res['ForumTopic'] ?? ($res['thread'] ?? []);
        $postRaw = $res['forum_post'] ?? $res['Forum_Post'] ?? ($res['post'] ?? []);

        $this->logDebug('[buildForumTreeForVue] raw counts', [
            'categories' => \is_array($catRaw) ? \count($catRaw) : 0,
            'forums' => \is_array($forumRaw) ? \count($forumRaw) : 0,
            'topics' => \is_array($topicRaw) ? \count($topicRaw) : 0,
            'posts' => \is_array($postRaw) ? \count($postRaw) : 0,
        ]);

        // Quick classifiers (defensive)
        $isForum = function (object $o): bool {
            $e = (isset($o->obj) && \is_object($o->obj)) ? $o->obj : $o;
            if (isset($e->forum_title) && \is_string($e->forum_title)) {
                return true;
            }
            if (isset($e->default_view) || isset($e->allow_anonymous)) {
                return true;
            }
            if ((isset($e->forum_category) || isset($e->forum_category_id) || isset($e->category_id)) && !isset($e->forum_id)) {
                return true;
            }

            return false;
        };
        $isTopic = function (object $o) use ($isForum): bool {
            $e = (isset($o->obj) && \is_object($o->obj)) ? $o->obj : $o;
            if (isset($e->forum_id) && (isset($e->thread_title) || isset($e->thread_date) || isset($e->poster_name))) {
                return true;
            }
            if (isset($e->forum_id) && !isset($e->forum_title)) {
                return true;
            }
            if ($isForum($o)) {
                return false;
            }

            return false;
        };
        $getForumCategoryId = function (object $forum): int {
            $e = (isset($forum->obj) && \is_object($forum->obj)) ? $forum->obj : $forum;
            $cid = (int) ($e->forum_category ?? 0);
            if ($cid <= 0) {
                $cid = (int) ($e->forum_category_id ?? 0);
            }
            if ($cid <= 0) {
                $cid = (int) ($e->category_id ?? 0);
            }

            return $cid;
        };

        // Categories
        $cats = [];
        foreach ($catRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $label = $this->resolveItemLabel('forum_category', $this->objectEntity($obj), $id);
            $cats[$id] = [
                'id' => $id,
                'type' => 'forum_category',
                'label' => ('' !== $label ? $label : 'Category #'.$id).'/',
                'selectable' => true,
                'items' => [],
                'has_children' => false,
                'item_count' => 0,
                'extra' => ['filetype' => 'folder'],
            ];
        }

        // Virtual "Uncategorized"
        $uncatKey = -9999;
        $cats[$uncatKey] ??= [
            'id' => $uncatKey,
            'type' => 'forum_category',
            'label' => 'Uncategorized/',
            'selectable' => true,
            'items' => [],
            '_virtual' => true,
            'has_children' => false,
            'item_count' => 0,
            'extra' => ['filetype' => 'folder'],
        ];

        // Forums
        $forums = [];
        foreach ($forumRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            if (!$isForum($obj)) {
                $this->logDebug('[buildForumTreeForVue] skipped non-forum in forum bucket', ['id' => $id]);

                continue;
            }
            $forums[$id] = $this->objectEntity($obj);
        }

        // Topics (+ post counts)
        $topics = [];
        $postCountByTopic = [];
        foreach ($topicRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            if (!$isTopic($obj)) {
                continue;
            }
            $topics[$id] = $this->objectEntity($obj);
        }
        foreach ($postRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $e = $this->objectEntity($obj);
            $tid = (int) ($e->thread_id ?? 0);
            if ($tid > 0) {
                $postCountByTopic[$tid] = ($postCountByTopic[$tid] ?? 0) + 1;
            }
        }

        // Attach topics to forums and forums to categories
        foreach ($forums as $fid => $f) {
            $catId = $getForumCategoryId($f);
            if (!isset($cats[$catId])) {
                $catId = $uncatKey;
            }

            $forumNode = [
                'id' => $fid,
                'type' => 'forum',
                'label' => $this->resolveItemLabel('forum', $f, $fid),
                'extra' => $this->buildExtra('forum', $f) ?: new stdClass(),
                'selectable' => true,
                'items' => [],
                // UI hints
                'has_children' => false,
                'item_count' => 0,
                'ui_depth' => 2,
            ];

            foreach ($topics as $tid => $t) {
                if ((int) ($t->forum_id ?? 0) !== $fid) {
                    continue;
                }

                $author = (string) ($t->thread_poster_name ?? $t->poster_name ?? '');
                $date = (string) ($t->thread_date ?? '');
                $nPosts = (int) ($postCountByTopic[$tid] ?? 0);

                $topicLabel = $this->resolveItemLabel('forum_topic', $t, $tid);

                $meta = [];
                if ('' !== $author) {
                    $meta[] = $author;
                }
                if ('' !== $date) {
                    $meta[] = $date;
                }
                if ($meta) {
                    $topicLabel .= ' ('.implode(', ', $meta).')';
                }
                if ($nPosts > 0) {
                    $topicLabel .= ' — '.$nPosts.' post'.(1 === $nPosts ? '' : 's');
                }

                $forumNode['items'][] = [
                    'id' => $tid,
                    'type' => 'forum_topic',
                    'label' => $topicLabel,
                    'extra' => new stdClass(),
                    'selectable' => true,
                    'ui_depth' => 3,
                    'item_count' => 0,
                ];
            }

            if (!empty($forumNode['items'])) {
                usort($forumNode['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
                $forumNode['has_children'] = true;
                $forumNode['item_count'] = \count($forumNode['items']);
            }

            $cats[$catId]['items'][] = $forumNode;
        }

        // Remove empty virtual category; sort forums inside each category and finalize hints
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['items'])) {
                return false;
            }

            return true;
        }));

        foreach ($catNodes as &$cat) {
            if (!empty($cat['items'])) {
                usort($cat['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
            }
            $cat['has_children'] = !empty($cat['items']);
            $cat['item_count'] = \count($cat['items'] ?? []);
        }
        unset($cat);

        $this->logDebug('[buildForumTreeForVue] end', ['categories' => \count($catNodes)]);

        return [
            'type' => 'forum',
            'title' => $groupTitle,
            'items' => $catNodes,
        ];
    }

    /**
     * Build link tree (Category → Link) for the UI.
     */
    protected function buildLinkTreeForVue(object $course, string $groupTitle): array
    {
        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        $catRaw = $res['link_category'] ?? $res['Link_Category'] ?? [];
        $linkRaw = $res['link'] ?? $res['Link'] ?? [];

        $cats = [];
        foreach ($catRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $label = $this->resolveItemLabel('link_category', $this->objectEntity($obj), $id);
            $cats[$id] = [
                'id' => $id,
                'type' => 'link_category',
                'label' => ('' !== $label ? $label : 'Category #'.$id).'/',
                'selectable' => true,
                'items' => [],
                'has_children' => false,
                'item_count' => 0,
                'extra' => ['filetype' => 'folder'],
            ];
        }

        // Virtual "Uncategorized"
        $uncatKey = -9999;
        $cats[$uncatKey] ??= [
            'id' => $uncatKey,
            'type' => 'link_category',
            'label' => 'Uncategorized/',
            'selectable' => true,
            'items' => [],
            '_virtual' => true,
            'has_children' => false,
            'item_count' => 0,
            'extra' => ['filetype' => 'folder'],
        ];

        foreach ($linkRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }

            $e = $this->objectEntity($obj);
            $catId = (int) ($e->category_id ?? 0);
            if (!isset($cats[$catId])) {
                $catId = $uncatKey;
            }

            $cats[$catId]['items'][] = [
                'id' => $id,
                'type' => 'link',
                'label' => $this->resolveItemLabel('link', $e, $id),
                'extra' => $this->buildExtra('link', $e) ?: new stdClass(),
                'selectable' => true,
            ];
        }

        // Drop empty virtual and finalize hints
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['items'])) {
                return false;
            }

            return true;
        }));

        foreach ($catNodes as &$cat) {
            if (!empty($cat['items'])) {
                usort($cat['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
            }
            $cat['has_children'] = !empty($cat['items']);
            $cat['item_count'] = \count($cat['items'] ?? []);
        }
        unset($cat);

        return [
            'type' => 'link',
            'title' => $groupTitle,
            'items' => $catNodes,
        ];
    }

    /**
     * Normalize a raw type to a lowercase key.
     */
    protected function normalizeTypeKey(int|string $raw): string
    {
        if (\is_int($raw)) {
            return (string) $raw;
        }

        $s = strtolower(str_replace(['\\', ' '], ['/', '_'], (string) $raw));

        $map = [
            'forum_category' => 'forum_category',
            'forumtopic' => 'forum_topic',
            'forum_topic' => 'forum_topic',
            'forum_post' => 'forum_post',
            'thread' => 'forum_topic',
            'post' => 'forum_post',
            'exercise_question' => 'exercise_question',
            'surveyquestion' => 'survey_question',
            'surveyinvitation' => 'survey_invitation',
            'survey' => 'survey',
            'link_category' => 'link_category',
            'coursecopylearnpath' => 'learnpath',
            'coursecopytestcategory' => 'test_category',
            'coursedescription' => 'course_description',
            'session_course' => 'session_course',
            'gradebookbackup' => 'gradebook',
            'scormdocument' => 'scorm',
            'tool/introduction' => 'tool_intro',
            'tool_introduction' => 'tool_intro',
        ];

        return $map[$s] ?? $s;
    }

    /**
     * Keys to skip as top-level groups in UI.
     *
     * @return array<string,bool>
     */
    protected function getSkipTypeKeys(): array
    {
        return [
            'forum_category' => true,
            'forum_topic' => true,
            'forum_post' => true,
            'thread' => true,
            'post' => true,
            'exercise_question' => true,
            'survey_question' => true,
            'survey_invitation' => true,
            'session_course' => true,
            'scorm' => true,
            'asset' => true,
            'link_category' => true,
        ];
    }

    /**
     * Default labels for groups.
     *
     * @return array<string,string>
     */
    protected function getDefaultTypeTitles(): array
    {
        return [
            'announcement' => 'Announcements',
            'document' => 'Documents',
            'glossary' => 'Glossaries',
            'calendar_event' => 'Calendar events',
            'event' => 'Calendar events',
            'link' => 'Links',
            'course_description' => 'Course descriptions',
            'learnpath' => 'Learning paths',
            'learnpath_category' => 'Learning path categories',
            'forum' => 'Forums',
            'forum_category' => 'Forum categories',
            'quiz' => 'Quizzes',
            'test_category' => 'Test categories',
            'wiki' => 'Wikis',
            'thematic' => 'Thematics',
            'attendance' => 'Attendances',
            'work' => 'Works',
            'session_course' => 'Session courses',
            'gradebook' => 'Gradebook',
            'scorm' => 'SCORM packages',
            'survey' => 'Surveys',
            'survey_question' => 'Survey questions',
            'survey_invitation' => 'Survey invitations',
            'asset' => 'Assets',
            'tool_intro' => 'Tool introductions',
        ];
    }

    protected function isSelectableItem(string $type, object $obj): bool
    {
        // Keep permissive; selection rules can be tightened later if needed.
        return true;
    }

    /**
     * Resolve label for an item with fallbacks.
     */
    protected function resolveItemLabel(string $type, object $obj, int $fallbackId): string
    {
        $entity = $this->objectEntity($obj);

        foreach (['title', 'name', 'subject', 'question', 'display', 'code', 'description'] as $k) {
            if (isset($entity->{$k}) && \is_string($entity->{$k}) && '' !== trim($entity->{$k})) {
                return trim((string) $entity->{$k});
            }
        }

        if (isset($obj->params) && \is_array($obj->params)) {
            foreach (['title', 'name', 'subject', 'display', 'description'] as $k) {
                if (!empty($obj->params[$k]) && \is_string($obj->params[$k])) {
                    return (string) $obj->params[$k];
                }
            }
        }

        switch ($type) {
            case 'document':
                // 1) raw path as stored in backup/DB
                $raw = (string) ($entity->path ?? $obj->path ?? '');
                if ('' !== $raw) {
                    // 2) normalize to relative and strip optional "document/" prefix
                    $rel = ltrim($raw, '/');
                    $rel = preg_replace('~^document/?~', '', (string) $rel);

                    // 3) folder => enforce trailing slash
                    $fileType = (string) ($entity->file_type ?? $obj->file_type ?? '');
                    if ('folder' === $fileType) {
                        $rel = rtrim((string) $rel, '/').'/';
                    }

                    // 4) last resort: basename
                    return '' !== (string) $rel ? (string) $rel : basename($raw);
                }

                // fallback: title or filename
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }

                break;

            case 'course_description':
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }
                $t = (int) ($obj->description_type ?? 0);
                $names = [
                    1 => 'Description',
                    2 => 'Objectives',
                    3 => 'Topics',
                    4 => 'Methodology',
                    5 => 'Course material',
                    6 => 'Resources',
                    7 => 'Assessment',
                    8 => 'Custom',
                ];

                return $names[$t] ?? ('#'.$fallbackId);

            case 'forum':
                if (!empty($entity->forum_title)) {
                    return (string) $entity->forum_title;
                }

                break;

            case 'forum_category':
                if (!empty($entity->cat_title)) {
                    return (string) $entity->cat_title;
                }

                break;

            case 'link':
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }
                if (!empty($obj->url)) {
                    return (string) $obj->url;
                }

                break;

            case 'survey':
                if (!empty($obj->title)) {
                    return trim((string) $obj->title);
                }

                break;

            case 'learnpath':
                if (!empty($obj->name)) {
                    return (string) $obj->name;
                }

                break;

            case 'thematic':
                if (isset($obj->params['title']) && \is_string($obj->params['title'])) {
                    return (string) $obj->params['title'];
                }

                break;

            case 'quiz':
                if (!empty($entity->title)) {
                    return (string) $entity->title;
                }

                break;

            case 'forum_topic':
                if (!empty($entity->thread_title)) {
                    return (string) $entity->thread_title;
                }

                break;
        }

        return '#'.$fallbackId;
    }

    /**
     * Extract wrapped entity (->obj) or the object itself.
     */
    protected function objectEntity(object $resource): object
    {
        if (isset($resource->obj) && \is_object($resource->obj)) {
            return $resource->obj;
        }

        return $resource;
    }

    /**
     * Extra payload per item for UI (optional).
     *
     * @return array<string,mixed>
     */
    protected function buildExtra(string $type, object $obj): array
    {
        $extra = [];

        $get = static function (object $o, string $k, $default = null) {
            return (isset($o->{$k}) && (\is_string($o->{$k}) || is_numeric($o->{$k}))) ? $o->{$k} : $default;
        };

        switch ($type) {
            case 'document':
                $extra['path'] = (string) ($get($obj, 'path', '') ?? '');
                $extra['filetype'] = (string) ($get($obj, 'file_type', '') ?? '');
                $extra['size'] = (string) ($get($obj, 'size', '') ?? '');

                break;

            case 'link':
                $extra['url'] = (string) ($get($obj, 'url', '') ?? '');
                $extra['target'] = (string) ($get($obj, 'target', '') ?? '');

                break;

            case 'forum':
                $entity = $this->objectEntity($obj);
                $extra['category_id'] = (string) ($entity->forum_category ?? '');
                $extra['default_view'] = (string) ($entity->default_view ?? '');

                break;

            case 'learnpath':
                $extra['name'] = (string) ($get($obj, 'name', '') ?? '');
                $extra['items'] = isset($obj->items) && \is_array($obj->items)
                    ? array_map(
                        static function ($i) {
                            return [
                                'id' => (int) ($i['id'] ?? 0),
                                'title' => (string) ($i['title'] ?? ''),
                                'type' => (string) ($i['item_type'] ?? ''),
                                'path' => (string) ($i['path'] ?? ''),
                            ];
                        },
                        $obj->items
                    )
                    : [];

                break;

            case 'quiz':
            case 'survey':
                $entity = $this->objectEntity($obj);
                $extra['question_ids'] = isset($entity->question_ids) && \is_array($entity->question_ids)
                    ? array_map('intval', $entity->question_ids)
                    : [];

                break;
        }

        return array_filter($extra, static fn ($v) => !('' === $v || null === $v || [] === $v));
    }

    /**
     * Get first existing key from candidates.
     */
    protected function firstExistingKey(array $orig, array $candidates): ?string
    {
        foreach ($candidates as $k) {
            if (!\is_string($k) || '' === $k) {
                continue;
            }
            if (isset($orig[$k]) && \is_array($orig[$k]) && !empty($orig[$k])) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Find the real bucket key in $orig that matches the given logical type.
     */
    protected function findBucketKey(array $orig, string $type): ?string
    {
        $want = strtolower($this->normalizeTypeKey($type));

        foreach ($orig as $k => $_v) {
            if (!\is_string($k)) {
                continue;
            }
            $nk = strtolower($this->normalizeTypeKey($k));
            if ($nk === $want) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Return bucket array for a logical type (handles legacy casings).
     */
    protected function findBucket(array $orig, string $type): array
    {
        $k = $this->findBucketKey($orig, $type);
        if (null === $k) {
            return [];
        }

        return (isset($orig[$k]) && \is_array($orig[$k])) ? $orig[$k] : [];
    }

    /**
     * Intersect a bucket by IDs (IDs map: string=>true).
     */
    protected function intersectBucketByIds(array $bucket, array $idsMap): array
    {
        // Bucket keys can be int or string, normalize to string for intersection
        $out = [];
        foreach ($bucket as $id => $obj) {
            $key = is_numeric($id) ? (string) (int) $id : (string) $id;
            if (isset($idsMap[$key])) {
                $out[$id] = $obj;
            }
        }

        return $out;
    }

    /**
     * Filter legacy Course by UI selections (and pull dependencies).
     *
     * @param array<string,array> $selected [type => [id => true]]
     */
    protected function filterLegacyCourseBySelection(object $course, array $selected): object
    {
        // Sanitize incoming selection (frontend sometimes sends synthetic groups)
        $selected = array_filter($selected, 'is_array');
        unset($selected['undefined']);

        $this->logDebug('[filterSelection] start', ['selected_types' => array_keys($selected)]);

        if (empty($course->resources) || !\is_array($course->resources)) {
            $this->logDebug('[filterSelection] course has no resources');

            return $course;
        }

        /** @var array<string,mixed> $orig */
        $orig = $course->resources;

        // Preserve meta buckets (keys that start with "__")
        $__metaBuckets = [];
        foreach ($orig as $k => $v) {
            if (\is_string($k) && str_starts_with($k, '__')) {
                $__metaBuckets[$k] = $v;
            }
        }

        $getBucket = fn (array $a, string $key): array => (isset($a[$key]) && \is_array($a[$key])) ? $a[$key] : [];

        // ---------- Forums flow ----------
        if (!empty($selected['forum'])) {
            $selForums = array_fill_keys(array_map('strval', array_keys($selected['forum'])), true);
            if (!empty($selForums)) {
                $forums = $this->findBucket($orig, 'forum');
                $threads = $this->findBucket($orig, 'forum_topic');
                $posts = $this->findBucket($orig, 'forum_post');

                $catsToKeep = [];

                foreach ($forums as $fid => $f) {
                    if (!isset($selForums[(string) (int) $fid]) && !isset($selForums[(string) $fid])) {
                        continue;
                    }
                    $e = (isset($f->obj) && \is_object($f->obj)) ? $f->obj : $f;
                    $cid = (int) ($e->forum_category ?? $e->forum_category_id ?? $e->category_id ?? 0);
                    if ($cid > 0) {
                        $catsToKeep[$cid] = true;
                    }
                }

                $threadToKeep = [];
                foreach ($threads as $tid => $t) {
                    $e = (isset($t->obj) && \is_object($t->obj)) ? $t->obj : $t;
                    $fid = (string) ((int) ($e->forum_id ?? 0));
                    if ('' !== $fid && isset($selForums[$fid])) {
                        $threadToKeep[(int) $tid] = true;
                    }
                }

                $postToKeep = [];
                foreach ($posts as $pid => $p) {
                    $e = (isset($p->obj) && \is_object($p->obj)) ? $p->obj : $p;
                    if (isset($threadToKeep[(int) ($e->thread_id ?? 0)])) {
                        $postToKeep[(int) $pid] = true;
                    }
                }

                $out = [];
                foreach ($selected as $type => $ids) {
                    if (!\is_array($ids) || empty($ids)) {
                        continue;
                    }
                    $bucket = $this->findBucket($orig, (string) $type);
                    $key = $this->findBucketKey($orig, (string) $type);
                    if (null !== $key && !empty($bucket)) {
                        $idsMap = array_fill_keys(array_map('strval', array_keys($ids)), true);
                        $out[$key] = $this->intersectBucketByIds($bucket, $idsMap);
                    }
                }

                $forumCat = $this->findBucket($orig, 'forum_category');
                $forumBucket = $this->findBucket($orig, 'forum');
                $threadBucket = $this->findBucket($orig, 'forum_topic');
                $postBucket = $this->findBucket($orig, 'forum_post');

                if (!empty($forumCat) && !empty($catsToKeep)) {
                    $out[$this->findBucketKey($orig, 'forum_category') ?? 'Forum_Category'] =
                        array_intersect_key(
                            $forumCat,
                            array_fill_keys(array_map('strval', array_keys($catsToKeep)), true)
                        );
                }

                if (!empty($forumBucket)) {
                    $out[$this->findBucketKey($orig, 'forum') ?? 'forum'] =
                        array_intersect_key($forumBucket, $selForums);
                }
                if (!empty($threadBucket)) {
                    $out[$this->findBucketKey($orig, 'forum_topic') ?? 'thread'] =
                        array_intersect_key(
                            $threadBucket,
                            array_fill_keys(array_map('strval', array_keys($threadToKeep)), true)
                        );
                }
                if (!empty($postBucket)) {
                    $out[$this->findBucketKey($orig, 'forum_post') ?? 'post'] =
                        array_intersect_key(
                            $postBucket,
                            array_fill_keys(array_map('strval', array_keys($postToKeep)), true)
                        );
                }

                // If we kept forums but no category bucket exists, keep original categories (best effort)
                if (!empty($out['forum']) && empty($out['Forum_Category']) && !empty($forumCat)) {
                    $out['Forum_Category'] = $forumCat;
                }

                $out = array_filter($out);
                $course->resources = !empty($__metaBuckets) ? ($__metaBuckets + $out) : $out;

                $this->logDebug('[filterSelection] end (forums)', [
                    'kept_types' => array_keys((array) $course->resources),
                ]);

                return $course;
            }
        }

        // ---------- Generic + quiz/survey/gradebook ----------
        $keep = [];
        foreach ($selected as $type => $ids) {
            if (!\is_array($ids) || empty($ids)) {
                continue;
            }
            $legacyKey = $this->findBucketKey($orig, (string) $type);
            if (null === $legacyKey) {
                continue;
            }
            $bucket = $orig[$legacyKey] ?? [];
            if (!empty($bucket) && \is_array($bucket)) {
                $idsMap = array_fill_keys(array_map('strval', array_keys($ids)), true);
                $keep[$legacyKey] = $this->intersectBucketByIds($bucket, $idsMap);
            }
        }

        // Gradebook
        $gbKey = $this->firstExistingKey($orig, ['gradebook', 'Gradebook', 'GradebookBackup', 'gradebookbackup']);
        if ($gbKey && !empty($selected['gradebook'])) {
            $gbBucket = $getBucket($orig, $gbKey);
            if (!empty($gbBucket)) {
                $selIds = array_keys(array_filter((array) $selected['gradebook']));
                $firstItem = reset($gbBucket);

                if (\in_array('all', $selIds, true) || !\is_object($firstItem)) {
                    $keep[$gbKey] = $gbBucket;
                    $this->logDebug('[filterSelection] kept full gradebook', ['key' => $gbKey, 'count' => \count($gbBucket)]);
                } else {
                    $keep[$gbKey] = array_intersect_key($gbBucket, array_fill_keys(array_map('strval', $selIds), true));
                    $this->logDebug('[filterSelection] kept partial gradebook', ['key' => $gbKey, 'count' => \count($keep[$gbKey])]);
                }
            }
        }

        // Documents: add parent folders for selected files
        $docKey = $this->firstExistingKey($orig, ['document', 'Document']);
        if ($docKey && !empty($keep[$docKey])) {
            $docBucket = $getBucket($orig, $docKey);

            $foldersByRel = [];
            foreach ($docBucket as $fid => $res) {
                $e = (isset($res->obj) && \is_object($res->obj)) ? $res->obj : $res;
                $ftRaw = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
                $isFolder = ('folder' === $ftRaw) || (isset($e->path) && '/' === substr((string) $e->path, -1));
                if (!$isFolder) {
                    continue;
                }

                $p = (string) ($e->path ?? '');
                if ('' === $p) {
                    continue;
                }

                // Match your current logic (strip "document/")
                $rel = '/'.ltrim(substr($p, 8), '/');
                $rel = rtrim($rel, '/').'/';
                if ('//' !== $rel) {
                    $foldersByRel[$rel] = $fid;
                }
            }

            $needFolderIds = [];
            foreach ($keep[$docKey] as $res) {
                $e = (isset($res->obj) && \is_object($res->obj)) ? $res->obj : $res;
                $ftRaw = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
                $isFolder = ('folder' === $ftRaw) || (isset($e->path) && '/' === substr((string) $e->path, -1));
                if ($isFolder) {
                    continue;
                }

                $p = (string) ($e->path ?? '');
                if ('' === $p) {
                    continue;
                }

                $rel = '/'.ltrim(substr($p, 8), '/');
                $dir = rtrim(\dirname($rel), '/');
                if ('' === $dir) {
                    continue;
                }

                $acc = '';
                foreach (array_filter(explode('/', $dir)) as $seg) {
                    $acc .= '/'.$seg;
                    $accKey = rtrim($acc, '/').'/';
                    if (isset($foldersByRel[$accKey])) {
                        $needFolderIds[$foldersByRel[$accKey]] = true;
                    }
                }
            }

            if (!empty($needFolderIds)) {
                $added = array_intersect_key($docBucket, $needFolderIds);
                $keep[$docKey] += $added;
            }
        }

        // Links: keep categories used by selected links
        $lnkKey = $this->firstExistingKey($orig, ['link', 'Link']);
        if ($lnkKey && !empty($keep[$lnkKey])) {
            $catIdsUsed = [];
            foreach ($keep[$lnkKey] as $lWrap) {
                $L = (isset($lWrap->obj) && \is_object($lWrap->obj)) ? $lWrap->obj : $lWrap;
                $cid = (int) ($L->category_id ?? 0);
                if ($cid > 0) {
                    $catIdsUsed[(string) $cid] = true;
                }
            }

            $catKey = $this->firstExistingKey($orig, ['link_category', 'Link_Category']);
            if ($catKey && !empty($catIdsUsed)) {
                $catBucket = $getBucket($orig, $catKey);
                if (!empty($catBucket)) {
                    $keep[$catKey] = array_intersect_key($catBucket, $catIdsUsed);
                }
            }
        }

        $keep = array_filter($keep);
        $course->resources = !empty($__metaBuckets) ? ($__metaBuckets + $keep) : $keep;

        $this->logDebug('[filterSelection] end (generic)', [
            'kept_types' => array_keys((array) $course->resources),
        ]);

        return $course;
    }

    // -----------------------------------------------------------------------------
    // CC 1.3 helpers (optional, used by your controller)
    // -----------------------------------------------------------------------------

    /**
     * Expand CC13 selection when categories/folders are selected.
     *
     * @param array<string,mixed> $sel
     *
     * @return array<string,array<string,bool>>
     */
    protected function expandCc13SelectionFromCategories(object $courseFull, array $sel): array
    {
        $res = (isset($courseFull->resources) && \is_array($courseFull->resources)) ? $courseFull->resources : [];

        $out = [
            'documents' => [],
            'links' => [],
            'forums' => [],
        ];

        // Documents: if a selected doc is a folder, include all docs under its path prefix
        $docBucket = $res['document'] ?? [];
        $selectedDocs = (array) ($sel['documents'] ?? []);
        if (\is_array($docBucket) && \is_array($selectedDocs)) {
            $folderPaths = [];
            foreach (array_keys($selectedDocs) as $idStr) {
                $id = (int) $idStr;
                if ($id <= 0) {
                    continue;
                }
                foreach ($docBucket as $d) {
                    if (!\is_object($d)) {
                        continue;
                    }
                    if ((int) ($d->source_id ?? 0) !== $id) {
                        continue;
                    }
                    $ft = strtolower((string) ($d->filetype ?? $d->file_type ?? ''));
                    if ('folder' === $ft) {
                        $p = (string) ($d->path ?? '');
                        if ('' !== $p) {
                            $folderPaths[] = rtrim($p, '/').'/';
                        }
                    }
                }
                $out['documents'][(string) $id] = true;
            }

            if (!empty($folderPaths)) {
                foreach ($docBucket as $d) {
                    if (!\is_object($d)) {
                        continue;
                    }
                    $sid = (int) ($d->source_id ?? 0);
                    if ($sid <= 0) {
                        continue;
                    }
                    $p = (string) ($d->path ?? '');
                    foreach ($folderPaths as $fp) {
                        if ('' !== $p && str_starts_with($p, $fp)) {
                            $out['documents'][(string) $sid] = true;
                        }
                    }
                }
            }
        }

        // Links: if a category is selected (rare), include all links from that category
        $linkBucket = $res['link'] ?? [];
        $selectedLinks = (array) ($sel['links'] ?? []);
        if (\is_array($linkBucket) && \is_array($selectedLinks)) {
            foreach (array_keys($selectedLinks) as $idStr) {
                $id = (int) $idStr;
                if ($id > 0) {
                    $out['links'][(string) $id] = true;
                }
            }
        }

        // Forums: keep as-is
        $selectedForums = (array) ($sel['forums'] ?? []);
        foreach (array_keys($selectedForums) as $idStr) {
            $id = (int) $idStr;
            if ($id > 0) {
                $out['forums'][(string) $id] = true;
            }
        }

        return $out;
    }

    /**
     * Filter course resources using a simplified selection structure (documents/links/forums).
     *
     * @param array<string,array<string,bool>> $selected
     */
    protected function filterCourseResources(object $course, array $selected): void
    {
        if (empty($course->resources) || !\is_array($course->resources)) {
            return;
        }

        $orig = $course->resources;
        $out = [];

        // documents -> document bucket
        if (!empty($selected['documents'])) {
            $docKey = $this->firstExistingKey($orig, ['document', 'Document']);
            if ($docKey) {
                $out[$docKey] = $this->intersectBucketByIds((array) ($orig[$docKey] ?? []), $selected['documents']);
            }
        }

        // links -> link bucket (+ keep categories best-effort)
        if (!empty($selected['links'])) {
            $lnkKey = $this->firstExistingKey($orig, ['link', 'Link']);
            if ($lnkKey) {
                $out[$lnkKey] = $this->intersectBucketByIds((array) ($orig[$lnkKey] ?? []), $selected['links']);
            }
        }

        // forums -> forum bucket
        if (!empty($selected['forums'])) {
            $fKey = $this->firstExistingKey($orig, ['forum', 'Forum']);
            if ($fKey) {
                $out[$fKey] = $this->intersectBucketByIds((array) ($orig[$fKey] ?? []), $selected['forums']);
            }
        }

        $course->resources = array_filter($out);
    }
}
