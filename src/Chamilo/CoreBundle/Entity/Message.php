<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\MessageBundle\Entity\Message as BaseMessage;
use Avanzu\AdminThemeBundle\Model\MessageInterface as ThemeMessage;

/**
 * @ORM\Table(name="message")
 * @ORM\Entity
 */
class Message extends BaseMessage implements ThemeMessage
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Chamilo\CoreBundle\Entity\Thread",
     *   inversedBy="messages"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @var ParticipantInterface
     */
    protected $sender;

    /**
     * @ORM\OneToMany(
     *   targetEntity="Chamilo\CoreBundle\Entity\MessageMetadata",
     *   mappedBy="message",
     *   cascade={"all"}
     * )
     * @var MessageMetadata
     */
    protected $metadata;

    //
    public function getFrom()
    {
        return $this->getSender();
    }

    public function getSentAt()
    {
        return $this->getTimestamp();
    }

    public function getSubject()
    {
        return $this->getThread()->getSubject();
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

}
