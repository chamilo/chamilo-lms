<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFile
 *
 * @ORM\Table(name="c_dropbox_file")
 * @ORM\Entity
 */
class CDropboxFile
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="uploader_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploaderId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filename;

    /**
     * @var integer
     *
     * @ORM\Column(name="filesize", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $filesize;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_upload_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lastUploadDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @ORM\OneToMany(targetEntity="CDropboxPost", mappedBy="file", cascade={"ALL"}, indexBy="file_id")
     */
    private $file;


    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="uploader_id", referencedColumnName="user_id")
     **/
    private $userSent;

    public function __construct($cId)
    {
        $this->cId = $cId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CDropboxFile
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
     * @return CDropboxFile
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
     * Set uploaderId
     *
     * @param integer $uploaderId
     * @return CDropboxFile
     */
    public function setUploaderId($uploaderId)
    {
        $this->uploaderId = $uploaderId;

        return $this;
    }

    /**
     * Get uploaderId
     *
     * @return integer
     */
    public function getUploaderId()
    {
        return $this->uploaderId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return CDropboxFile
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

    /**
     * Set filesize
     *
     * @param integer $filesize
     * @return CDropboxFile
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filesize
     *
     * @return integer
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CDropboxFile
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CDropboxFile
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return CDropboxFile
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set uploadDate
     *
     * @param \DateTime $uploadDate
     * @return CDropboxFile
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * Get uploadDate
     *
     * @return \DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set lastUploadDate
     *
     * @param \DateTime $lastUploadDate
     * @return CDropboxFile
     */
    public function setLastUploadDate($lastUploadDate)
    {
        $this->lastUploadDate = $lastUploadDate;

        return $this;
    }

    /**
     * Get lastUploadDate
     *
     * @return \DateTime
     */
    public function getLastUploadDate()
    {
        return $this->lastUploadDate;
    }

    /**
     * Set catId
     *
     * @param integer $catId
     * @return CDropboxFile
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId
     *
     * @return integer
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CDropboxFile
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
