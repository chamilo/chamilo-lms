<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

/**
 * A QTI 2.1 item label identifying Chamilo draggable matching questions.
 *
 * Standard matchInteraction alone cannot distinguish matching type 4 from draggable type 19.
 * A label is a standard optional assessmentItem attribute and can be ignored by other tools.
 */
final class Qti2MatchingTypeMarker
{
    public const DRAGGABLE_LABEL = 'chamilo:matching-draggable';

    public static function isDraggable(string $label): bool
    {
        return self::DRAGGABLE_LABEL === $label;
    }
}
