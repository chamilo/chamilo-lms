<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookMyStudentsLpTrackingEventInterface.
 */
interface HookMyStudentsLpTrackingEventInterface extends HookEventInterface
{
    /**
     * @return array
     */
    public function notifyTrackingHeader();

    /**
     * @param int $lpId
     * @param int $studentId
     *
     * @return array
     */
    public function notifyTrackingContent($lpId, $studentId);
}
