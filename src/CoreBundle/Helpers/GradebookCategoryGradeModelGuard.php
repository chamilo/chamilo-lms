<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

/**
 * Whether the legacy Category::save() path should insert child categories
 * from a grading model. Mirrors the Vue guard in
 * GradebookCategoryActionProcessor::updateGradeModel() (issue #9458,
 * leftover of #3387): only create children when the model actually changed
 * and the root has no subcategories (or links) yet.
 */
final class GradebookCategoryGradeModelGuard
{
    public static function shouldCreateChildrenFromModel(
        int $currentGradeModelId,
        int $targetGradeModelId,
        int $existingSubcategoryCount,
        int $existingLinkCount = 0,
    ): bool {
        if ($targetGradeModelId <= 0) {
            return false;
        }

        if ($currentGradeModelId === $targetGradeModelId) {
            return false;
        }

        if ($existingSubcategoryCount > 0 || $existingLinkCount > 0) {
            return false;
        }

        return true;
    }
}
