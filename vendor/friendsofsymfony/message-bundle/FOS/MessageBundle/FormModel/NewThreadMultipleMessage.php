<?php
namespace FOS\MessageBundle\FormModel;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Class for handling multiple recipients in thread
 */
class NewThreadMultipleMessage extends AbstractMessage
{
    /**
     * The user who receives the message
     *
     * @var ArrayCollection
     */
    protected $recipients;

    /**
     * The thread subject
     *
     * @var string
     */
    protected $subject;

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return null
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return ArrayCollection
     */
    public function getRecipients()
    {
        if ($this->recipients === null) {
            $this->recipients = new ArrayCollection();
        }

        return $this->recipients;
    }

    /**
     * Adds single recipient to collection
     *
     * @param ParticipantInterface $recipient
     *
     * @return null
     */
    public function addRecipient(ParticipantInterface $recipient)
    {
        if (!$this->recipients->contains($recipient)) {
            $this->recipients->add($recipient);
        }
    }


    /**
     * Removes recipient from collection
     *
     * @param ParticipantInterface $recipient
     *
     * @return null
     *
     */
    public function removeRecipient(ParticipantInterface $recipient)
    {
        $this->recipients->removeElement($recipient);
    }

}
