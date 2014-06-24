<?php

namespace FOS\MessageBundle\ModelManager;

use FOS\MessageBundle\Model\MessageInterface;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Interface to be implemented by message managers. This adds an additional level
 * of abstraction between your application, and the actual repository.
 *
 * All changes to messages should happen through this interface.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface MessageManagerInterface extends ReadableManagerInterface
{
    /**
     * Tells how many unread, non-spam, messages this participant has
     *
     * @param ParticipantInterface $participant
     * @return int the number of unread messages
     */
    function getNbUnreadMessageByParticipant(ParticipantInterface $participant);

    /**
     * Creates an empty message instance
     *
     * @return MessageInterface
     */
    function createMessage();

    /**
     * Saves a message
     *
     * @param MessageInterface $message
     * @param Boolean $andFlush Whether to flush the changes (default true)
     */
    function saveMessage(MessageInterface $message, $andFlush = true);

    /**
     * Returns the message's fully qualified class MessageManagerInterface.
     *
     * @return string
     */
    function getClass();
}
