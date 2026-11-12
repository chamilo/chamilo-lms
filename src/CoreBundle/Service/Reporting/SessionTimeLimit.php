<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Reporting;

use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;

/**
 * Caps each recorded login or course access before totals are aggregated.
 * Recorded timestamps remain untouched so administrators can adjust this threshold.
 */
final readonly class SessionTimeLimit
{
    public const int DEFAULT_MINUTES = 360;

    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    public function getMaximumSeconds(): int
    {
        $minutes = (int) $this->settingsManager->getSetting('tracking.max_time_spent_per_session_threshold', true);

        return self::secondsForMinutes($minutes);
    }

    public static function secondsForMinutes(int $minutes): int
    {
        return 60 * ($minutes > 0 && $minutes <= 525600 ? $minutes : self::DEFAULT_MINUTES);
    }

    public static function clampSeconds(int $seconds, int $maxSeconds): int
    {
        return min(max(0, $seconds), max(0, $maxSeconds));
    }

    /**
     * Only fixed developer-controlled SQL column names (or COALESCE of such names)
     * may be passed; the configured value is normalized to an integer first.
     */
    public function sql(string $start, string $end): string
    {
        return self::sqlForSeconds($start, $end, $this->getMaximumSeconds());
    }

    public static function sqlForSeconds(string $start, string $end, int $seconds): string
    {
        $identifier = '[a-zA-Z_][a-zA-Z0-9_.]*';
        foreach ([$start, $end] as $expression) {
            if (!preg_match('~\A(?:'.$identifier.'|COALESCE\('.$identifier.',\s*'.$identifier.'\))\z~D', $expression)) {
                throw new InvalidArgumentException('Unexpected duration SQL expression.');
            }
        }

        return \sprintf(
            'LEAST(GREATEST(TIMESTAMPDIFF(SECOND, %s, %s), 0), %d)',
            $start,
            $end,
            max(0, $seconds),
        );
    }
}
