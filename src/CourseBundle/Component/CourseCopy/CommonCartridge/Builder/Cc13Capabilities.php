<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder;

final class Cc13Capabilities
{
    /**
     * CC 1.3 exportable types (UI + server-side guard).
     */
    public static function exportableTypes(): array
    {
        // Keep forum for UI visibility; exporter will ignore it for now.
        return ['document', 'link', 'forum'];
    }

    /**
     * Normalize and keep only exportable types in a tree built for the UI.
     * Does not mutate input structure beyond filtering supported groups/items.
     */
    public static function filterTree(array $tree): array
    {
        $allowed = array_fill_keys(self::exportableTypes(), true);
        $out = [];

        foreach ($tree as $group) {
            $type = (string) ($group['type'] ?? '');
            if (!isset($allowed[$type])) {
                continue;
            }
            // Forums: categories not selectable; items (forum) selectable; topics shown as context.
            if ('forum' === $type) {
                $out[] = $group; // Already shaped as Cat → Forum → Topic by the controller.

                continue;
            }
            $out[] = $group;
        }

        return $out;
    }

    /**
     * Normalize the selection object coming from the UI.
     * Returns: ['documents'=>[id=>true], 'links'=>[id=>true], 'forums'=>[id=>true], 'link_category'=>..., 'forum_category'=>...].
     */
    public static function filterSelection(array $sel): array
    {
        $norm = [
            'documents' => [],
            'links' => [],
            'forums' => [],
            'link_category' => [],
            'forum_category' => [],
        ];

        foreach ($sel as $type => $ids) {
            $type = strtolower((string) $type);
            if (!\is_array($ids)) {
                continue;
            }

            // Accept both singular/plural forms defensively
            switch ($type) {
                case 'document':
                case 'documents':
                    $norm['documents'] += array_fill_keys(array_map('strval', array_keys($ids)), true);

                    break;

                case 'link':
                case 'links':
                    $norm['links'] += array_fill_keys(array_map('strval', array_keys($ids)), true);

                    break;

                case 'forum':
                case 'forums':
                    $norm['forums'] += array_fill_keys(array_map('strval', array_keys($ids)), true);

                    break;

                case 'link_category':
                    $norm['link_category'] += array_fill_keys(array_map('strval', array_keys($ids)), true);

                    break;

                case 'forum_category':
                    $norm['forum_category'] += array_fill_keys(array_map('strval', array_keys($ids)), true);

                    break;

                default:
                    // ignore others
                    break;
            }
        }

        // Drop empty buckets
        return array_filter($norm, static fn ($v) => \is_array($v) && !empty($v));
    }
}
