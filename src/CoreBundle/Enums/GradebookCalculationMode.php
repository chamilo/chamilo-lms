<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Enums;

/**
 * How a gradebook category aggregates the scores of its direct children.
 */
enum GradebookCalculationMode: string
{
    /**
     * Normalized weighted average (legacy default): grade = Σ(score/max × weight) / Σ(weight).
     */
    case WEIGHTED_AVERAGE = 'weighted_average';

    /**
     * Points sum: grade = Σ(score/max × weight), without dividing by Σ(weight).
     * Each weight is interpreted as the maximum number of points of the item.
     */
    case POINTS_SUM = 'points_sum';
}
