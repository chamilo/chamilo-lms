<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

/**
 * AttendanceMetaExport
 *
 * Writes Chamilo-only metadata for Attendance into:
 *   chamilo/attendance/attendance_{moduleId}.json
 * and indexes it from chamilo/manifest.json. Moodle will ignore this directory,
 * while Chamilo can re-import it losslessly.
 *
 * Data source: CourseBuilder legacy bag (RESOURCE_ATTENDANCE).
 */
class AttendanceMetaExport extends ActivityExport
{
    /**
     * Export one Attendance as JSON + manifest entry.
     *
     * @param int    $activityId Legacy id (iid) of the attendance activity (from CourseBuilder)
     * @param string $exportDir  Absolute temp export directory for the .mbz (root of backup)
     * @param int    $moduleId   Module id used to name directories/files inside activities/
     * @param int    $sectionId  Section id (topic), informative for manifest
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $attendance = $this->findAttendanceById($activityId);
        if (null === $attendance) {
            // Nothing to export; keep a trace for debug
            @error_log('[AttendanceMetaExport] Skipping: attendance not found id='.$activityId);
            return;
        }

        // Build payload from legacy object assembled by CourseBuilder
        $payload = $this->buildPayloadFromLegacy($attendance, $moduleId, $sectionId);

        // Ensure base dir exists: {exportDir}/chamilo/attendance
        $base = rtrim($exportDir, '/').'/chamilo/attendance';
        if (!is_dir($base)) {
            @mkdir($base, (int) octdec('0775'), true);
        }

        // Write JSON: chamilo/attendance/attendance_{moduleId}.json
        $jsonFile = $base.'/attendance_'.$moduleId.'.json';
        file_put_contents(
            $jsonFile,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        // Append entry to chamilo/manifest.json
        $this->appendToManifest($exportDir, [
            'kind'      => 'attendance',
            'moduleid'  => $moduleId,
            'sectionid' => $sectionId,
            'title'     => (string) ($payload['name'] ?? 'Attendance'),
            'path'      => 'chamilo/attendance/attendance_'.$moduleId.'.json',
        ]);

        @error_log('[AttendanceMetaExport] Exported attendance moduleid='.$moduleId.' sectionid='.$sectionId);
    }

    /**
     * Find an Attendance legacy object from the CourseBuilder bag.
     *
     * Accepts multiple buckets and wrappers defensively:
     * - resources[RESOURCE_ATTENDANCE] or resources['attendance']
     * - each item may be {$obj: …} or the object itself.
     */
    private function findAttendanceById(int $iid): ?object
    {
        $bag = $this->course->resources[\defined('RESOURCE_ATTENDANCE') ? RESOURCE_ATTENDANCE : 'attendance']
            ?? $this->course->resources['attendance']
            ?? [];

        if (!\is_array($bag)) {
            return null;
        }

        foreach ($bag as $maybe) {
            if (!\is_object($maybe)) {
                continue;
            }
            $obj = (isset($maybe->obj) && \is_object($maybe->obj)) ? $maybe->obj : $maybe;

            // Accept id, iid or source_id (defensive)
            $candidates = [
                (int) ($obj->id ?? 0),
                (int) ($obj->iid ?? 0),
                (int) ($obj->source_id ?? 0),
            ];
            if (\in_array($iid, $candidates, true)) {
                return $obj;
            }
        }

        return null;
    }

    /**
     * Build a robust JSON payload from the legacy Attendance object.
     * Tries several field names to be resilient to legacy structures.
     */
    private function buildPayloadFromLegacy(object $att, int $moduleId, int $sectionId): array
    {
        $name = $this->firstNonEmptyString($att, ['title','name'], 'Attendance');
        $intro = $this->firstNonEmptyString($att, ['description','intro','introtext'], '');
        $active = (int) ($att->active ?? 1);

        $qualTitle = $this->firstNonEmptyString($att, ['attendance_qualify_title','grade_title'], '');
        $qualMax   = (int) ($att->attendance_qualify_max ?? $att->grade_max ?? 0);
        $weight    = (float) ($att->attendance_weight ?? 0.0);
        $locked    = (int) ($att->locked ?? 0);

        $calendars = $this->extractCalendars($att);

        return [
            'type'        => 'attendance',
            'moduleid'    => $moduleId,
            'sectionid'   => $sectionId,
            'name'        => $name,
            'intro'       => $intro,
            'active'      => $active,
            'qualify'     => [
                'title' => $qualTitle,
                'max'   => $qualMax,
                'weight'=> $weight,
            ],
            'locked'      => $locked,
            'calendars'   => $calendars,
            '_exportedAt' => date('c'),
        ];
    }

    /** Extract calendars list from different possible shapes. */
    private function extractCalendars(object $att): array
    {
        // Try common property names first
        $lists = [
            $att->calendars          ?? null,
            $att->attendance_calendar?? null,
            $att->calendar           ?? null,
        ];

        // Try getter methods as fallback
        foreach (['getCalendars','get_calendar','get_attendance_calendars'] as $m) {
            if (\is_callable([$att, $m])) {
                $lists[] = $att->{$m}();
            }
        }

        // Flatten items to a normalized array
        $out = [];
        foreach ($lists as $maybeList) {
            if (!$maybeList) {
                continue;
            }
            foreach ((array) $maybeList as $c) {
                if (!\is_array($c) && !\is_object($c)) {
                    continue;
                }
                $id     = (int) ($c['id'] ?? $c['iid'] ?? $c->id ?? $c->iid ?? 0);
                $aid    = (int) ($c['attendance_id'] ?? $c->attendance_id ?? 0);
                $dt     = (string) ($c['date_time'] ?? $c->date_time ?? $c['datetime'] ?? $c->datetime ?? '');
                $done   = (bool)  ($c['done_attendance'] ?? $c->done_attendance ?? false);
                $blocked= (bool)  ($c['blocked'] ?? $c->blocked ?? false);
                $dur    = $c['duration'] ?? $c->duration ?? null;
                $dur    = (null !== $dur) ? (int) $dur : null;

                $out[$id] = [
                    'id'             => $id,
                    'attendance_id'  => $aid,
                    'date_time'      => $dt,
                    'done_attendance'=> $done,
                    'blocked'        => $blocked,
                    'duration'       => $dur,
                ];
            }
        }

        // Preserve stable order
        ksort($out);

        return array_values($out);
    }

    /** Helper: pick first non-empty string field from object. */
    private function firstNonEmptyString(object $o, array $keys, string $fallback = ''): string
    {
        foreach ($keys as $k) {
            if (!empty($o->{$k}) && \is_string($o->{$k})) {
                $v = trim((string) $o->{$k});
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return $fallback;
    }

    /** Append a record into chamilo/manifest.json (create if missing). */
    private function appendToManifest(string $exportDir, array $record): void
    {
        $dir = rtrim($exportDir, '/').'/chamilo';
        if (!is_dir($dir)) {
            @mkdir($dir, (int) octdec('0775'), true);
        }

        $manifestFile = $dir.'/manifest.json';
        $manifest = [
            'version'     => 1,
            'exporter'    => 'C2-MoodleExport',
            'generatedAt' => date('c'),
            'items'       => [],
        ];

        if (is_file($manifestFile)) {
            $decoded = json_decode((string) file_get_contents($manifestFile), true);
            if (\is_array($decoded)) {
                $manifest = array_replace_recursive($manifest, $decoded);
            }
            if (!isset($manifest['items']) || !\is_array($manifest['items'])) {
                $manifest['items'] = [];
            }
        }

        $manifest['items'][] = $record;

        file_put_contents(
            $manifestFile,
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }
}
