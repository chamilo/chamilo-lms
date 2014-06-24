<?php

namespace FOS\MessageBundle\Security;

use FOS\MessageBundle\Model\ThreadInterface;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Manages permissions to manipulate threads and messages
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface AuthorizerInterface
{
    /**
     * Tells if the current user is allowed
     * to see this thread
     *
     * @param ThreadInterface $thread
     * @return boolean
     */
    function canSeeThread(ThreadInterface $thread);

    /**
     * Tells if the current participant is allowed
     * to delete this thread
     *
     * @param ThreadInterface $thread
     * @return boolean
     */
    function canDeleteThread(ThreadInterface $thread);

    /**
     * Tells if the current participant is allowed
     * to send a message to this other participant
     *
     * @param ParticipantInterface $participant the one we want to send a message to
     * @return boolean
     */
    function canMessageParticipant(ParticipantInterface $participant);
}
