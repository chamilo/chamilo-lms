<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;

/**
 * Whether learners see, on every page of a course, a clock with the time they spent in it: the
 * value the course chose, when the platform lets courses choose it, and otherwise the platform
 * value. An empty course value means the course follows the platform.
 */
final readonly class CourseTimeSpentClockHelper
{
    public const string SETTING = 'show_clock_time_spent_on_all_course_page_for_learner';
    public const string COURSE_OVERRIDE = 'show_clock_time_spent_can_be_modified_in_course';
    public const string COURSE_SETTING_CATEGORY = 'tracking';

    public function __construct(
        private SettingsManager $settingsManager,
        private CCourseSettingRepository $courseSettingRepository,
        private ?CourseSettingPolicyManager $courseSettingPolicyManager = null,
    ) {}

    public function isEnabled(Course $course): bool
    {
        $courseValue = null;
        if ($this->canBeModifiedInCourse()) {
            $value = $this->getCourseValue($course);
            $courseValue = '' !== $value ? $value : null;
        }

        $platformValue = (string) $this->settingsManager->getSetting('course.'.self::SETTING, true);
        $effective = null !== $this->courseSettingPolicyManager
            ? $this->courseSettingPolicyManager->effectiveValue(self::SETTING, $courseValue, $platformValue, self::COURSE_SETTING_CATEGORY)
            : ($courseValue ?? $platformValue);

        return 'true' === trim((string) $effective);
    }

    public function canBeModifiedInCourse(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('course.'.self::COURSE_OVERRIDE, true);
    }

    /**
     * The value the course chose, or an empty string when it follows the platform.
     */
    public function getCourseValue(Course $course): string
    {
        $courseSetting = $this->courseSettingRepository->findOneBy([
            'cId' => (int) $course->getId(),
            'variable' => self::SETTING,
        ]);

        return $courseSetting instanceof CCourseSetting ? self::normalizeCourseValue($courseSetting->getValue()) : '';
    }

    /**
     * A course value as stored: empty to follow the platform, otherwise "true" or "false".
     */
    public static function normalizeCourseValue(mixed $value): string
    {
        $value = trim((string) $value);

        return \in_array($value, ['true', 'false'], true) ? $value : '';
    }
}
