<?php
/* For licensing terms, see /license.txt */
declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

final class CourseCalendarExport
{
    private object $course;

    public function __construct(object $course)
    {
        $this->course = $course;
    }

    /** Export course-level calendar events into course/events.xml */
    public function export(string $exportDir): int
    {
        $events = $this->collectEvents();
        if (empty($events)) {
            @error_log('[CourseCalendarExport] No events found; skipping course/events.xml');
            return 0;
        }

        $courseDir = rtrim($exportDir, '/') . '/course';
        if (!is_dir($courseDir)) {
            @mkdir($courseDir, 0775, true);
        }

        $xml = $this->buildEventsXml($events);
        file_put_contents($courseDir . '/events.xml', $xml);
        @error_log('[CourseCalendarExport] Wrote '.count($events).' events to course/events.xml');

        return count($events);
    }

    /** Collect events from legacy course resources (best-effort). */
    private function collectEvents(): array
    {
        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];
        $bag = ($res[\defined('RESOURCE_EVENT') ? RESOURCE_EVENT : 'events'] ?? null)
            ?? ($res['events'] ?? null)
            ?? ($res['event'] ?? null)
            ?? ($res['agenda'] ?? null)
            ?? [];

        $out = [];
        foreach ((array) $bag as $maybe) {
            $o = $this->unwrap($maybe);
            if (!$o) continue;

            $title = $this->firstNonEmpty($o, ['title','name','subject'], 'Event');
            $desc  = $this->firstNonEmpty($o, ['content','description','text','body'], '');
            $ts    = $this->firstTimestamp($o, ['start','start_date','from','begin','date']);
            $te    = $this->firstTimestampOrNull($o, ['end','end_date','to','due']);
            $dur   = ($te !== null && $te > $ts) ? ($te - $ts) : 0;

            $out[] = [
                'name'         => $title,
                'description'  => $desc,
                'format'       => 1,
                // Let Moodle bind on restore:
                'courseid'     => '$@NULL@$', // important
                'groupid'      => 0,
                'userid'       => 0,          // restoring without users
                'repeatid'     => 0,
                'eventtype'    => 'course',
                'timestart'    => $ts,
                'timeduration' => $dur,
                'visible'      => 1,
                'timemodified' => $ts,
                'timesort'     => $ts,
            ];
        }

        // Sort by timestart for determinism
        usort($out, static fn($a,$b) => [$a['timestart'],$a['name']] <=> [$b['timestart'],$b['name']]);
        return $out;
    }

    /** Build the minimal, Moodle-compatible events.xml */
    private function buildEventsXml(array $events): string
    {
        $eol = PHP_EOL;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$eol;
        $xml .= '<events>'.$eol;

        foreach ($events as $e) {
            $xml .= '  <event>'.$eol;
            $xml .= '    <name>'.htmlspecialchars($e['name'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'</name>'.$eol;
            $xml .= '    <description><![CDATA['.($e['description'] ?? '').']]></description>'.$eol;
            $xml .= '    <format>'.(int)$e['format'].'</format>'.$eol;
            $xml .= '    <courseid>'.$e['courseid'].'</courseid>'.$eol; // $@NULL@$
            $xml .= '    <groupid>'.(int)$e['groupid'].'</groupid>'.$eol;
            $xml .= '    <userid>'.(int)$e['userid'].'</userid>'.$eol;
            $xml .= '    <repeatid>'.(int)$e['repeatid'].'</repeatid>'.$eol;
            $xml .= '    <eventtype>'.htmlspecialchars($e['eventtype'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'</eventtype>'.$eol;
            $xml .= '    <timestart>'.(int)$e['timestart'].'</timestart>'.$eol;
            $xml .= '    <timeduration>'.(int)$e['timeduration'].'</timeduration>'.$eol;
            $xml .= '    <visible>'.(int)$e['visible'].'</visible>'.$eol;
            $xml .= '    <timemodified>'.(int)$e['timemodified'].'</timemodified>'.$eol;
            $xml .= '    <timesort>'.(int)$e['timesort'].'</timesort>'.$eol;
            $xml .= '  </event>'.$eol;
        }

        $xml .= '</events>'.$eol;
        return $xml;
    }

    private function unwrap(mixed $x): ?object
    {
        if (\is_object($x)) return isset($x->obj) && \is_object($x->obj) ? $x->obj : $x;
        if (\is_array($x))  return (object) $x;
        return null;
    }

    private function firstNonEmpty(object $o, array $keys, string $fallback=''): string
    {
        foreach ($keys as $k) {
            if (!empty($o->{$k}) && \is_string($o->{$k})) {
                $v = trim((string)$o->{$k});
                if ($v !== '') return $v;
            }
        }
        return $fallback;
    }

    private function firstTimestamp(object $o, array $keys): int
    {
        foreach ($keys as $k) {
            if (!isset($o->{$k})) continue;
            $v = $o->{$k};
            if (\is_numeric($v)) return (int)$v;
            if (\is_string($v)) { $t = strtotime($v); if ($t !== false) return (int)$t; }
        }
        return time();
    }

    private function firstTimestampOrNull(object $o, array $keys): ?int
    {
        foreach ($keys as $k) {
            if (!isset($o->{$k})) continue;
            $v = $o->{$k};
            if (\is_numeric($v)) return (int)$v;
            if (\is_string($v)) { $t = strtotime($v); if ($t !== false) return (int)$t; }
        }
        return null;
    }
}
