<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Exercise;

use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeResultProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * A menu blank cut from formatted text keeps its formatting tags in its options, e.g.
 * [haben&nbsp;<font size="2">|</font> machen] gives "haben <font size="2">". The legacy page
 * showed "haben", the browser dropping the tags; the result page showed the tags as text.
 */
final class ExerciseResultFillBlankDisplayTest extends TestCase
{
    /**
     * @dataProvider answerProvider
     */
    public function testTheAnswerIsShownWithoutItsFormattingTags(string $method, string $saved, string $expected): void
    {
        $provider = (new ReflectionClass(ExerciseRuntimeResultProvider::class))->newInstanceWithoutConstructor();

        self::assertSame($expected, (new ReflectionMethod($provider, $method))->invoke($provider, $saved));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function answerProvider(): iterable
    {
        // The menu of the question 14231 of the report, as the teacher saved it
        $menu = 'haben&nbsp;<font face="Verdana, sans-serif"><font size="2">|</font></font> machen';

        yield 'the expected option of the menu' => ['resolveFillBlankCorrectAnswerDisplay', $menu, 'haben'];

        yield "the learner's choice, saved escaped" => [
            'trimFillBlankOption',
            'haben &lt;font face=&quot;Verdana, sans-serif&quot;&gt;&lt;font size=&quot;2&quot;&gt;',
            'haben',
        ];

        yield 'an answer without tags is unchanged' => ['trimFillBlankOption', 'Pr&auml;teritum', 'Präteritum'];
    }
}
