<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventEmailTemplate.
 *
 * @ORM\Table(name="event_email_template", indexes={@ORM\Index(name="event_name_index", columns={"event_type_name"})})
 * @ORM\Entity
 */
class EventEmailTemplate
{
    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=255, nullable=true)
     */
    protected $eventTypeName;

    /**
     * @var bool
     *
     * @ORM\Column(name="activated", type="boolean", nullable=false)
     */
    protected $activated;

    /**
     * @var int
     *
     * @ORM\Column(name="language_id", type="integer", nullable=true)
     */
    protected $languageId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return EventEmailTemplate
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return EventEmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set eventTypeName.
     *
     * @param string $eventTypeName
     *
     * @return EventEmailTemplate
     */
    public function setEventTypeName($eventTypeName)
    {
        $this->eventTypeName = $eventTypeName;

        return $this;
    }

    /**
     * Get eventTypeName.
     *
     * @return string
     */
    public function getEventTypeName()
    {
        return $this->eventTypeName;
    }

    /**
     * Set activated.
     *
     * @param bool $activated
     *
     * @return EventEmailTemplate
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * Get activated.
     *
     * @return bool
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Set languageId.
     *
     * @param int $languageId
     *
     * @return EventEmailTemplate
     */
    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Get languageId.
     *
     * @return int
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
