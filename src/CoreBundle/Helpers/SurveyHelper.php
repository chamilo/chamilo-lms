<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;

readonly class SurveyHelper
{
    public function __construct(
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * Whether the current user may manage surveys in the current course context.
     *
     * The generic course editing rules come from IsAllowedToEditHelper: platform and session
     * administrators, the course teacher, read-only sessions, a course frozen for its sessions,
     * and the student view. On top of those, a session coach only reaches surveys when
     * survey.extend_rights_for_coach_on_survey allows it, which is this tool's own switch and
     * has no equivalent in the shared helper.
     */
    public function canManage(): bool
    {
        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            return false;
        }

        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        // Reached only by platform and session administrators, whom the helper already cleared.
        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return true;
        }

        return $this->coachRightsAreExtended();
    }

    /**
     * Whether the current user may open a survey in preview mode.
     *
     * The role and this tool's coach switch, with no student view gate: previewing is reading,
     * and a teacher has to keep being able to check a survey they currently cannot edit.
     */
    public function canPreview(): bool
    {
        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->coachRightsAreExtended();
    }

    private function coachRightsAreExtended(): bool
    {
        return 'true' === $this->settingsManager->getSetting('survey.extend_rights_for_coach_on_survey', true);
    }
}
