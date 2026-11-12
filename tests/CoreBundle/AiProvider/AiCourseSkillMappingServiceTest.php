<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\AiProvider;

use Chamilo\CoreBundle\AiProvider\AiCourseSkillMappingService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AiCourseSkillMappingServiceTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            ['id' => 1, 'title' => 'Root', 'description' => '', 'status' => 1, 'acquirable' => false],
            ['id' => 2, 'title' => 'Spreadsheet formulas', 'description' => 'Use formulas', 'status' => 1, 'acquirable' => true],
            ['id' => 3, 'title' => 'Disabled skill', 'description' => '', 'status' => 0, 'acquirable' => true],
        ];
    }

    private function mapper(): AiCourseSkillMappingService
    {
        // Normalization does not use dependencies and must remain safe without DB or an AI provider.
        return (new ReflectionClass(AiCourseSkillMappingService::class))->newInstanceWithoutConstructor();
    }

    public function testExistingSuggestionUsesRealSkillDetails(): void
    {
        $result = $this->mapper()->normalizeSuggestions([
            'suggestions' => [
                ['kind' => 'existing', 'skillId' => 2, 'title' => 'Fake title', 'reason' => 'Covered by the course'],
            ],
        ], $this->catalogue());

        self::assertCount(1, $result);
        self::assertSame('Spreadsheet formulas', $result[0]['title']);
        self::assertSame(2, $result[0]['skillId']);
    }

    public function testUnknownDisabledAndNonAcquirableSkillsAreRejected(): void
    {
        $result = $this->mapper()->normalizeSuggestions([
            'suggestions' => [
                ['kind' => 'existing', 'skillId' => 999],
                ['kind' => 'existing', 'skillId' => 3],
                ['kind' => 'existing', 'skillId' => 1],
            ],
        ], $this->catalogue());

        self::assertSame([], $result);
    }

    public function testNewSkillRequiresExistingEnabledParent(): void
    {
        $result = $this->mapper()->normalizeSuggestions([
            'suggestions' => [
                ['kind' => 'new', 'parentId' => 999, 'title' => 'Unknown parent'],
                ['kind' => 'new', 'parentId' => 3, 'title' => 'Disabled parent'],
                ['kind' => 'new', 'parentId' => 1, 'title' => 'Data visualization', 'reason' => 'Relevant'],
            ],
        ], $this->catalogue());

        self::assertCount(1, $result);
        self::assertSame('Data visualization', $result[0]['title']);
        self::assertSame(1, $result[0]['parentId']);
        self::assertSame('Root', $result[0]['parentTitle']);
    }

    public function testDuplicateExistingAndNewSkillTitlesAreRejected(): void
    {
        $result = $this->mapper()->normalizeSuggestions([
            'suggestions' => [
                ['kind' => 'existing', 'skillId' => 2],
                ['kind' => 'existing', 'skillId' => 2],
                ['kind' => 'new', 'parentId' => 1, 'title' => ' spreadsheet FORMULAS '],
                ['kind' => 'new', 'parentId' => 1, 'title' => 'New skill'],
                ['kind' => 'new', 'parentId' => 1, 'title' => 'new SKILL'],
            ],
        ], $this->catalogue());

        self::assertCount(2, $result);
        self::assertSame('existing', $result[0]['kind']);
        self::assertSame('new', $result[1]['kind']);
    }

    public function testMalformedSuggestionsAreIgnored(): void
    {
        self::assertSame([], $this->mapper()->normalizeSuggestions(['suggestions' => 'not a list'], $this->catalogue()));
        self::assertSame([], $this->mapper()->normalizeSuggestions(['suggestions' => [['kind' => 'unknown']]], $this->catalogue()));
    }

    public function testSuggestionCountIsBounded(): void
    {
        $rows = [];
        for ($i = 0; $i < 30; ++$i) {
            $rows[] = ['kind' => 'new', 'parentId' => 1, 'title' => 'Skill '.$i];
        }

        self::assertCount(12, $this->mapper()->normalizeSuggestions(['suggestions' => $rows], $this->catalogue()));
    }

    public function testCourseWithNoPublishedContentCannotBeMapped(): void
    {
        $mapper = $this->mapper();
        $method = (new ReflectionClass(AiCourseSkillMappingService::class))->getMethod('hasCourseMaterial');
        self::assertFalse($method->invoke($mapper, [
            'course' => ['description' => ''],
            'lessons' => [],
            'standaloneDocuments' => [],
            'standaloneExercises' => [],
            'assignments' => [],
            'surveys' => [],
        ]));
    }

    public function testStandaloneDocumentsAndExercisesCountAsCourseMaterial(): void
    {
        $mapper = $this->mapper();
        $method = (new ReflectionClass(AiCourseSkillMappingService::class))->getMethod('hasCourseMaterial');
        self::assertTrue($method->invoke($mapper, [
            'course' => ['description' => ''],
            'lessons' => [],
            'standaloneDocuments' => [['id' => 12]],
            'standaloneExercises' => [],
            'assignments' => [],
            'surveys' => [],
        ]));
        self::assertTrue($method->invoke($mapper, [
            'course' => ['description' => ''],
            'lessons' => [],
            'standaloneDocuments' => [],
            'standaloneExercises' => [['id' => 15]],
            'assignments' => [],
            'surveys' => [],
        ]));
        self::assertTrue($method->invoke($mapper, [
            'course' => ['description' => 'Covers practical AI model integration.'],
        ]));
    }
}
