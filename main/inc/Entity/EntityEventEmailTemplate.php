<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityEventEmailTemplate
 *
 * @Table(name="event_email_template")
 * @Entity
 */
class EntityEventEmailTemplate
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="message", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $message;

    /**
     * @var string
     *
     * @Column(name="subject", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @Column(name="event_type_name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $eventTypeName;

    /**
     * @var boolean
     *
     * @Column(name="activated", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $activated;

    /**
     * @var integer
     *
     * @Column(name="language_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
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
     * @return EntityEventEmailTemplate
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
     * @return EntityEventEmailTemplate
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
     * @return EntityEventEmailTemplate
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
     * @return EntityEventEmailTemplate
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
     * @return EntityEventEmailTemplate
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
