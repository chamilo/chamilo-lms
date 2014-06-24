<?php

namespace FOS\MessageBundle\Deleter;

use FOS\MessageBundle\Model\ThreadInterface;

/**
 * Marks threads as deleted
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface DeleterInterface
{
    /**
     * Marks the thread as deleted by the current authenticated user
     *
     * @param ThreadInterface $thread
     */
    function markAsDeleted(ThreadInterface $thread);

    /**
     * Marks the thread as undeleted by the current authenticated user
     *
     * @param ThreadInterface $thread
     */
    function markAsUndeleted(ThreadInterface $thread);
}
