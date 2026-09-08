<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto\Gradebook;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Everything the detailed learner report reads from the query string, built by
 * GradebookCriteriaFactory.
 *
 * A userId of 0 means "the current user": the export controller iterates over a
 * course and names each learner instead.
 *
 * Excluded from the service container: a constructor of scalars is nothing
 * autowiring can supply.
 */
#[Exclude]
final readonly class GradebookLearnerReportCriteria
{
    public function __construct(
        public int $node,
        public int $categoryId,
        public int $userId,
    ) {}
}
