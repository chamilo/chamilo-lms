<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

use Doctrine\ORM\EntityManager;

/**
 * Interface HookEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookEventInterface
{
    /**
     * Attach an HookObserver.
     *
     * @see http://php.net/manual/en/splsubject.attach.php
     *
     * @param HookObserverInterface $observer <p>
     *                                        The <b>HookObserver</b> to attach.
     *                                        </p>
     */
    public function attach(HookObserverInterface $observer);

    /**
     * Detach an HookObserver.
     *
     * @see http://php.net/manual/en/splsubject.detach.php
     *
     * @param HookObserverInterface $observer <p>
     *                                        The <b>HookObserver</b> to detach.
     *                                        </p>
     */
    public function detach(HookObserverInterface $observer);

    /**
     * Return the singleton instance of Hook event.
     *
     * @param EntityManager $entityManager
     *
     * @return static
     */
    public static function create(EntityManager $entityManager);

    /**
     * Return an array containing all data needed by the hook observer to update.
     *
     * @return array
     */
    public function getEventData(): array;

    /**
     * Set an array with data needed by hooks.
     *
     * @param array $data
     *
     * @return HookEventInterface
     */
    public function setEventData(array $data): HookEventInterface;

    /**
     * Return the event name refer to where hook is used.
     *
     * @return string
     */
    public function getEventName(): string;

    /**
     * Clear all hookObservers without detach them.
     *
     * @return mixed
     */
    public function clearAttachments();

    /**
     * Detach all hook observers.
     *
     * @return HookEventInterface
     */
    public function detachAll(): HookEventInterface;

    /**
     * Get the entity manager used.
     *
     * @return EntityManager|null
     */
    public function getEntityManager(): ? EntityManager;
}
