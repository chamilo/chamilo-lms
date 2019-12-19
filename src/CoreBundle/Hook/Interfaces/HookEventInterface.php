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
     * @return static
     */
    public static function create(EntityManager $entityManager);

    /**
     * Return an array containing all data needed by the hook observer to update.
     */
    public function getEventData(): array;

    /**
     * Set an array with data needed by hooks.
     */
    public function setEventData(array $data): self;

    /**
     * Return the event name refer to where hook is used.
     */
    public function getEventName(): string;

    /**
     * Clear all hookObservers without detach them.
     */
    public function clearAttachments();

    /**
     * Detach all hook observers.
     */
    public function detachAll(): self;

    /**
     * Get the entity manager used.
     */
    public function getEntityManager(): ? EntityManager;
}
