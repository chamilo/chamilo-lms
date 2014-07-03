<?php

namespace FOS\MessageBundle\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Message model
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface MessageInterface extends ReadableInterface
{
    /**
     * Gets the message unique id
     *
     * @return mixed
     */
    function getId();

    /**
     * @return ThreadInterface
     */
    function getThread();

    /**
     * @param  ThreadInterface
     * @return null
     */
    function setThread(ThreadInterface $thread);

    /**
     * @return DateTime
     */
    function getCreatedAt();

    /**
     * @return string
     */
    function getBody();

    /**
     * @param  string
     * @return null
     */
    function setBody($body);

    /**
     * @return ParticipantInterface
     */
    function getSender();

    /**
     * @param  ParticipantInterface
     * @return null
     */
    function setSender(ParticipantInterface $sender);
}
