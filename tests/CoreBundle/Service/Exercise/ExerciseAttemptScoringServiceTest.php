<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptScoringService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ExerciseAttemptScoringServiceTest extends TestCase
{
    private ExerciseAttemptScoringService $service;
    private ReflectionMethod $fillBlankMatcher;

    protected function setUp(): void
    {
        $this->service = new ExerciseAttemptScoringService(
            $this->createMock(EntityManagerInterface::class),
        );
        $this->fillBlankMatcher = new ReflectionMethod(
            ExerciseAttemptScoringService::class,
            'isFillBlankStudentAnswerGood',
        );
    }

    /**
     * @dataProvider fillBlankAnswerProvider
     */
    public function testFillBlankAnswerMatching(
        string $studentAnswer,
        string $correctAnswer,
        bool $caseInsensitive,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            $this->fillBlankMatcher->invoke(
                $this->service,
                $studentAnswer,
                $correctAnswer,
                $caseInsensitive,
            ),
        );
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     */
    public static function fillBlankAnswerProvider(): iterable
    {
        yield 'plain answer trims persisted whitespace' => [' Lima ', 'Lima', false, true];

        yield 'single-pipe menu accepts first option' => ['Lima', 'Lima|Paris|Bogota', false, true];

        yield 'single-pipe menu rejects distractor' => ['Paris', 'Lima|Paris|Bogota', false, false];

        yield 'double-pipe accepts alternate answer' => ['one', '1||one||un||uno', false, true];

        yield 'plain answer decodes HTML entities from persisted answer' => ['AT&amp;T', 'AT&amp;T', false, true];

        yield 'menu answer decodes HTML entities from persisted answer' => [
            'Tom &amp; Jerry',
            'Tom &amp; Jerry|Batman &amp; Robin',
            false,
            true,
        ];

        yield 'entity answer remains case-insensitive when configured' => ['at&amp;t', 'AT&amp;T', true, true];
    }
}
