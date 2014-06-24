<?php

namespace FOS\MessageBundle\Reader;

use FOS\MessageBundle\Model\ReadableInterface;

/**
 * Marks messages and threads as read or unread
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ReaderInterface
{
    /**
     * Marks the readable as read by the current authenticated user
     *
     * @param ReadableInterface $readable
     */
    function markAsRead(ReadableInterface $readable);

    /**
     * Marks the readable as unread by the current authenticated user
     *
     * @param ReadableInterface $readable
     */
    function markAsUnread(ReadableInterface $readable);
}
