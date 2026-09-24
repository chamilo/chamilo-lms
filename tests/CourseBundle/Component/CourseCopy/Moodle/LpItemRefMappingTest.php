<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    public function testMapLpItemRefPrefersResourcePathOverCollidingLpLocalRef(): void
    {
        $import = new MoodleImport(false);
        $ref = new ReflectionClass($import);
        $buildIdx = $ref->getMethod('buildResourceIndexes');
        $buildIdx->setAccessible(true);
        $mapRef = $ref->getMethod('mapLpItemRef');
        $mapRef->setAccessible(true);

        $resources = [
            'document' => [
                // Sequential import key 5 collides with the LP-local ref=5.
                5 => $this->wrap(5, [
                    'title' => 'Learning path folder',
                    'path' => '/document/learning_path/AI Act',
                    'file_type' => 'folder',
                    'source_id' => 5,
                ]),
                7 => $this->wrap(7, [
                    'title' => 'Module 0',
                    'path' => '/document/Learning paths/AI Act/Module 0',
                    'file_type' => 'file',
                    'source_id' => 626,
                ]),
                8 => $this->wrap(8, [
                    'title' => 'sample-5mb.mp4',
                    'path' => '/document/learning_path/AI Act/sample-5mb.mp4',
                    'file_type' => 'file',
                    'source_id' => 653,
                ]),
            ],
        ];

        $idx = $buildIdx->invoke($import, $resources);

        $docMapped = $mapRef->invoke($import, [
            'item_type' => 'document',
            'ref' => '5',
            'path' => '626',
            'identifierref' => '626',
            'title' => 'Module 0',
        ], $idx, $resources);
        self::assertSame(7, $docMapped);

        $videoMapped = $mapRef->invoke($import, [
            'item_type' => 'video',
            'ref' => '',
            'path' => '653',
            'identifierref' => '653',
            'title' => 'sample-5mb.mp4',
        ], $idx, $resources);
        self::assertSame(8, $videoMapped);
    }

    public function testMapLpItemRefResolvesLinkByOriginalSourceId(): void
    {
        $import = new MoodleImport(false);
        $ref = new ReflectionClass($import);
        $buildIdx = $ref->getMethod('buildResourceIndexes');
        $buildIdx->setAccessible(true);
        $mapRef = $ref->getMethod('mapLpItemRef');
        $mapRef->setAccessible(true);

        $resources = [
            'link' => [
                1 => $this->wrap(1, [
                    'title' => 'Video youtube',
                    'url' => 'https://www.youtube.com/watch?v=PHc4FVCgCnY',
                    'source_id' => 31,
                    'source_activity_id' => 31,
                    'source_moduleid' => 31,
                ]),
            ],
        ];

        $idx = $buildIdx->invoke($import, $resources);

        $mapped = $mapRef->invoke($import, [
            'item_type' => 'link',
            'ref' => '',
            'path' => '31',
            'identifierref' => '31',
            'title' => 'Video youtube',
        ], $idx, $resources);

        self::assertSame(1, $mapped);
    }

    public function testReadUrlModuleKeepsOriginalActivityIdentifiers(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'chamilo-url-');
        self::assertNotFalse($tmp);

        try {
            file_put_contents(
                $tmp,
                <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<activity id="31" moduleid="31" modulename="url" contextid="72">
  <sectionid>64</sectionid>
  <url id="31">
    <name>Video youtube</name>
    <intro></intro>
    <externalurl>https://www.youtube.com/watch?v=PHc4FVCgCnY</externalurl>
  </url>
</activity>
XML
            );

            $import = new MoodleImport(false);
            $method = (new ReflectionClass($import))->getMethod('readUrlModule');
            $method->setAccessible(true);
            $data = $method->invoke($import, $tmp);

            self::assertSame('Video youtube', $data['name']);
            self::assertSame('https://www.youtube.com/watch?v=PHc4FVCgCnY', $data['url']);
            self::assertSame(31, $data['source_id']);
            self::assertSame(31, $data['source_activity_id']);
            self::assertSame(31, $data['source_moduleid']);
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
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

    public function testLearnpathSidecarKeepsLevelForOmittedRootCompatibility(): void
    {
        $tmp = sys_get_temp_dir().'/chamilo-lp-sidecar-'.bin2hex(random_bytes(6));
        $lpDir = $tmp.'/chamilo/learnpath/lp_299';
        self::assertTrue(mkdir($lpDir, 0777, true));

        try {
            file_put_contents(
                $tmp.'/chamilo/learnpath/index.json',
                json_encode([
                    'learnpaths' => [[
                        'id' => 299,
                        'title' => 'Legacy LP',
                        'lp_type' => 1,
                        'category_id' => 0,
                        'dir' => 'lp_299',
                    ]],
                ], JSON_THROW_ON_ERROR)
            );
            file_put_contents(
                $tmp.'/chamilo/learnpath/categories.json',
                json_encode(['categories' => []], JSON_THROW_ON_ERROR)
            );
            file_put_contents(
                $lpDir.'/learnpath.json',
                json_encode([
                    'learnpath' => [
                        'id' => 299,
                        'title' => 'Legacy LP',
                        'lp_type' => 1,
                        'category_id' => 0,
                    ],
                ], JSON_THROW_ON_ERROR)
            );
            file_put_contents(
                $lpDir.'/items.json',
                json_encode([
                    'items' => [
                        [
                            'id' => 639,
                            'item_type' => 'final_item',
                            'title' => 'Final item',
                            'path' => '400',
                            'identifierref' => '400',
                            'parent_item_id' => 637,
                            'display_order' => 0,
                            'level' => 1,
                        ],
                        [
                            'id' => 638,
                            'item_type' => 'quiz',
                            'title' => 'Quiz item',
                            'path' => '38',
                            'identifierref' => '38',
                            'parent_item_id' => 637,
                            'display_order' => 2,
                            'level' => 1,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR)
            );

            $import = new MoodleImport(false);
            $reflection = new ReflectionClass($import);
            $method = $reflection->getMethod('tryImportLearnpathMeta');
            $method->setAccessible(true);

            $resources = [
                'learnpath' => [],
                'learnpath_category' => [],
                'quiz' => [],
                'quizzes' => [],
                'document' => [],
                'link' => [],
                'forum' => [],
                'survey' => [],
                'surveys' => [],
                'works' => [],
                'scorm' => [],
                'scorm_documents' => [],
            ];

            self::assertTrue($method->invokeArgs($import, [$tmp, &$resources]));
            self::assertCount(1, $resources['learnpath']);

            $lp = reset($resources['learnpath']);
            self::assertIsObject($lp);
            self::assertCount(2, $lp->items);
            self::assertSame(1, $lp->items[0]['level']);
            self::assertSame(1, $lp->items[1]['level']);
            self::assertSame(637, $lp->items[0]['parent_item_id']);
            self::assertSame(637, $lp->items[1]['parent_item_id']);
        } finally {
            if (is_dir($tmp)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $entry) {
                    if ($entry->isDir()) {
                        rmdir($entry->getPathname());
                    } else {
                        unlink($entry->getPathname());
                    }
                }
                rmdir($tmp);
            }
        }
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
