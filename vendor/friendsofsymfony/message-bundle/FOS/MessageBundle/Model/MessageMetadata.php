<?php

namespace FOS\MessageBundle\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

abstract class MessageMetadata
{
    protected $participant;

    protected $isRead = false;

    /**
     * @return ParticipantInterface
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param ParticipantInterface $participant
     * @return null
     */
    public function setParticipant(ParticipantInterface $participant)
    {
        $this->participant = $participant;
    }

    /**
     * @return boolean
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @param boolean $isRead
     * @return null
     */
    public function setIsRead($isRead)
    {
        $this->isRead = (boolean)$isRead;
    }
}
