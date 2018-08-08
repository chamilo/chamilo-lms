<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFile.
 *
 * @ORM\Table(
 *  name="c_dropbox_file",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="UN_filename", columns={"filename"})
 *  },
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CDropboxFile
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
     * @var int
     *
     * @ORM\Column(name="uploader_id", type="integer", nullable=false)
     */
    protected $uploaderId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=250, nullable=false)
     */
    protected $filename;

    /**
     * @var int
     *
     * @ORM\Column(name="filesize", type="integer", nullable=false)
     */
    protected $filesize;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=250, nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=250, nullable=true)
     */
    protected $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", nullable=false)
     */
    protected $uploadDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_upload_date", type="datetime", nullable=false)
     */
    protected $lastUploadDate;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_id", type="integer", nullable=false)
     */
    protected $catId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set uploaderId.
     *
     * @param int $uploaderId
     *
     * @return CDropboxFile
     */
    public function setUploaderId($uploaderId)
    {
        $this->uploaderId = $uploaderId;

        return $this;
    }

    /**
     * Get uploaderId.
     *
     * @return int
     */
    public function getUploaderId()
    {
        return $this->uploaderId;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return CDropboxFile
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
     * Set filesize.
     *
     * @param int $filesize
     *
     * @return CDropboxFile
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize.
     *
     * @return int
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CDropboxFile
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CDropboxFile
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set author.
     *
     * @param string $author
     *
     * @return CDropboxFile
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set uploadDate.
     *
     * @param \DateTime $uploadDate
     *
     * @return CDropboxFile
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * Get uploadDate.
     *
     * @return \DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set lastUploadDate.
     *
     * @param \DateTime $lastUploadDate
     *
     * @return CDropboxFile
     */
    public function setLastUploadDate($lastUploadDate)
    {
        $this->lastUploadDate = $lastUploadDate;

        return $this;
    }

    /**
     * Get lastUploadDate.
     *
     * @return \DateTime
     */
    public function getLastUploadDate()
    {
        return $this->lastUploadDate;
    }

    /**
     * Set catId.
     *
     * @param int $catId
     *
     * @return CDropboxFile
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId.
     *
     * @return int
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CDropboxFile
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CDropboxFile
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
     * @return CDropboxFile
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

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CDropboxFile
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }
}
