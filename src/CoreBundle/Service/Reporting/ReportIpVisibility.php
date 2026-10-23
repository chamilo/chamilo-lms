<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Reporting;

use Chamilo\CoreBundle\Settings\SettingsManager;

final readonly class ReportIpVisibility
{
    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    public function isHidden(): bool
    {
        return 'true' === $this->settingsManager->getSetting('tracking.hide_ip_address', true);
    }

    public function isExerciseHidden(): bool
    {
        return $this->isHidden()
            || 'true' === $this->settingsManager->getSetting('exercise.exercise_hide_ip', true);
    }
}
