<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

/**
 * Stateless formatting helpers shared across the Symfony layer and the legacy
 * procedural code (which delegates to these through thin shims).
 */
final class FormatHelper
{
    /**
     * Transform a size value into a human-readable "value unit" string (e.g. "500 MB").
     *
     * The value is first converted to bytes from the unit it is given in, then
     * scaled to the most appropriate unit using 1024-based magnitudes (so a value
     * expressed in megabytes round-trips with the disk_quota migration that divided
     * bytes by 1024*1024).
     *
     * @param int    $size      The size value, expressed in the unit given by $unit
     * @param string $unit      Unit the $size value is already in: 'B', 'KB', 'MB' or 'GB'
     * @param bool   $rtlCompat When true, the result is wrapped in a <bdi> element so the
     *                          LTR "number + Latin unit" run is isolated from RTL text
     */
    public static function formatFileSize(int $size, string $unit = 'B', bool $rtlCompat = false): string
    {
        $multipliers = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1048576,
            'GB' => 1073741824,
        ];

        $bytes = $size * ($multipliers[strtoupper($unit)] ?? 1);

        if ($bytes >= 1073741824) {
            $value = round($bytes / 1073741824 * 100) / 100;
            $suffix = 'GB';
        } elseif ($bytes >= 1048576) {
            $value = round($bytes / 1048576 * 100) / 100;
            $suffix = 'MB';
        } elseif ($bytes >= 1024) {
            $value = round($bytes / 1024 * 100) / 100;
            $suffix = 'KB';
        } else {
            $value = $bytes;
            $suffix = 'B';
        }

        $formatted = $value.' '.$suffix;

        return $rtlCompat ? '<bdi>'.$formatted.'</bdi>' : $formatted;
    }
}
