<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAnnouncementAttachment.
 *
 * @ORM\Table(
 *  name="c_announcement_attachment",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CAnnouncementAttachment
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
     * @ORM\Column(name="announcement_id", type="integer", nullable=false)
     */
    protected $announcementId;

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
     * @return CAnnouncementAttachment
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
     * @return CAnnouncementAttachment
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
     * @return CAnnouncementAttachment
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
     * Set announcementId.
     *
     * @param int $announcementId
     *
     * @return CAnnouncementAttachment
     */
    public function setAnnouncementId($announcementId)
    {
        $this->announcementId = $announcementId;

        return $this;
    }

    /**
     * Get announcementId.
     *
     * @return int
     */
    public function getAnnouncementId()
    {
        return $this->announcementId;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return CAnnouncementAttachment
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
     * @return CAnnouncementAttachment
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
     * @return CAnnouncementAttachment
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
