<?php

namespace FOS\MessageBundle\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

interface ReadableInterface
{
    /**
     * Tells if this is read by this participant
     *
     * @return bool
     */
    function isReadByParticipant(ParticipantInterface $participant);

    /**
     * Sets whether or not this participant has read this
     *
     * @param ParticipantInterface $participant
     * @param boolean $isRead
     */
    function setIsReadByParticipant(ParticipantInterface $participant, $isRead);
}
