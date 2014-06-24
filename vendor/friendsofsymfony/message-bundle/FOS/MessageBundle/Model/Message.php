<?php

namespace FOS\MessageBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Abstract message model
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
abstract class Message implements MessageInterface
{
    /**
     * Unique id of the message
     *
     * @var mixed
     */
    protected $id;

    /**
     * User who sent the message
     *
     * @var ParticipantInterface
     */
    protected $sender;

    /**
     * Text body of the message
     *
     * @var string
     */
    protected $body;

    /**
     * Date when the message was sent
     *
     * @var DateTime
     */
    protected $createdAt;

    /**
     * Thread the message belongs to
     *
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * Collection of MessageMetadata
     *
     * @var Collection of MessageMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->metadata = new ArrayCollection();
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::getThread()
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::setThread()
     */
    public function setThread(ThreadInterface $thread)
    {
        $this->thread = $thread;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::getCreatedAt()
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::getBody()
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::setBody()
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::getSender()
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @see FOS\MessageBundle\Model\MessageInterface::setSender()
     */
    public function setSender(ParticipantInterface $sender)
    {
        $this->sender = $sender;
    }

    /**
     * Gets the created at timestamp
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->getCreatedAt()->getTimestamp();
    }

    /**
     * Adds MessageMetadata to the metadata collection.
     *
     * @param MessageMetadata $meta
     */
    public function addMetadata(MessageMetadata $meta)
    {
        $this->metadata->add($meta);
    }

    /**
     * Get the MessageMetadata for a participant.
     *
     * @param ParticipantInterface $participant
     * @return MessageMetadata
     */
    public function getMetadataForParticipant(ParticipantInterface $participant)
    {
        foreach ($this->metadata as $meta) {
            if ($meta->getParticipant()->getId() == $participant->getId()) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @see FOS\MessageBundle\Model\ReadableInterface::isReadByParticipant()
     */
    public function isReadByParticipant(ParticipantInterface $participant)
    {
        if ($meta = $this->getMetadataForParticipant($participant)) {
            return $meta->getIsRead();
        }

        return false;
    }

    /**
     * @see FOS\MessageBundle\Model\ReadableInterface::setIsReadByParticipant()
     */
    public function setIsReadByParticipant(ParticipantInterface $participant, $isRead)
    {
        if (!$meta = $this->getMetadataForParticipant($participant)) {
            throw new \InvalidArgumentException(sprintf('No metadata exists for participant with id "%s"', $participant->getId()));
        }

        $meta->setIsRead($isRead);
    }
}
