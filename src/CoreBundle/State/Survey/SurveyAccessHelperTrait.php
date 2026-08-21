<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Who may manage surveys here, in one place instead of eleven copies.
 */
trait SurveyAccessHelperTrait
{
    /**
     * The generic course editing rules plus the one restriction that belongs to this tool.
     *
     * The helper covers what every course tool shares — platform and session administrators,
     * the course teacher, read-only sessions, a course frozen for its sessions, and the
     * student view, which none of the copied versions used to check. On top of that a session
     * coach only reaches surveys when survey.extend_rights_for_coach_on_survey allows it,
     * which is this tool's own switch and has no equivalent in the helper.
     */
    private function canManageSurveys(
        IsAllowedToEditHelper $isAllowedToEditHelper,
        Security $security,
        SettingsManager $settingsManager,
    ): bool {
        if (!$isAllowedToEditHelper->check(coach: true)) {
            return false;
        }

        if ($security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return true;
        }

        return 'true' === $settingsManager->getSetting('survey.extend_rights_for_coach_on_survey', true);
    }
}
