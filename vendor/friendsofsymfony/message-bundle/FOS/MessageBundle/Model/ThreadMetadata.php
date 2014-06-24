<?php

namespace FOS\MessageBundle\Model;

use FOS\MessageBundle\Model\ParticipantInterface;

abstract class ThreadMetadata
{
    protected $participant;

    protected $isDeleted = false;

    /**
    * Date of last message written by the participant
    *
    * @var DateTime
    */
    protected $lastParticipantMessageDate;

    /**
     * Date of last message written by another participant
     *
     * @var DateTime
     */
    protected $lastMessageDate;

    /**
     * @return ParticipantInterface
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param ParticipantInterface
     * @return null
     */
    public function setParticipant(ParticipantInterface $participant)
    {
        $this->participant = $participant;
    }

    /**
     * @return boolean
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param boolean $isDeleted
     * @return null
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = (boolean)$isDeleted;
    }

    /**
     * @return DateTime
     */
    public function getLastParticipantMessageDate()
    {
        return $this->lastParticipantMessageDate;
    }

    /**
     * @param DateTime $date
     * @return null
     */
    public function setLastParticipantMessageDate(\DateTime $date)
    {
        $this->lastParticipantMessageDate = $date;
    }

    /**
     * @return DateTime
     */
    public function getLastMessageDate()
    {
        return $this->lastMessageDate;
    }

    /**
     * @param DateTime $date
     * @return null
     */
    public function setLastMessageDate(\DateTime $date)
    {
        $this->lastMessageDate = $date;
    }
}
