<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\GradeModelComponentLimit;
use PHPUnit\Framework\TestCase;

/**
 * The admin grading-model form must offer enough slots for a real scheme
 * (issue #3387 needed 26) and must still bound a crafted POST (issue #9457).
 */
final class GradeModelComponentLimitTest extends TestCase
{
    public function testTheNamedCapCoversTheTwentySixCategoriesFromIssue3387(): void
    {
        $this->assertSame(40, GradeModelComponentLimit::MAX_COMPONENTS);
        $this->assertGreaterThanOrEqual(26, GradeModelComponentLimit::MAX_COMPONENTS);
        $this->assertSame(40, GradeModelComponentLimit::slotCount());
        $this->assertSame(40, GradeModelComponentLimit::slotCount(0));
        $this->assertSame(40, GradeModelComponentLimit::slotCount(11));
        $this->assertSame(40, GradeModelComponentLimit::slotCount(26));
    }

    public function testAnExistingModelLargerThanTheCapIsNotTruncated(): void
    {
        $this->assertSame(45, GradeModelComponentLimit::slotCount(45));
    }

    public function testANegativeExistingCountDoesNotShrinkTheCap(): void
    {
        $this->assertSame(40, GradeModelComponentLimit::slotCount(-3));
    }

    public function testPostedComponentsAreCappedForANewModel(): void
    {
        $posted = [];
        for ($i = 0; $i < 80; ++$i) {
            $posted[$i] = ['title' => 'C'.$i, 'percentage' => 1, 'acronym' => 'C'.$i];
        }

        $kept = GradeModelComponentLimit::postedComponents($posted, 0);

        $this->assertCount(40, $kept);
        $this->assertSame('C0', $kept[0]['title']);
        $this->assertSame('C39', $kept[39]['title']);
    }

    public function testPostedComponentsPreserveExistingRowsAboveTheCap(): void
    {
        $posted = [];
        for ($i = 0; $i < 60; ++$i) {
            $posted[$i] = ['title' => 'C'.$i];
        }

        $kept = GradeModelComponentLimit::postedComponents($posted, 45);

        $this->assertCount(45, $kept);
        $this->assertSame('C44', $kept[44]['title']);
    }

    public function testPostedComponentsReindexSparseKeys(): void
    {
        $kept = GradeModelComponentLimit::postedComponents([
            2 => ['title' => 'A'],
            7 => ['title' => 'B'],
        ], 0);

        $this->assertSame(['A', 'B'], array_column($kept, 'title'));
    }

    public function testTheFormRendersTheNamedNumberOfSlots(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/inc/lib/grade_model.lib.php');
        $this->assertStringContainsString('GradeModelComponentLimit::slotCount', $source);
        $this->assertStringContainsString('GradeModelComponentLimit::postedComponents', $source);
        $this->assertDoesNotMatchRegularExpression('/\$max\s*=\s*10\s*;/', $source);
    }
}
