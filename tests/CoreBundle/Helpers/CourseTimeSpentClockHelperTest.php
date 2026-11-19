<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CourseTimeSpentClockHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use PHPUnit\Framework\TestCase;

class CourseTimeSpentClockHelperTest extends TestCase
{
    /**
     * Without any choice, platforms keep their historic behaviour: no clock.
     */
    public function testClockIsHiddenByDefault(): void
    {
        $helper = $this->helper(platformValue: 'false', canBeModifiedInCourse: 'false', courseValue: null);

        $this->assertFalse($helper->isEnabled($this->course()));
    }

    public function testCourseFollowsThePlatformWhenItHasNoChoice(): void
    {
        $helper = $this->helper(platformValue: 'true', canBeModifiedInCourse: 'true', courseValue: '');

        $this->assertTrue($helper->isEnabled($this->course()));
    }

    /**
     * A course can show the clock while the platform hides it, and the reverse, but only when the
     * platform lets courses choose.
     */
    public function testCourseChoiceOverridesThePlatformWhenAllowed(): void
    {
        $this->assertTrue($this->helper('false', 'true', 'true')->isEnabled($this->course()));
        $this->assertFalse($this->helper('true', 'true', 'false')->isEnabled($this->course()));
    }

    /**
     * A choice saved while courses could choose must not apply once the platform takes it back.
     */
    public function testCourseChoiceIsIgnoredWhenCoursesCannotChoose(): void
    {
        $this->assertFalse($this->helper('false', 'false', 'true')->isEnabled($this->course()));
        $this->assertTrue($this->helper('true', 'false', 'false')->isEnabled($this->course()));
    }

    public function testOnlyYesNoCourseValuesAreKept(): void
    {
        $this->assertSame('true', CourseTimeSpentClockHelper::normalizeCourseValue(' true '));
        $this->assertSame('false', CourseTimeSpentClockHelper::normalizeCourseValue('false'));
        $this->assertSame('', CourseTimeSpentClockHelper::normalizeCourseValue('1'));
        $this->assertSame('', CourseTimeSpentClockHelper::normalizeCourseValue(null));
    }

    private function helper(string $platformValue, string $canBeModifiedInCourse, ?string $courseValue): CourseTimeSpentClockHelper
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')->willReturnMap([
            ['course.'.CourseTimeSpentClockHelper::SETTING, true, $platformValue],
            ['course.'.CourseTimeSpentClockHelper::COURSE_OVERRIDE, true, $canBeModifiedInCourse],
        ]);

        $repository = $this->createMock(CCourseSettingRepository::class);
        $repository->method('findOneBy')->willReturn(
            null === $courseValue ? null : (new CCourseSetting())->setValue($courseValue),
        );

        return new CourseTimeSpentClockHelper($settingsManager, $repository);
    }

    private function course(): Course
    {
        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(1);

        return $course;
    }
}
