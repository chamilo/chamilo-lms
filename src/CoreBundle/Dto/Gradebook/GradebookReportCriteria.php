<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto\Gradebook;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Everything the Gradebook report reads from the query string, already clamped
 * and normalized by GradebookCriteriaFactory.
 *
 * Excluded from the service container: a constructor of scalars is nothing
 * autowiring can supply.
 */
#[Exclude]
final readonly class GradebookReportCriteria
{
    public function __construct(
        public int $node,
        public int $categoryId,
        public int $page,
        public int $itemsPerPage,
        public string $search,
        public string $sortBy,
        public string $sortDirection,
        public bool $exportAll,
        public bool $includeScores,
    ) {}
}
