<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

final class DateTimeHelper
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly Security $security,
    ) {}

    /**
     * Resolve timezone using platform + user settings.
     * This is the instance implementation (DI-friendly).
     */
    private function resolveTimezoneFromSettings(?string $default = null): string
    {
        $tz = $default ?: (date_default_timezone_get() ?: 'UTC');

        // Platform-specific timezone overrides server default
        $platformTz = (string) ($this->settingsManager->getSetting('platform.timezone') ?? '');
        if ('' !== $platformTz) {
            $tz = $platformTz;
        }

        // User-specific timezone if allowed
        $allowUserTz = (string) ($this->settingsManager->getSetting('profile.use_users_timezone') ?? '');
        if ('true' === $allowUserTz) {
            $user = $this->security->getUser();
            if ($user && method_exists($user, 'getTimezone')) {
                $userTz = (string) $user->getTimezone();
                if ('' !== $userTz) {
                    $tz = $userTz;
                }
            }
        }

        // Normalize wrong separators
        return str_replace('\\', '/', $tz);
    }

    /**
     * Static timezone resolver for legacy/static contexts.
     * Uses the Symfony container when available, otherwise falls back to PHP default timezone.
     */
    public static function resolveTimezone(?string $default = null): string
    {
        $fallback = $default ?: (date_default_timezone_get() ?: 'UTC');

        try {
            if (Container::$container && Container::$container->has(self::class)) {
                /** @var self $helper */
                $helper = Container::$container->get(self::class);

                return $helper->resolveTimezoneFromSettings($fallback);
            }
        } catch (Throwable) {
            // Keep fallback timezone
        }

        return $fallback;
    }

    /**
     * Symfony-safe replacement for legacy api_get_local_time().
     * - If $toTimezone is null: uses resolved timezone (platform/user).
     * - If $fromTimezone is null: assumes UTC.
     */
    public static function localTime(
        mixed $time = null,
        ?string $toTimezone = null,
        ?string $fromTimezone = null,
        bool $returnNullIfInvalidDate = false,
        bool $showTime = true,
        bool $humanForm = false,
        string $format = ''
    ): ?string {
        $fromTimezone = $fromTimezone ?: 'UTC';
        $toTimezone = $toTimezone ?: self::resolveTimezone();

        $baseDate = self::normalizeToDateTime($time, $fromTimezone, $returnNullIfInvalidDate);
        if (null === $baseDate) {
            return $returnNullIfInvalidDate ? null : '';
        }

        try {
            $localDate = $baseDate->setTimezone(new DateTimeZone($toTimezone));

            if ('' !== $format) {
                return $localDate->format($format);
            }

            // Minimal stable output (avoid legacy api_get_human_date_time()).
            $defaultFormat = $showTime ? 'Y-m-d H:i:s' : 'Y-m-d';

            return $localDate->format($defaultFormat);
        } catch (Exception) {
            return $returnNullIfInvalidDate ? null : '';
        }
    }

    public static function nowLocalDateTime(?string $timezone = null): DateTimeImmutable
    {
        $tz = $timezone ?: self::resolveTimezone();

        return new DateTimeImmutable('now', new DateTimeZone($tz));
    }

    public static function localTimeYmdHis(
        mixed $time = null,
        ?string $toTimezone = null,
        ?string $fromTimezone = null
    ): string {
        return self::localTime($time, $toTimezone, $fromTimezone, false, true, false, 'Y-m-d H:i:s') ?? '';
    }

    private static function normalizeToDateTime(
        mixed $time,
        string $fromTimezone,
        bool $returnNullIfInvalidDate
    ): ?DateTimeImmutable {
        if (null === $time || '' === $time || '0000-00-00 00:00:00' === $time) {
            return $returnNullIfInvalidDate
                ? null
                : new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }

        // Timestamp input
        if (\is_int($time) || (\is_string($time) && ctype_digit($time))) {
            $ts = (int) $time;

            if ($returnNullIfInvalidDate && $ts <= 0) {
                return null;
            }

            return (new DateTimeImmutable('@'.$ts))->setTimezone(new DateTimeZone('UTC'));
        }

        // DateTime input (legacy semantics: treat baseline as UTC)
        if ($time instanceof DateTimeInterface) {
            return new DateTimeImmutable($time->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
        }

        // String datetime
        try {
            return new DateTimeImmutable((string) $time, new DateTimeZone($fromTimezone));
        } catch (Exception) {
            return $returnNullIfInvalidDate ? null : null;
        }
    }
}
