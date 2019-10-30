<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookMyStudentsQuizTrackingEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookMyStudentsQuizTrackingEventInterface extends HookEventInterface
{
    /**
     * @return array
     */
    public function notifyTrackingHeader(): array;

    /**
     * @param int $quizId
     * @param int $studentId
     *
     * @return array
     */
    public function notifyTrackingContent($quizId, $studentId): array;
}
