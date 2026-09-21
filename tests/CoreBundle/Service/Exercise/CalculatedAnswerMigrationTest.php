<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../../public/main/exercise/calculated_answer_migration.inc.php';

/**
 * Covers getMigratedValue() against the legacy CalculatedAnswer engine's real
 * save format (traced from the pre-rewrite calculated_answer.class.php):
 * each stored answer line is "<wording with random values substituted> [result]@@<original formula with named [x] placeholders>",
 * one line per answerVariations entry.
 */
final class CalculatedAnswerMigrationTest extends TestCase
{
    public function testMigratesASimpleTwoVariableFormula(): void
    {
        $lines = [
            '<p>3 apples and 4 oranges</p> [7]@@[x]+[y]',
            '<p>5 apples and 6 oranges</p> [11]@@[x]+[y]',
        ];

        $migrated = getMigratedValue($lines, 10);

        self::assertSame(
            '<p>[#x] apples and [#y] oranges</p>  = [=result]@@@=result:x+y:0:digit:0:10;#x:3-5::0;#y:4-6::0;',
            $migrated
        );
    }

    public function testReusesTheSameVariableWhenTheWordingRepeatsIt(): void
    {
        // Legacy behaviour: the same bracket name used twice in the wording
        // ("[x] plus [x]") is substituted with the SAME random value both
        // times (str_replace does a global replace), so the stored line
        // holds the identical number at both positions.
        $lines = [
            '<p>3 plus 3 equals result</p> [6]@@[x]+[x]',
            '<p>7 plus 7 equals result</p> [14]@@[x]+[x]',
        ];

        $migrated = getMigratedValue($lines, 10);

        // Before the fix, the second occurrence ran past the end of the
        // (single-entry) variable list and produced a broken "[#]" blank
        // with an empty variable name.
        self::assertSame(
            '<p>[#x] plus [#x] equals result</p>  = [=result]@@@=result:x+x:0:digit:0:10;#x:3-7::0;',
            $migrated
        );
    }

    public function testKeepsALiteralNumberThatNeverChangesAsAConstant(): void
    {
        // "20" is fixed wording text (never randomized), unrelated to the
        // formula's own literal "+20" -- only [x] is an actual blank.
        $lines = [
            'Take 5 and add the fixed value 20 to get a total. [25]@@[x]+20',
            'Take 8 and add the fixed value 20 to get a total. [28]@@[x]+20',
        ];

        $migrated = getMigratedValue($lines, 10);

        self::assertSame(
            'Take [#x] and add the fixed value 20 to get a total.  = [=result]@@@=result:x+20:0:digit:0:10;#x:5-8::0;',
            $migrated
        );
    }

    public function testSkipsWhenAWordingBlankIsNeverUsedByTheFormula(): void
    {
        // "age" varies independently in the wording but the formula never
        // references it -- the original (substituted) bracket name is lost,
        // so there is no safe way to tell this apart from a genuine second
        // formula variable. Guessing an assignment risks silently computing
        // the wrong result, so this must be left for manual review instead.
        $lines = [
            'You are 25 years old and weigh 70 kg. [140]@@[weight]*2',
            'You are 30 years old and weigh 80 kg. [160]@@[weight]*2',
        ];

        self::assertSame('', getMigratedValue($lines, 10));
    }

    public function testReturnsEmptyStringWhenTheLineDoesNotMatchTheLegacyShape(): void
    {
        self::assertSame('', getMigratedValue(['not a legacy calculated answer line'], 10));
    }
}
