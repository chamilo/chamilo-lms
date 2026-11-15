<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

/**
 * Upper bound on the number of components in a grading model.
 *
 * The admin form used to hard-code `$max = 10` and loop `$i <= $max`, which
 * only produced 11 slots (issue #3387, leftover #9457). The original report
 * needed 26 categories. 40 covers that with headroom for a weekly-or-similar
 * scheme, and is a hard bound so a crafted POST cannot create unbounded rows.
 *
 * If a model already has more components than the cap (legacy data), the
 * form and the persist filter expand to that existing count so rows are not
 * silently dropped. New rows are still limited to MAX_COMPONENTS.
 */
final class GradeModelComponentLimit
{
    public const MAX_COMPONENTS = 40;

    public static function slotCount(int $existingComponentCount = 0): int
    {
        return max(self::MAX_COMPONENTS, max(0, $existingComponentCount));
    }

    /**
     * Keep only as many posted component rows as the form is allowed to show.
     *
     * @param array<array-key, mixed> $posted
     *
     * @return list<mixed>
     */
    public static function postedComponents(array $posted, int $existingComponentCount = 0): array
    {
        return \array_slice(array_values($posted), 0, self::slotCount($existingComponentCount));
    }
}
