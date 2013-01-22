<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCDropboxFile
 *
 * @Table(name="c_dropbox_file")
 * @Entity
 */
class EntityCDropboxFile
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
     * @var integer
     *
     * @Column(name="uploader_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploaderId;

    /**
     * @var string
     *
     * @Column(name="filename", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filename;

    /**
     * @var integer
     *
     * @Column(name="filesize", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $filesize;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="author", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @Column(name="upload_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadDate;

    /**
     * @var \DateTime
     *
     * @Column(name="last_upload_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lastUploadDate;

    /**
     * @var integer
     *
     * @Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
     * @return EntityCDropboxFile
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
