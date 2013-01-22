<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUserApiKey
 *
 * @Table(name="user_api_key")
 * @Entity
 */
class EntityUserApiKey
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
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="api_key", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $apiKey;

    /**
     * @var string
     *
     * @Column(name="api_service", type="string", length=10, precision=0, scale=0, nullable=false, unique=false)
     */
    private $apiService;

    /**
     * @var string
     *
     * @Column(name="api_end_point", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $apiEndPoint;

    /**
     * @var \DateTime
     *
     * @Column(name="created_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @Column(name="validity_start_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $validityStartDate;

    /**
     * @var \DateTime
     *
     * @Column(name="validity_end_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $validityEndDate;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;


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
     * Set userId
     *
     * @param integer $userId
     * @return EntityUserApiKey
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
     * Set apiKey
     *
     * @param string $apiKey
     * @return EntityUserApiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string 
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set apiService
     *
     * @param string $apiService
     * @return EntityUserApiKey
     */
    public function setApiService($apiService)
    {
        $this->apiService = $apiService;

        return $this;
    }

    /**
     * Get apiService
     *
     * @return string 
     */
    public function getApiService()
    {
        return $this->apiService;
    }

    /**
     * Set apiEndPoint
     *
     * @param string $apiEndPoint
     * @return EntityUserApiKey
     */
    public function setApiEndPoint($apiEndPoint)
    {
        $this->apiEndPoint = $apiEndPoint;

        return $this;
    }

    /**
     * Get apiEndPoint
     *
     * @return string 
     */
    public function getApiEndPoint()
    {
        return $this->apiEndPoint;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return EntityUserApiKey
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set validityStartDate
     *
     * @param \DateTime $validityStartDate
     * @return EntityUserApiKey
     */
    public function setValidityStartDate($validityStartDate)
    {
        $this->validityStartDate = $validityStartDate;

        return $this;
    }

    /**
     * Get validityStartDate
     *
     * @return \DateTime 
     */
    public function getValidityStartDate()
    {
        return $this->validityStartDate;
    }

    /**
     * Set validityEndDate
     *
     * @param \DateTime $validityEndDate
     * @return EntityUserApiKey
     */
    public function setValidityEndDate($validityEndDate)
    {
        $this->validityEndDate = $validityEndDate;

        return $this;
    }

    /**
     * Get validityEndDate
     *
     * @return \DateTime 
     */
    public function getValidityEndDate()
    {
        return $this->validityEndDate;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityUserApiKey
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
}
