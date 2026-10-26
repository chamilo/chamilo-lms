<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use InvalidArgumentException;

/**
 * The visibility a new document, test or learning path gets when a teacher creates it.
 *
 * Each tool has a platform setting "<tool>_default_visibility_on_creation" in its own settings
 * category. When "<tool>_default_visibility_can_be_modified_in_course" is enabled, a course can
 * replace it with a course setting of the same name.
 */
final readonly class ResourceDefaultVisibilityHelper
{
    public const string DOCUMENT = 'document';
    public const string EXERCISE = 'exercise';
    public const string LEARNING_PATH = 'lp';

    public const string VISIBLE = 'visible';
    public const string INVISIBLE = 'invisible';

    /**
     * The schema default of each tool: documents and tests are visible, learning paths are
     * created invisible so the teacher publishes them once they are built.
     */
    private const array DEFAULTS = [
        self::DOCUMENT => self::VISIBLE,
        self::EXERCISE => self::VISIBLE,
        self::LEARNING_PATH => self::INVISIBLE,
    ];

    public function __construct(
        private SettingsManager $settingsManager,
        private CCourseSettingRepository $courseSettingRepository,
        private ?CourseSettingPolicyManager $courseSettingPolicyManager = null,
    ) {}

    public static function visibilitySetting(string $tool): string
    {
        return $tool.'_default_visibility_on_creation';
    }

    public static function courseOverrideSetting(string $tool): string
    {
        return $tool.'_default_visibility_can_be_modified_in_course';
    }

    /**
     * @return array<string, string> the default visibility of each tool, keyed by tool
     */
    public static function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * The ResourceLink visibility to give a resource of $tool created in $course.
     */
    public function getResourceLinkVisibility(string $tool, ?Course $course = null): int
    {
        return self::VISIBLE === $this->getVisibility($tool, $course)
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;
    }

    /**
     * @return string self::VISIBLE or self::INVISIBLE
     */
    public function getVisibility(string $tool, ?Course $course = null): string
    {
        $variable = self::visibilitySetting($tool);
        $courseValue = null;
        if (null !== $course && $this->canBeModifiedInCourse($tool)) {
            $courseValue = $this->getCourseVisibility($tool, $course);
        }
        $platformValue = $this->getPlatformVisibility($tool);
        $effective = null !== $this->courseSettingPolicyManager
            ? $this->courseSettingPolicyManager->effectiveValue($variable, $courseValue, $platformValue, $tool)
            : ($courseValue ?? $platformValue);

        return self::normalize($effective) ?? $this->getPlatformVisibility($tool);
    }

    /**
     * @return string self::VISIBLE or self::INVISIBLE
     */
    public function getPlatformVisibility(string $tool): string
    {
        $this->assertTool($tool);
        $value = $this->settingsManager->getSetting($tool.'.'.self::visibilitySetting($tool), true);

        return self::normalize($value) ?? self::DEFAULTS[$tool];
    }

    public function canBeModifiedInCourse(string $tool): bool
    {
        $this->assertTool($tool);

        return 'true' === (string) $this->settingsManager->getSetting($tool.'.'.self::courseOverrideSetting($tool), true);
    }

    /**
     * The value the course chose, or null when it chose none.
     */
    private function getCourseVisibility(string $tool, Course $course): ?string
    {
        $setting = $this->courseSettingRepository->findOneBy([
            'cId' => (int) $course->getId(),
            'variable' => self::visibilitySetting($tool),
        ]);

        return $setting instanceof CCourseSetting ? self::normalize($setting->getValue()) : null;
    }

    private static function normalize(mixed $value): ?string
    {
        $value = \is_string($value) ? strtolower(trim($value)) : '';

        return \in_array($value, [self::VISIBLE, self::INVISIBLE], true) ? $value : null;
    }

    private function assertTool(string $tool): void
    {
        if (!isset(self::DEFAULTS[$tool])) {
            throw new InvalidArgumentException(\sprintf('No default visibility is defined for the tool "%s".', $tool));
        }
    }
}
