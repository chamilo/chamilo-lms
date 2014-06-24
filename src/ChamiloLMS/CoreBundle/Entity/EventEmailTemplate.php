<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventEmailTemplate
 *
 * @ORM\Table(name="event_email_template", indexes={@ORM\Index(name="event_name_index", columns={"event_type_name"})})
 * @ORM\Entity
 */
class EventEmailTemplate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type_name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $eventTypeName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="activated", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $activated;

    /**
     * @var integer
     *
     * @ORM\Column(name="language_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $languageId;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return EventEmailTemplate
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return EventEmailTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set eventTypeName
     *
     * @param string $eventTypeName
     * @return EventEmailTemplate
     */
    public function setEventTypeName($eventTypeName)
    {
        $this->eventTypeName = $eventTypeName;

        return $this;
    }

    /**
     * Get eventTypeName
     *
     * @return string
     */
    public function getEventTypeName()
    {
        return $this->eventTypeName;
    }

    /**
     * Set activated
     *
     * @param boolean $activated
     * @return EventEmailTemplate
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * Get activated
     *
     * @return boolean
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Set languageId
     *
     * @param integer $languageId
     * @return EventEmailTemplate
     */
    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * Get languageId
     *
     * @return integer
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }
}
