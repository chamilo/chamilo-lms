<?php

namespace FOS\MessageBundle\Entity;

use Doctrine\Common\Collections\Collection;
use FOS\MessageBundle\Model\Message as BaseMessage;

use FOS\MessageBundle\Model\MessageMetadata as ModelMessageMetadata;

abstract class Message extends BaseMessage
{
    /**
     * Get the collection of MessageMetadata.
     *
     * @return Collection
     */
    public function getAllMetadata()
    {
        return $this->metadata;
    }

    /**
     * @see FOS\MessageBundle\Model\Message::addMetadata()
     */
    public function addMetadata(ModelMessageMetadata $meta)
    {
        $meta->setMessage($this);
        parent::addMetadata($meta);
    }
}
