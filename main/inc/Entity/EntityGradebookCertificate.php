<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookCertificate
 *
 * @Table(name="gradebook_certificate")
 * @Entity
 */
class EntityGradebookCertificate
{
    /**
     * @var integer
     *
     * @Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var float
     *
     * @Column(name="score_certificate", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $scoreCertificate;

    /**
     * @var \DateTime
     *
     * @Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var string
     *
     * @Column(name="path_certificate", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $pathCertificate;


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
     * Set catId
     *
     * @param integer $catId
     * @return EntityGradebookCertificate
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityGradebookCertificate
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set scoreCertificate
     *
     * @param float $scoreCertificate
     * @return EntityGradebookCertificate
     */
    public function setScoreCertificate($scoreCertificate)
    {
        $this->scoreCertificate = $scoreCertificate;

        return $this;
    }

    /**
     * Get scoreCertificate
     *
     * @return float 
     */
    public function getScoreCertificate()
    {
        return $this->scoreCertificate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EntityGradebookCertificate
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set pathCertificate
     *
     * @param string $pathCertificate
     * @return EntityGradebookCertificate
     */
    public function setPathCertificate($pathCertificate)
    {
        $this->pathCertificate = $pathCertificate;

        return $this;
    }

    /**
     * Get pathCertificate
     *
     * @return string 
     */
    public function getPathCertificate()
    {
        return $this->pathCertificate;
    }
}
