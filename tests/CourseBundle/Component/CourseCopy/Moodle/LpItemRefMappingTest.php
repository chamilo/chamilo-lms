<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

use const JSON_THROW_ON_ERROR;

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

    public function testLearnpathMetaKeepsPrerequisiteScoreRange(): void
    {
        $workDir = sys_get_temp_dir().'/chamilo-lp-prerequisite-'.bin2hex(random_bytes(6));
        $baseDir = $workDir.'/chamilo/learnpath';
        $lpDir = $baseDir.'/lp_30';
        self::assertTrue(mkdir($lpDir, 0777, true));

        try {
            file_put_contents($baseDir.'/index.json', json_encode([
                'learnpaths' => [[
                    'id' => 30,
                    'title' => 'AI Act',
                    'lp_type' => 1,
                    'category_id' => 0,
                    'dir' => 'lp_30',
                ]],
            ], JSON_THROW_ON_ERROR));
            file_put_contents($baseDir.'/categories.json', json_encode(['categories' => []], JSON_THROW_ON_ERROR));
            file_put_contents($lpDir.'/learnpath.json', json_encode([
                'learnpath' => [
                    'id' => 30,
                    'lp_type' => 1,
                    'title' => 'AI Act',
                ],
            ], JSON_THROW_ON_ERROR));
            file_put_contents($lpDir.'/items.json', json_encode([
                'items' => [
                    [
                        'id' => 105,
                        'item_type' => 'quiz',
                        'path' => '31',
                        'title' => 'Mini-test 1',
                        'display_order' => 1,
                        'prerequisite' => '',
                    ],
                    [
                        'id' => 106,
                        'item_type' => 'document',
                        'path' => '306',
                        'title' => 'Module 2',
                        'display_order' => 2,
                        'prerequisite' => '105',
                        'prerequisite_min_score' => 10,
                        'prerequisite_max_score' => 20,
                    ],
                ],
            ], JSON_THROW_ON_ERROR));

            $resources = [];
            $import = new MoodleImport(false);
            $method = (new ReflectionClass($import))->getMethod('tryImportLearnpathMeta');
            $method->setAccessible(true);
            $args = [$workDir, &$resources];

            self::assertTrue($method->invokeArgs($import, $args));
            self::assertArrayHasKey('learnpath', $resources);
            self::assertCount(1, $resources['learnpath']);

            $learnpath = reset($resources['learnpath']);
            self::assertIsObject($learnpath);
            self::assertCount(2, $learnpath->items);
            self::assertSame('105', $learnpath->items[1]['prerequisite']);
            self::assertSame(10.0, $learnpath->items[1]['prerequisite_min_score']);
            self::assertSame(20.0, $learnpath->items[1]['prerequisite_max_score']);
        } finally {
            $this->removeDirectory($workDir);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
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
