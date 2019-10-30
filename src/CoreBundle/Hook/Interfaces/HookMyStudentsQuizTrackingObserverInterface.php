<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookMyStudentsQuizTrackingObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
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
     * @param HookMyStudentsQuizTrackingEventInterface $hook
     *
     * @return array
     */
    public function trackingHeader(HookMyStudentsQuizTrackingEventInterface $hook): array;

    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => '5 connected users ',
     *     'attrs' => ['class' => 'text-center text-success'],
     * ]
     * </code>.
     *
     * @param HookMyStudentsQuizTrackingEventInterface $hook
     *
     * @return array
     */
    public function trackingContent(HookMyStudentsQuizTrackingEventInterface $hook): array;
}
