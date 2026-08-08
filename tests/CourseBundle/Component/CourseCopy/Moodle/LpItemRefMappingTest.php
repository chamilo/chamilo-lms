<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Learning-path items store source resource iids in path/identifierref.
 * After Moodle import, bags are often keyed by sequential local ids with source_id
 * holding the original. Mapping must resolve path→bag key before restore.
 */
final class LpItemRefMappingTest extends TestCase
{
    public function testMapLpItemRefResolvesQuizSurveyDocumentBySourceId(): void
    {
        $import = new MoodleImport(false);
        $ref = new ReflectionClass($import);
        $buildIdx = $ref->getMethod('buildResourceIndexes');
        $buildIdx->setAccessible(true);
        $mapRef = $ref->getMethod('mapLpItemRef');
        $mapRef->setAccessible(true);

        $resources = [
            // Bag key 1, original Chamilo quiz iid 31
            'quizzes' => [
                1 => $this->wrap(1, [
                    'title' => 'Mini-test 1',
                    'source_id' => 31,
                    'id' => 31,
                ]),
            ],
            // Bag key 2, original survey iid 12
            'surveys' => [
                2 => $this->wrap(2, [
                    'title' => 'Satisfaction Survey',
                    'source_id' => 12,
                    'source_moduleid' => 12,
                ]),
            ],
            // Bag key 5, original document iid 311
            'document' => [
                5 => $this->wrap(5, [
                    'title' => 'Module 0',
                    'path' => '/document/Learning paths/Module 0',
                    'source_id' => 311,
                ]),
            ],
        ];

        $idx = $buildIdx->invoke($import, $resources);

        $quizMapped = $mapRef->invoke($import, [
            'item_type' => 'quiz',
            'path' => '31',
            'identifierref' => '31',
            'ref' => '',
            'title' => 'Mini-test 1',
        ], $idx, $resources);
        self::assertSame(1, $quizMapped);

        $surveyMapped = $mapRef->invoke($import, [
            'item_type' => 'survey',
            'path' => '12',
            'identifierref' => '12',
            'ref' => '',
            'title' => 'Satisfaction Survey',
        ], $idx, $resources);
        self::assertSame(2, $surveyMapped);

        $docMapped = $mapRef->invoke($import, [
            'item_type' => 'document',
            'path' => '311',
            'identifierref' => '311',
            'ref' => '',
            'title' => 'Module 0',
        ], $idx, $resources);
        self::assertSame(5, $docMapped);
    }

    public function testCanonicalizeMergesSurveysAliasIntoResourceSurvey(): void
    {
        $import = new MoodleImport(false);
        $ref = new ReflectionClass($import);
        $m = $ref->getMethod('canonicalizeResourceBags');
        $m->setAccessible(true);

        $res = [
            'surveys' => [
                1 => $this->wrap(1, ['title' => 'S', 'source_id' => 12]),
            ],
            'quizzes' => [
                3 => $this->wrap(3, ['title' => 'Q', 'source_id' => 31]),
            ],
        ];

        $out = $m->invoke($import, $res);

        self::assertArrayHasKey('survey', $out);
        self::assertArrayNotHasKey('surveys', $out);
        self::assertArrayHasKey(1, $out['survey']);
        self::assertSame(12, (int) $out['survey'][1]->source_id);

        self::assertArrayHasKey('quiz', $out);
        self::assertArrayNotHasKey('quizzes', $out);
        self::assertSame(31, (int) $out['quiz'][3]->source_id);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function wrap(int $sourceId, array $payload): stdClass
    {
        $o = new stdClass();
        $o->type = 'x';
        $o->source_id = (int) ($payload['source_id'] ?? $sourceId);
        $o->destination_id = null;
        $o->has_obj = true;
        $o->obj = (object) $payload;
        foreach ($payload as $k => $v) {
            if (\is_scalar($v) || null === $v) {
                $o->{$k} = $v;
            }
        }

        return $o;
    }
}
