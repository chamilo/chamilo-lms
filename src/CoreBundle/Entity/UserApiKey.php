<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserApiKey.
 *
 * @ORM\Table(name="user_api_key", indexes={
 *     @ORM\Index(name="idx_user_api_keys_user", columns={"user_id"})
 * })
 * @ORM\Entity
 */
class UserApiKey
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="api_key", type="string", length=32, nullable=false)
     */
    protected string $apiKey;

    /**
     * @ORM\Column(name="api_service", type="string", length=10, nullable=false)
     */
    protected string $apiService;

    /**
     * @ORM\Column(name="api_end_point", type="text", nullable=true)
     */
    protected ?string $apiEndPoint = null;

    /**
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    protected ?DateTime $createdDate = null;

    /**
     * @ORM\Column(name="validity_start_date", type="datetime", nullable=true)
     */
    protected ?DateTime $validityStartDate = null;

    /**
     * @ORM\Column(name="validity_end_date", type="datetime", nullable=true)
     */
    protected ?DateTime $validityEndDate = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * Set userId.
     *
     * @return UserApiKey
     */
    public function setUserId(int $userId)
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
     * @return UserApiKey
     */
    public function setApiKey(string $apiKey)
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
     * @return UserApiKey
     */
    public function setApiService(string $apiService)
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
     * @return UserApiKey
     */
    public function setApiEndPoint(string $apiEndPoint)
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
     * @return UserApiKey
     */
    public function setCreatedDate(DateTime $createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate.
     *
     * @return DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set validityStartDate.
     *
     * @return UserApiKey
     */
    public function setValidityStartDate(DateTime $validityStartDate)
    {
        $this->validityStartDate = $validityStartDate;

        return $this;
    }

    /**
     * Get validityStartDate.
     *
     * @return DateTime
     */
    public function getValidityStartDate()
    {
        return $this->validityStartDate;
    }

    /**
     * Set validityEndDate.
     *
     * @return UserApiKey
     */
    public function setValidityEndDate(DateTime $validityEndDate)
    {
        $this->validityEndDate = $validityEndDate;

        return $this;
    }

    /**
     * Get validityEndDate.
     *
     * @return DateTime
     */
    public function getValidityEndDate()
    {
        return $this->validityEndDate;
    }

    /**
     * Set description.
     *
     * @return UserApiKey
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
