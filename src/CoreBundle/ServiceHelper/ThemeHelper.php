<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;

final class ThemeHelper
{
    public const DEFAULT_THEME = 'chamilo';

    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper,
        private readonly CidReqHelper $cidReqHelper,
        private readonly SettingsCourseManager $settingsCourseManager,
    ) {}

    /**
     * Returns the name of the color theme configured to be applied on the current page.
     * The returned name depends on the platform, course or user settings.
     */
    public function getVisualTheme(): string
    {
        static $visualTheme;

        global $lp_theme_css;

        if (isset($visualTheme)) {
            return $visualTheme;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        $visualTheme = $accessUrl->getActiveColorTheme()?->getColorTheme()->getSlug();

        if ('true' == $this->settingsManager->getSetting('profile.user_selected_theme')) {
            $visualTheme = $this->userHelper->getCurrent()?->getTheme();
        }

        if ('true' == $this->settingsManager->getSetting('course.allow_course_theme')) {
            $course = $this->cidReqHelper->getCourseEntity();

            if ($course) {
                $this->settingsCourseManager->setCourse($course);

                $visualTheme = $this->settingsCourseManager->getSetting('course_theme');

                if (1 === (int) $this->settingsCourseManager->getSetting('allow_learning_path_theme')) {
                    $visualTheme = $lp_theme_css;
                }
            }
        }

        if (empty($visualTheme)) {
            return self::DEFAULT_THEME;
        }

        return $visualTheme;
    }
}
