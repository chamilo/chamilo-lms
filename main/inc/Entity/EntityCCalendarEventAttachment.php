<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCCalendarEventAttachment
 *
 * @Table(name="c_calendar_event_attachment")
 * @Entity
 */
class EntityCCalendarEventAttachment
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="path", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $path;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var integer
     *
     * @Column(name="size", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $size;

    /**
     * @var integer
     *
     * @Column(name="agenda_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $agendaId;

    /**
     * @var string
     *
     * @Column(name="filename", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filename;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCCalendarEventAttachment
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCCalendarEventAttachment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set path
     *
     * @param string $path
     * @return EntityCCalendarEventAttachment
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntityCCalendarEventAttachment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return EntityCCalendarEventAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set agendaId
     *
     * @param integer $agendaId
     * @return EntityCCalendarEventAttachment
     */
    public function setAgendaId($agendaId)
    {
        $this->agendaId = $agendaId;

        return $this;
    }

    /**
     * Get agendaId
     *
     * @return integer 
     */
    public function getAgendaId()
    {
        return $this->agendaId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return EntityCCalendarEventAttachment
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
