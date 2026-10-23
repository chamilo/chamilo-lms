<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;

/**
 * The forum participation settings (replies hidden until the learner posts, category description
 * in threads, participation progress bar) of a course: the value the course chose, when the
 * platform lets courses choose them, and otherwise the platform value. An empty course value means
 * the course follows the platform.
 */
final readonly class ForumParticipationSettingsHelper
{
    public const string REPLIES_AFTER_PARTICIPATION = 'show_thread_replies_after_participation';
    public const string CATEGORY_DESCRIPTION = 'show_category_description_in_thread';
    public const string PROGRESS_POSTS = 'thread_participation_progress_posts';
    public const string COURSE_OVERRIDE = 'forum_participation_can_be_modified_in_course';

    public const array SETTINGS = [
        self::REPLIES_AFTER_PARTICIPATION,
        self::CATEGORY_DESCRIPTION,
        self::PROGRESS_POSTS,
    ];

    public function __construct(
        private SettingsManager $settingsManager,
        private CCourseSettingRepository $courseSettingRepository,
    ) {}

    public function areRepliesHiddenUntilParticipation(Course $course): bool
    {
        return 'true' === $this->getValue(self::REPLIES_AFTER_PARTICIPATION, $course);
    }

    public function isCategoryDescriptionShown(Course $course): bool
    {
        return 'true' === $this->getValue(self::CATEGORY_DESCRIPTION, $course);
    }

    /**
     * The number of posts the participation progress bar expects, 0 when the bar is hidden.
     */
    public function getProgressRequiredPosts(Course $course): int
    {
        return max(0, (int) $this->getValue(self::PROGRESS_POSTS, $course));
    }

    public function canBeModifiedInCourse(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('forum.'.self::COURSE_OVERRIDE, true);
    }

    /**
     * The value the course chose, or an empty string when it follows the platform.
     */
    public function getCourseValue(string $setting, Course $course): string
    {
        $courseSetting = $this->courseSettingRepository->findOneBy([
            'cId' => (int) $course->getId(),
            'variable' => $setting,
        ]);

        return $courseSetting instanceof CCourseSetting ? self::normalizeCourseValue($setting, $courseSetting->getValue()) : '';
    }

    /**
     * A course value as stored: empty to follow the platform, otherwise "true" or "false" for the
     * yes/no settings and a number of posts for the progress bar.
     */
    public static function normalizeCourseValue(string $setting, mixed $value): string
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return '';
        }

        if (self::PROGRESS_POSTS === $setting) {
            return ctype_digit($value) ? (string) (int) $value : '';
        }

        return \in_array($value, ['true', 'false'], true) ? $value : '';
    }

    private function getValue(string $setting, Course $course): string
    {
        if ($this->canBeModifiedInCourse()) {
            $courseValue = $this->getCourseValue($setting, $course);
            if ('' !== $courseValue) {
                return $courseValue;
            }
        }

        return trim((string) $this->settingsManager->getSetting('forum.'.$setting, true));
    }
}
