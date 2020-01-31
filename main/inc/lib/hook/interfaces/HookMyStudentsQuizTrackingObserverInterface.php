<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookMyStudentsQuizTrackingObserverInterface.
 */
interface HookMyStudentsQuizTrackingObserverInterface extends HookObserverInterface
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
    public function trackingHeader(HookMyStudentsQuizTrackingEventInterface $hook);

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
    public function trackingContent(HookMyStudentsQuizTrackingEventInterface $hook);
}
