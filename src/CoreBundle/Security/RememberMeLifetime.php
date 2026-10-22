<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Chamilo\CoreBundle\Settings\SettingsManager;

/**
 * Shared lifetime for persistent login cookies and their database tokens.
 * A missing setting preserves the 14-day behavior during rolling upgrades.
 */
final readonly class RememberMeLifetime
{
    public const int DEFAULT_DAYS = 14;
    public const int MAX_DAYS = 90;

    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    public function getSeconds(): int
    {
        // Bypass the session-cached settings snapshot so disabling takes effect immediately.
        $value = $this->settingsManager->getSetting('security.remember_me_days', true);
        if (null === $value) {
            return self::DEFAULT_DAYS * 86400;
        }

        if (!\is_int($value) && !\is_string($value)) {
            return 0;
        }

        $raw = (string) $value;
        if (!preg_match('/^(0|[1-9][0-9]*)$/D', $raw)) {
            return 0;
        }

        $days = (int) $raw;
        if ($days > self::MAX_DAYS) {
            return 0;
        }

        return $days * 86400;
    }
}
