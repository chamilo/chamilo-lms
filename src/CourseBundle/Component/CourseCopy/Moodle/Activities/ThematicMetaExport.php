<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

/**
 * ThematicMetaExport
 *
 * Writes Chamilo-only metadata for Thematic into:
 *   chamilo/thematic/thematic_{moduleId}.json
 * and indexes it from chamilo/manifest.json. Moodle ignores this directory.
 *
 * Primary data source (CourseBuilder wrapper shape):
 *   - $thematic->params:               ['id','title','content','active']
 *   - $thematic->thematic_advance_list: list of advances (array of arrays)
 *   - $thematic->thematic_plan_list:    list of plans (array of arrays)
 *
 * Also supports the "legacy/domain" shape as a fallback.
 */
class ThematicMetaExport extends ActivityExport
{
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $thematic = $this->findThematicById($activityId);
        if ($thematic === null) {
            @error_log('[ThematicMetaExport] Skipping: thematic not found id=' . $activityId);
            return;
        }

        $payload = $this->buildPayloadFromLegacy($thematic, $moduleId, $sectionId);

        $base = rtrim($exportDir, '/') . '/chamilo/thematic';
        if (!is_dir($base)) {
            @mkdir($base, (int)octdec('0775'), true);
        }

        $jsonFile = $base . '/thematic_' . $moduleId . '.json';
        @file_put_contents(
            $jsonFile,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->appendToManifest($exportDir, [
            'kind'      => 'thematic',
            'moduleid'  => $moduleId,
            'sectionid' => $sectionId,
            'title'     => (string)($payload['title'] ?? 'Thematic'),
            'path'      => 'chamilo/thematic/thematic_' . $moduleId . '.json',
        ]);

        @error_log('[ThematicMetaExport] Exported thematic moduleid=' . $moduleId . ' sectionid=' . $sectionId);
    }

    /**
     * Find thematic by iid across both shapes:
     *  - CourseBuilder wrapper: params['id']
     *  - Legacy object: id/iid/source_id
     */
    private function findThematicById(int $iid): ?object
    {
        $bag = $this->course->resources[\defined('RESOURCE_THEMATIC') ? RESOURCE_THEMATIC : 'thematic']
            ?? $this->course->resources['thematic']
            ?? [];

        if (!\is_array($bag)) {
            return null;
        }

        foreach ($bag as $maybe) {
            if (!\is_object($maybe)) {
                continue;
            }

            // Direct match on wrapper params['id']
            $params = $this->readParams($maybe);
            $pid    = (int)($params['id'] ?? 0);
            if ($pid === $iid) {
                return $maybe;
            }

            // Fallback to object/id/iid/source_id
            $obj = (isset($maybe->obj) && \is_object($maybe->obj)) ? $maybe->obj : $maybe;
            $candidates = [
                (int)($obj->id ?? 0),
                (int)($obj->iid ?? 0),
                (int)($obj->source_id ?? 0),
            ];
            if (\in_array($iid, $candidates, true)) {
                return $obj;
            }
        }

        return null;
    }

    /**
     * Build payload from the CourseBuilder wrapper first; fallback to legacy getters/props.
     */
    private function buildPayloadFromLegacy(object $t, int $moduleId, int $sectionId): array
    {
        // ---- PRIMARY: CourseBuilder wrapper shape ----
        $params   = $this->readParams($t);
        $title    = (string)($params['title']   ?? $this->readFirst($t, ['title','name'], 'Thematic'));
        $content  = (string)($params['content'] ?? $this->readFirst($t, ['content','summary','description','intro'], ''));
        $active   = (int)((isset($params['active']) ? (int)$params['active'] : ($t->active ?? 1)));

        // Lists from wrapper keys
        $advanceList = $this->readList($t, [
            'thematic_advance_list', // exact key from your dump
            'thematic_advances',
            'advances',
        ]);

        $planList = $this->readList($t, [
            'thematic_plan_list', // exact key from your dump
            'thematic_plans',
            'plans',
        ]);

        // Normalize collections
        $advances = $this->normalizeAdvances($advanceList);
        $plans    = $this->normalizePlans($planList);

        // Derive optional semantics (objective/outcomes) from plans if available
        [$objective, $outcomes] = $this->deriveObjectiveAndOutcomes($plans);

        // Collect cross-links (documents/assign/quiz/forums/URLs) from texts
        $links = array_values($this->uniqueByHash(array_merge(
            $this->collectLinksFromText($content),
            ...array_map(fn($a) => $this->collectLinksFromText((string)($a['content'] ?? '')), $advances),
            ...array_map(fn($p) => $this->collectLinksFromText((string)($p['description'] ?? '')), $plans)
        )));

        return [
            'type'        => 'thematic',
            'moduleid'    => $moduleId,
            'sectionid'   => $sectionId,
            'title'       => $title,
            'content'     => $content,
            'active'      => $active,
            'objective'   => $objective,
            'outcomes'    => $outcomes,
            'advances'    => $advances,
            'plans'       => $plans,
            'links'       => $links,
            '_exportedAt' => date('c'),
        ];
    }

