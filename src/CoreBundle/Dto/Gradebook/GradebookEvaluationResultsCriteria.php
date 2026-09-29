<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto\Gradebook;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Everything the manual evaluation result table reads from the query string,
 * built by GradebookCriteriaFactory.
 *
 * Excluded from the service container: a constructor of scalars is nothing
 * autowiring can supply.
 */
#[Exclude]
final readonly class GradebookEvaluationResultsCriteria
{
    public function __construct(
        public int $node,
        public int $evaluationId,
    ) {}
}
