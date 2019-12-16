<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

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
     */
    public function trackingHeader(HookMyStudentsLpTrackingEventInterface $hook): array;

    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => '5 connected users ',
     *     'attrs' => ['class' => 'text-center text-success'],
     * ]
     * </code>.
     */
    public function trackingContent(HookMyStudentsLpTrackingEventInterface $hook): array;
}
