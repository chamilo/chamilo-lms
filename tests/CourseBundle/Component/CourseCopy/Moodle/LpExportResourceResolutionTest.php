<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class LpExportResourceResolutionTest extends TestCase
{
    public function testKeepsValidCurrentQuizIdAndResolvesStaleQuizAndSurveyIdsByUniqueTitle(): void
    {
        $export = $this->createExport([
            'quiz' => [
                76 => $this->quizWrap(76, 'Mini-test 1: Module 1'),
                82 => $this->quizWrap(82, 'Mini-test 2: Module 2'),
            ],
            'survey' => [
                16 => $this->surveyWrap(16, 'Satisfaction Survey'),
            ],
        ]);

        $resolve = (new ReflectionClass($export))->getMethod('resolveLpCurrentResourceId');
        $resolve->setAccessible(true);

        self::assertSame(76, $resolve->invoke($export, 'quiz', '76', 'Renamed title', false));
        self::assertSame(76, $resolve->invoke($export, 'quiz', '31', 'Mini-test 1: Module 1', false));
        self::assertSame(16, $resolve->invoke($export, 'survey', '12', 'Satisfaction Survey', false));
    }

    public function testDoesNotGuessWhenStaleTitleIsAmbiguous(): void
    {
        $export = $this->createExport([
            'quiz' => [
                76 => $this->quizWrap(76, 'Duplicated title'),
                77 => $this->quizWrap(77, 'Duplicated title'),
            ],
        ]);

        $resolve = (new ReflectionClass($export))->getMethod('resolveLpCurrentResourceId');
        $resolve->setAccessible(true);

        self::assertNull($resolve->invoke($export, 'quiz', '31', 'Duplicated title', false));
    }

    public function testActivitiesUseResolvedLpIdsWithoutDuplicatingStandaloneResources(): void
    {
        $quizTitle = 'Mini-test 1: Module 1';
        $surveyTitle = 'Satisfaction Survey';

        $export = $this->createExport([
            'quiz' => [
                76 => $this->quizWrap(76, $quizTitle),
                84 => $this->quizWrap(84, 'Standalone quiz'),
            ],
            'survey' => [
                16 => $this->surveyWrap(16, $surveyTitle),
            ],
            'learnpath' => [
                37 => $this->learnpathWrap(37, [
                    [
                        'id' => 178,
                        'item_type' => 'quiz',
                        'path' => '31',
                        'title' => $quizTitle,
                        'display_order' => 1,
                    ],
                    [
                        'id' => 190,
                        'item_type' => 'survey',
                        'path' => '12',
                        'title' => $surveyTitle,
                        'display_order' => 2,
                    ],
                ]),
            ],
        ]);

        $getActivities = (new ReflectionClass($export))->getMethod('getActivities');
        $getActivities->setAccessible(true);

        /** @var array<int,array<string,mixed>> $activities */
        $activities = $getActivities->invoke($export);

        $quizzes = array_values(array_filter(
            $activities,
            static fn (array $activity): bool => 'quiz' === ($activity['modulename'] ?? '')
        ));
        $feedbacks = array_values(array_filter(
            $activities,
            static fn (array $activity): bool => 'feedback' === ($activity['modulename'] ?? '')
        ));

        self::assertCount(2, $quizzes);
        self::assertSame(76, $quizzes[0]['id']);
        self::assertSame(37, $quizzes[0]['sectionid']);
        self::assertSame(900000178, $quizzes[0]['moduleid']);
        self::assertSame(84, $quizzes[1]['id']);
        self::assertSame(0, $quizzes[1]['sectionid']);

        self::assertCount(1, $feedbacks);
        self::assertSame(16, $feedbacks[0]['id']);
        self::assertSame(37, $feedbacks[0]['sectionid']);
        self::assertSame(900000190, $feedbacks[0]['moduleid']);
    }

    public function testStaleLpReferenceStillMarksCurrentQuizAndSurveyAsLinked(): void
    {
        $quizTitle = 'Mini-test 1: Module 1';
        $surveyTitle = 'Satisfaction Survey';

        $export = $this->createExport([
            'quiz' => [
                76 => $this->quizWrap(76, $quizTitle),
            ],
            'survey' => [
                16 => $this->surveyWrap(16, $surveyTitle),
            ],
            'learnpath' => [
                37 => $this->learnpathWrap(37, [
                    [
                        'id' => 178,
                        'item_type' => 'quiz',
                        'path' => '31',
                        'title' => $quizTitle,
                    ],
                    [
                        'id' => 190,
                        'item_type' => 'survey',
                        'path' => '12',
                        'title' => $surveyTitle,
                    ],
                ]),
            ],
        ]);

        $linked = (new ReflectionClass($export))->getMethod('isActivityInLearnpath');
        $linked->setAccessible(true);

        self::assertTrue($linked->invoke($export, 'quiz', 76));
        self::assertTrue($linked->invoke($export, 'survey', 16));
        self::assertFalse($linked->invoke($export, 'quiz', 999));
    }

    /**
     * @param array<string,array<int,stdClass>> $resources
     */
    private function createExport(array $resources): MoodleExport
    {
        $ref = new ReflectionClass(MoodleExport::class);

        /** @var MoodleExport $export */
        $export = $ref->newInstanceWithoutConstructor();

        $course = new stdClass();
        $course->resources = $resources;

        $property = $ref->getProperty('course');
        $property->setAccessible(true);
        $property->setValue($export, $course);

        return $export;
    }

    private function quizWrap(int $id, string $title): stdClass
    {
        $wrap = new stdClass();
        $wrap->source_id = $id;
        $wrap->obj = (object) [
            'iid' => $id,
            'id' => $id,
            'title' => $title,
        ];

        return $wrap;
    }

    private function surveyWrap(int $id, string $title): stdClass
    {
        $wrap = new stdClass();
        $wrap->source_id = $id;
        $wrap->title = $title;
        $wrap->obj = (object) [
            'iid' => $id,
            'id' => $id,
            'title' => $title,
        ];

        return $wrap;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     */
    private function learnpathWrap(int $id, array $items): stdClass
    {
        $wrap = new stdClass();
        $wrap->source_id = $id;
        $wrap->obj = (object) [
            'id' => $id,
            'source_id' => $id,
            'lp_type' => 1,
            'items' => $items,
        ];

        return $wrap;
    }
}
