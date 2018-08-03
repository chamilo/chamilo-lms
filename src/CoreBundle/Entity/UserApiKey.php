<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserApiKey.
 *
 * @ORM\Table(name="user_api_key", indexes={@ORM\Index(name="idx_user_api_keys_user", columns={"user_id"})})
 * @ORM\Entity
 */
class UserApiKey
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="string", length=32, nullable=false)
     */
    protected $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="api_service", type="string", length=10, nullable=false)
     */
    protected $apiService;

    /**
     * @var string
     *
     * @ORM\Column(name="api_end_point", type="text", nullable=true)
     */
    protected $apiEndPoint;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    protected $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validity_start_date", type="datetime", nullable=true)
     */
    protected $validityStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validity_end_date", type="datetime", nullable=true)
     */
    protected $validityEndDate;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return UserApiKey
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set apiKey.
     *
     * @param string $apiKey
     *
     * @return UserApiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set apiService.
     *
     * @param string $apiService
     *
     * @return UserApiKey
     */
    public function setApiService($apiService)
    {
        $this->apiService = $apiService;

        return $this;
    }

    /**
     * Get apiService.
     *
     * @return string
     */
    public function getApiService()
    {
        return $this->apiService;
    }

    /**
     * Set apiEndPoint.
     *
     * @param string $apiEndPoint
     *
     * @return UserApiKey
     */
    public function setApiEndPoint($apiEndPoint)
    {
        $this->apiEndPoint = $apiEndPoint;

        return $this;
    }

    /**
     * Get apiEndPoint.
     *
     * @return string
     */
    public function getApiEndPoint()
    {
        return $this->apiEndPoint;
    }

    /**
     * Set createdDate.
     *
     * @param \DateTime $createdDate
     *
     * @return UserApiKey
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate.
     *
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set validityStartDate.
     *
     * @param \DateTime $validityStartDate
     *
     * @return UserApiKey
     */
    public function setValidityStartDate($validityStartDate)
    {
        $this->validityStartDate = $validityStartDate;

        return $this;
    }

    /**
     * Get validityStartDate.
     *
     * @return \DateTime
     */
    public function getValidityStartDate()
    {
        return $this->validityStartDate;
    }

    /**
     * Set validityEndDate.
     *
     * @param \DateTime $validityEndDate
     *
     * @return UserApiKey
     */
    public function setValidityEndDate($validityEndDate)
    {
        $this->validityEndDate = $validityEndDate;

        return $this;
    }

    /**
     * Get validityEndDate.
     *
     * @return \DateTime
     */
    public function getValidityEndDate()
    {
        return $this->validityEndDate;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return UserApiKey
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
