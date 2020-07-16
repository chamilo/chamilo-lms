<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookMyStudentsLpTrackingObserverInterface.
 */
interface HookMyStudentsLpTrackingObserverInterface extends HookObserverInterface
{
    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => 'Users online',
     *     'attrs' => ['class' => 'text-center'],
     * ]
     * </code>.
     *
     * @return array
     */
    public function trackingHeader(HookMyStudentsLpTrackingEventInterface $hook);

    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => '5 connected users ',
     *     'attrs' => ['class' => 'text-center text-success'],
     * ]
     * </code>.
     *
     * @return array
     */
    public function trackingContent(HookMyStudentsLpTrackingEventInterface $hook);
}
