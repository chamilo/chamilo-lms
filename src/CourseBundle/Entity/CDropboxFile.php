<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFile.
 *
 * @ORM\Table(
 *     name="c_dropbox_file",
 *     options={"row_format":"DYNAMIC"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="UN_filename", columns={"filename"})
 *     },
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CDropboxFile
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="uploader_id", type="integer", nullable=false)
     */
    protected int $uploaderId;

    /**
     * @ORM\Column(name="filename", type="string", length=190, nullable=false)
     */
    protected string $filename;

    /**
     * @ORM\Column(name="filesize", type="integer", nullable=false)
     */
    protected int $filesize;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="string", length=250, nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="author", type="string", length=250, nullable=true)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(name="upload_date", type="datetime", nullable=false)
     */
    protected DateTime $uploadDate;

    /**
     * @ORM\Column(name="last_upload_date", type="datetime", nullable=false)
     */
    protected DateTime $lastUploadDate;

    /**
     * @ORM\Column(name="cat_id", type="integer", nullable=false)
     */
    protected int $catId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * Set uploaderId.
     *
     * @return CDropboxFile
     */
    public function setUploaderId(int $uploaderId)
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
     * @return CDropboxFile
     */
    public function setFilename(string $filename)
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
     * @return CDropboxFile
     */
    public function setFilesize(int $filesize)
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
     * @return CDropboxFile
     */
    public function setTitle(string $title)
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
     * @return CDropboxFile
     */
    public function setDescription(string $description)
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
     * @return CDropboxFile
     */
    public function setAuthor(string $author)
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
     * @return CDropboxFile
     */
    public function setUploadDate(DateTime $uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * Get uploadDate.
     *
     * @return DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set lastUploadDate.
     *
     * @return CDropboxFile
     */
    public function setLastUploadDate(DateTime $lastUploadDate)
    {
        $this->lastUploadDate = $lastUploadDate;

        return $this;
    }

    /**
     * Get lastUploadDate.
     *
     * @return DateTime
     */
    public function getLastUploadDate()
    {
        return $this->lastUploadDate;
    }

    /**
     * Set catId.
     *
     * @return CDropboxFile
     */
    public function setCatId(int $catId)
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
     * @return CDropboxFile
     */
    public function setSessionId(int $sessionId)
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
     * Set cId.
     *
     * @return CDropboxFile
     */
    public function setCId(int $cId)
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
}
