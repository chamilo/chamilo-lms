<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserApiKey
 *
 * @ORM\Table(name="user_api_key", indexes={@ORM\Index(name="idx_user_api_keys_user", columns={"user_id"})})
 * @ORM\Entity
 */
class UserApiKey
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="string", length=32, nullable=false)
     */
    private $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="api_service", type="string", length=10, nullable=false)
     */
    private $apiService;

    /**
     * @var string
     *
     * @ORM\Column(name="api_end_point", type="text", nullable=true)
     */
    private $apiEndPoint;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validity_start_date", type="datetime", nullable=true)
     */
    private $validityStartDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validity_end_date", type="datetime", nullable=true)
     */
    private $validityEndDate;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