    /** Read $obj->params as array if present (wrapper shape). */
    private function readParams(object $obj): array
    {
        // Direct property
        if (isset($obj->params) && \is_array($obj->params)) {
            return $obj->params;
        }
        // Common getters
        foreach (['getParams','get_params'] as $m) {
            if (\is_callable([$obj, $m])) {
                try {
                    $v = $obj->{$m}();
                    if (\is_array($v)) {
                        return $v;
                    }
                } catch (\Throwable) { /* ignore */ }
            }
        }
        return [];
    }

    /** Read a list from any of the given property names or simple getters. */
    private function readList(object $obj, array $propNames): array
    {
        foreach ($propNames as $k) {
            if (isset($obj->{$k})) {
                $v = $obj->{$k};
                if (\is_array($v)) {
                    return $v;
                }
                if ($v instanceof \Traversable) {
                    return iterator_to_array($v);
                }
            }
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['_','-'], ' ', $k)));
            if (\is_callable([$obj, $getter])) {
                try {
                    $v = $obj->{$getter}();
                    if (\is_array($v)) {
                        return $v;
                    }
                    if ($v instanceof \Traversable) {
                        return iterator_to_array($v);
                    }
                } catch (\Throwable) { /* ignore */ }
            }
        }
        return [];
    }

    /** Fallback string reader from object props; returns $fallback when empty. */
    private function readFirst(object $o, array $propNames, string $fallback = ''): string
    {
        foreach ($propNames as $k) {
            if (isset($o->{$k}) && \is_string($o->{$k})) {
                $v = trim($o->{$k});
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return $fallback;
    }

    /** Normalize advances array (array-of-arrays OR array-of-objects). */
    private function normalizeAdvances(array $list): array
    {
        $out = [];
        foreach ($list as $it) {
            if (!\is_array($it) && !\is_object($it)) {
                continue;
            }
            $a = (array)$it; // array cast works for stdClass and most DTOs
            $id       = (int)($a['id']           ?? ($a['iid'] ?? 0));
            $themid   = (int)($a['thematic_id']  ?? 0);
            $content  = (string)($a['content']   ?? '');
            $start    = (string)($a['start_date']?? '');
            $duration = (int)($a['duration']     ?? 0);
            $done     = (bool)($a['done_advance']?? false);
            $attid    = (int)($a['attendance_id']?? 0);
            $roomId   = (int)($a['room_id']      ?? 0);

            $out[] = [
                'id'            => $id,
                'thematic_id'   => $themid,
                'content'       => $content,
                'start_date'    => $start,
                'start_iso8601' => $this->toIso($start),
                'duration'      => $duration,
                'done_advance'  => $done,
                'attendance_id' => $attid,
                'room_id'       => $roomId,
            ];
        }

        usort($out, function ($a, $b) {
            if (($a['id'] ?? 0) !== ($b['id'] ?? 0)) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }
            return strcmp((string)($a['start_date'] ?? ''), (string)($b['start_date'] ?? ''));
        });

        return $out;
    }

    /** Normalize plans array (array-of-arrays OR array-of-objects). */
    private function normalizePlans(array $list): array
    {
        $out = [];
        foreach ($list as $it) {
            if (!\is_array($it) && !\is_object($it)) {
                continue;
            }
            $p = (array)$it;
            $id     = (int)($p['id']              ?? ($p['iid'] ?? 0));
            $themid = (int)($p['thematic_id']     ?? 0);
            $title  = (string)($p['title']        ?? '');
            $desc   = (string)($p['description']  ?? '');
            $dtype  = (int)($p['description_type']?? 0);

            $out[] = [
                'id'               => $id,
                'thematic_id'      => $themid,
                'title'            => $title,
                'description'      => $this->normalizePlanText($desc),
                'description_type' => $dtype,
            ];
        }

        usort($out, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        return $out;
    }

    /** Very light HTML/whitespace normalization. */
    private function normalizePlanText(string $s): string
    {
        $s = preg_replace('/[ \t]+/', ' ', (string)$s);
        return trim($s ?? '');
    }

    /** Derive objective/outcomes from plans per description_type codes (1=objective, 2=outcomes). */
    private function deriveObjectiveAndOutcomes(array $plans): array
    {
        $objective = '';
        $outcomes  = [];

        foreach ($plans as $p) {
            $type = (int)($p['description_type'] ?? 0);
            $ttl  = trim((string)($p['title'] ?? ''));
            $txt  = trim((string)($p['description'] ?? ''));

            if ($type === 1 && $objective === '') {
                $objective = $ttl !== '' ? $ttl : $txt;
            } elseif ($type === 2) {
                $outcomes[] = $ttl !== '' ? $ttl : $txt;
            }
        }

        // Clean and deduplicate outcomes
        $outcomes = array_values(array_unique(array_filter($outcomes, fn($x) => $x !== '')));

        return [$objective, $outcomes];
    }

    /** Collect cheap cross-links from HTML/text. */
    private function collectLinksFromText(string $html): array
    {
        $found = [];
        $patterns = [
            ['type' => 'document', 're' => '/(?:document\/|doc:)(\d+)/i'],
            ['type' => 'quiz',     're' => '/(?:quiz\/|quiz:)(\d+)/i'],
            ['type' => 'assign',   're' => '/(?:assign\/|assign:)(\d+)/i'],
            ['type' => 'forum',    're' => '/(?:forum\/|forum:)(\d+)/i'],
            ['type' => 'url',      're' => '/https?:\/\/[\w\-\.\:]+[^\s"<>\']*/i'],
        ];

        foreach ($patterns as $p) {
            if ($p['type'] === 'url') {
                if (preg_match_all($p['re'], (string)$html, $m)) {
                    foreach ($m[0] as $u) {
                        $found[] = ['type' => 'url', 'href' => (string)$u];
                    }
                }
            } else {
                if (preg_match_all($p['re'], (string)$html, $m)) {
                    foreach ($m[1] as $id) {
                        $id = (int)$id;
                        if ($id > 0) {
                            $found[] = ['type' => $p['type'], 'id' => $id];
                        }
                    }
                }
            }
        }

        return $found;
    }

    /** Convert 'Y-m-d H:i:s' or 'Y-m-d' to ISO 8601 if possible. */
    private function toIso(string $s): ?string
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }
        $ts = strtotime($s);
        return $ts ? date('c', $ts) : null;
    }

    /** De-duplicate link arrays by hashing. */
    private function uniqueByHash(array $items): array
    {
        $seen = [];
        $out  = [];
        foreach ($items as $it) {
            $key = md5(json_encode($it));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $it;
            }
        }
        return $out;
    }

    /** Append to chamilo/manifest.json (create if missing). */
    private function appendToManifest(string $exportDir, array $record): void
    {
        $dir = rtrim($exportDir, '/') . '/chamilo';
        if (!is_dir($dir)) {
            @mkdir($dir, (int)octdec('0775'), true);
        }

        $manifestFile = $dir . '/manifest.json';
        $manifest = [
            'version'     => 1,
            'exporter'    => 'C2-MoodleExport',
            'generatedAt' => date('c'),
            'items'       => [],
        ];

        if (is_file($manifestFile)) {
            $decoded = json_decode((string)file_get_contents($manifestFile), true);
            if (\is_array($decoded)) {
                $manifest = array_replace_recursive($manifest, $decoded);
            }
            if (!isset($manifest['items']) || !\is_array($manifest['items'])) {
                $manifest['items'] = [];
            }
        }

        $manifest['items'][] = $record;

        @file_put_contents(
            $manifestFile,
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }
}
