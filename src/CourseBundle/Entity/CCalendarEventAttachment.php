<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CCalendarEventAttachment.
 *
 * @ORM\Table(
 *  name="c_calendar_event_attachment",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CCalendarEventAttachment
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected $size;

    /**
     * @var int
     *
     * @ORM\Column(name="agenda_id", type="integer", nullable=false)
     */
    protected $agendaId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    protected $filename;

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return CCalendarEventAttachment
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return CCalendarEventAttachment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return CCalendarEventAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set agendaId.
     *
     * @param int $agendaId
     *
     * @return CCalendarEventAttachment
     */
    public function setAgendaId($agendaId)
    {
        $this->agendaId = $agendaId;

        return $this;
    }

    /**
     * Get agendaId.
     *
     * @return int
     */
    public function getAgendaId()
    {
        return $this->agendaId;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return CCalendarEventAttachment
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CCalendarEventAttachment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CCalendarEventAttachment
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
