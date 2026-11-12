<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

/**
 * Give a long task more time and memory than the SAPI default, without ever
 * taking away what the environment already granted.
 *
 * A plain ini_set() lowers the limit whenever the caller asks for less than the
 * current value, and that value stays for the rest of the process: PHPUnit runs
 * the whole suite in one process, so one request to a download endpoint used to
 * leave every later test with the endpoint's own smaller budget.
 */
final class RuntimeLimitsHelper
{
    public static function raiseFor(int $seconds, string $memory): void
    {
        self::raiseExecutionTime($seconds);
        self::raiseMemoryLimit($memory);
    }

    public static function raiseExecutionTime(int $seconds): void
    {
        $current = (int) \ini_get('max_execution_time');

        // 0 means no limit at all, which is already more than any request here.
        if (0 === $current || $current >= $seconds) {
            return;
        }

        ini_set('max_execution_time', (string) $seconds);
    }

    public static function raiseMemoryLimit(string $memory): void
    {
        $current = self::toBytes((string) \ini_get('memory_limit'));

        // -1 means no limit at all.
        if (-1 === $current || $current >= self::toBytes($memory)) {
            return;
        }

        ini_set('memory_limit', $memory);
    }

    /**
     * Read a shorthand byte value ('512M', '1G', '-1') the way PHP itself does.
     */
    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ('' === $value) {
            return 0;
        }

        $number = (int) $value;
        $unit = strtolower(substr($value, -1));

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
