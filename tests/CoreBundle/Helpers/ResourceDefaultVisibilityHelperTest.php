<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\ResourceDefaultVisibilityHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * A teacher's new document, test or learning path must get the visibility the administrator
 * chose, and a course may only replace that choice when the administrator allows it.
 */
final class ResourceDefaultVisibilityHelperTest extends TestCase
{
    private const int COURSE_ID = 23;

    public function testWithoutAnyStoredValueLearningPathsAreCreatedInvisibleAndTheRestVisible(): void
    {
        $helper = $this->helper([], []);

        self::assertSame(ResourceLink::VISIBILITY_DRAFT, $helper->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::LEARNING_PATH, $this->course()));
        self::assertSame(ResourceLink::VISIBILITY_PUBLISHED, $helper->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::DOCUMENT, $this->course()));
        self::assertSame(ResourceLink::VISIBILITY_PUBLISHED, $helper->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::EXERCISE, $this->course()));
    }

    public function testThePlatformValueApplies(): void
    {
        $helper = $this->helper(['exercise.exercise_default_visibility_on_creation' => 'invisible'], []);

        self::assertSame(ResourceLink::VISIBILITY_DRAFT, $helper->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::EXERCISE, $this->course()));
    }

    public function testACourseChoiceIsIgnoredWhileTheAdministratorDoesNotAllowIt(): void
    {
        $helper = $this->helper(
            ['lp.lp_default_visibility_can_be_modified_in_course' => 'false'],
            ['lp_default_visibility_on_creation' => 'visible'],
        );

        self::assertSame(ResourceDefaultVisibilityHelper::INVISIBLE, $helper->getVisibility(ResourceDefaultVisibilityHelper::LEARNING_PATH, $this->course()));
    }

    public function testACourseChoiceReplacesThePlatformValueWhenAllowed(): void
    {
        $helper = $this->helper(
            ['lp.lp_default_visibility_can_be_modified_in_course' => 'true'],
            ['lp_default_visibility_on_creation' => 'visible'],
        );

        self::assertSame(ResourceLink::VISIBILITY_PUBLISHED, $helper->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::LEARNING_PATH, $this->course()));
    }

    public function testACourseWithoutAChoiceFollowsThePlatform(): void
    {
        $helper = $this->helper(
            [
                'document.document_default_visibility_on_creation' => 'invisible',
                'document.document_default_visibility_can_be_modified_in_course' => 'true',
            ],
            [],
        );

        self::assertSame(ResourceDefaultVisibilityHelper::INVISIBLE, $helper->getVisibility(ResourceDefaultVisibilityHelper::DOCUMENT, $this->course()));
    }

    public function testAnUnknownStoredValueFallsBackToTheToolDefault(): void
    {
        $helper = $this->helper(
            [
                'document.document_default_visibility_on_creation' => 'hidden',
                'document.document_default_visibility_can_be_modified_in_course' => 'true',
            ],
            ['document_default_visibility_on_creation' => '-1'],
        );

        self::assertSame(ResourceDefaultVisibilityHelper::VISIBLE, $helper->getVisibility(ResourceDefaultVisibilityHelper::DOCUMENT, $this->course()));
    }

    public function testAToolWithoutDefaultVisibilityIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->helper([], [])->getVisibility('forum');
    }

    /**
     * @param array<string, string> $platformSettings
     * @param array<string, string> $courseSettings
     */
    private function helper(array $platformSettings, array $courseSettings): ResourceDefaultVisibilityHelper
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')->willReturnCallback(
            static fn (string $name): ?string => $platformSettings[$name] ?? null
        );

        $repository = $this->createMock(CCourseSettingRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static function (array $criteria) use ($courseSettings): ?CCourseSetting {
                if (self::COURSE_ID !== $criteria['cId'] || !isset($courseSettings[$criteria['variable']])) {
                    return null;
                }

                return (new CCourseSetting())
                    ->setCId(self::COURSE_ID)
                    ->setVariable($criteria['variable'])
                    ->setValue($courseSettings[$criteria['variable']])
                ;
            }
        );

        return new ResourceDefaultVisibilityHelper($settingsManager, $repository);
    }

    private function course(): Course
    {
        $course = $this->createMock(Course::class);
        $course->method('getId')->willReturn(self::COURSE_ID);

        return $course;
    }
}
