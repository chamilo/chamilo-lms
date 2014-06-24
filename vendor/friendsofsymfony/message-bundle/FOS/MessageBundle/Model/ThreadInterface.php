<?php

namespace FOS\MessageBundle\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

interface ThreadInterface extends ReadableInterface
{
    /**
     * Gets the message unique id
     *
     * @return mixed
     */
    function getId();

    /**
     * @return string
     */
    function getSubject();

    /**
     * @param  string
     * @return null
     */
    function setSubject($subject);

    /**
     * Gets the messages contained in the thread
     *
     * @return Collection of MessageInterface
     */
    function getMessages();

    /**
     * Adds a new message to the thread
     *
     * @param MessageInterface $message
     */
    function addMessage(MessageInterface $message);

    /**
     * Gets the first message of the thread
     *
     * @return MessageInterface the first message
     */
    function getFirstMessage();

    /**
     * Gets the last message of the thread
     *
     * @return MessageInterface the last message
     */
    function getLastMessage();

    /**
     * Gets the participant that created the thread
     * Generally the sender of the first message
     *
     * @return ParticipantInterface
     */
    function getCreatedBy();

    /**
     * Sets the participant that created the thread
     * Generally the sender of the first message
     *
     * @param ParticipantInterface
     */
    function setCreatedBy(ParticipantInterface $participant);

    /**
     * Gets the date this thread was created at
     * Generally the date of the first message
     *
     * @return DateTime
     */
    function getCreatedAt();

    /**
     * Sets the date this thread was created at
     * Generally the date of the first message
     *
     * @param DateTime
     */
    function setCreatedAt(\DateTime $createdAt);

    /**
     * Gets the users participating in this conversation
     *
     * @return array of ParticipantInterface
     */
    function getParticipants();

    /**
     * Tells if the user participates to the conversation
     *
     * @param ParticipantInterface $participant
     * @return boolean
     */
    function isParticipant(ParticipantInterface $participant);

    /**
     * Adds a participant to the thread
     * If it already exists, nothing is done.
     *
     * @param ParticipantInterface $participant
     * @return null
     */
    function addParticipant(ParticipantInterface $participant);

    /**
     * Tells if this thread is deleted by this participant
     *
     * @return bool
     */
    function isDeletedByParticipant(ParticipantInterface $participant);

    /**
     * Sets whether or not this participant has deleted this thread
     *
     * @param ParticipantInterface $participant
     * @param boolean $isDeleted
     */
    function setIsDeletedByParticipant(ParticipantInterface $participant, $isDeleted);

    /**
     * Sets the thread as deleted or not deleted for all participants
     *
     * @param boolean $isDeleted
     */
    function setIsDeleted($isDeleted);

    /**
     * Get the participants this participant is talking with.
     *
     * @return array of ParticipantInterface
     */
    function getOtherParticipants(ParticipantInterface $participant);
}
