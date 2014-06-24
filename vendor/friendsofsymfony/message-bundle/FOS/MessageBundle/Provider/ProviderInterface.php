<?php

namespace FOS\MessageBundle\Provider;

/**
 * Provides threads for the current authenticated user
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ProviderInterface
{
    /**
     * Gets the thread in the inbox of the current user
     *
     * @return array of ThreadInterface
     */
    function getInboxThreads();

    /**
     * Gets the thread in the sentbox of the current user
     *
     * @return array of ThreadInterface
     */
     function getSentThreads();

    /**
     * Gets the deleted threads of the current user
     *
     * @return ThreadInterface[]
     */
     function getDeletedThreads();

    /**
     * Gets a thread by its ID
     * Performs authorization checks
     * Marks the thread as read
     *
     * @return ThreadInterface
     */
    function getThread($threadId);

    /**
     * Tells how many unread messages the authenticated participant has
     *
     * @return int the number of unread messages
     */
    function getNbUnreadMessages();
}
