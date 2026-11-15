<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\GradebookCategoryGradeModelGuard;
use PHPUnit\Framework\TestCase;

/**
 * Legacy Category::save() must not re-create grade-model children on every
 * save (issue #3387 leftover, #9458). This pins the same rules as
 * GradebookCategoryActionProcessor::updateGradeModel().
 */
final class GradebookCategoryGradeModelGuardTest extends TestCase
{
    public function testChildrenAreCreatedOnlyWhenTheModelIsFirstApplied(): void
    {
        $this->assertTrue(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(0, 4, 0, 0)
        );
    }

    public function testAnUnchangedModelNeverCreatesChildren(): void
    {
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, 4, 0, 0)
        );
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, 4, 3, 0)
        );
    }

    public function testExistingSubcategoriesBlockRecreationEvenIfTheModelChanges(): void
    {
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, 7, 3, 0)
        );
    }

    public function testExistingLinksBlockRecreationTheSameWayTheVuePathDoes(): void
    {
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, 7, 0, 2)
        );
    }

    public function testClearingOrOmittingTheModelDoesNotCreateChildren(): void
    {
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, 0, 0, 0)
        );
        $this->assertFalse(
            GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel(4, -1, 0, 0)
        );
    }

    public function testTheLegacySavePathKeepsTheGuard(): void
    {
        $source = (string) file_get_contents(
            __DIR__.'/../../../public/main/gradebook/lib/be/category.class.php'
        );
        $this->assertStringContainsString(
            'GradebookCategoryGradeModelGuard::shouldCreateChildrenFromModel',
            $source
        );

        // The unguarded "always insert" condition must not remain as the only gate.
        $save = $this->extractMethod($source, 'function save(');
        $this->assertNotSame('', $save);
        $this->assertStringContainsString('shouldCreateChildrenFromModel', $save);
        $add = $this->extractMethod($source, 'function add(');
        $this->assertNotSame('', $add);
        $this->assertStringNotContainsString('shouldCreateChildrenFromModel', $add);
    }

    private function extractMethod(string $source, string $signature): string
    {
        $start = strpos($source, $signature);
        if (false === $start) {
            return '';
        }
        $next = strpos($source, "\n    public function ", $start + \strlen($signature));
        if (false === $next) {
            $next = \strlen($source);
        }

        return substr($source, $start, $next - $start);
    }
}
