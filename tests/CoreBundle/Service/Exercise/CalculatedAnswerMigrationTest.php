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

    public function testMigratesAnUnusedBlankThatVariesOnEveryLineAsAnExtraDecoyVariable(): void
    {
        // Real-world case: "et 2/5/6/8" is a second number that changes on
        // every line but the formula only ever adds 1 to the first one. The
        // unused blank is auto-assigned a synthetic variable name ("z") and
        // kept randomized in the wording, without being wired into the formula.
        //
        // Known limitation (accepted, not handled): this assignment is purely
        // positional -- the first wording blank always gets the real formula
        // variable's name, the rest get synthetic names. If the *unused* blank
        // had come BEFORE the real variable in the wording instead (e.g. an
        // "age, weight" question where the formula only uses weight), the
        // names would be swapped onto the wrong ranges and the migrated
        // question would silently score using the wrong number. There is no
        // general fix for that ordering case, so it is not covered here.
        $lines = [
            '1 et 2 [2]@@[a]+1',
            '2 et 5 [3]@@[a]+1',
            '3 et 6 [4]@@[a]+1',
            '4 et 8 [5]@@[a]+1',
        ];

        $migrated = getMigratedValue($lines, 10);

        self::assertSame(
            '[#a] et [#z]  = [=result]@@@=result:a+1:0:digit:0:10;#a:1-4::0;#z:2-8::0;',
            $migrated
        );
    }

    public function testReturnsEmptyStringWhenTheLineDoesNotMatchTheLegacyShape(): void
    {
        self::assertSame('', getMigratedValue(['not a legacy calculated answer line'], 10));
    }
}
