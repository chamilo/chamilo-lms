<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelUser
 *
 * @ORM\Table(name="access_url_rel_user", indexes={@ORM\Index(name="idx_access_url_rel_user_user", columns={"user_id"}), @ORM\Index(name="idx_access_url_rel_user_access_url", columns={"access_url_id"}), @ORM\Index(name="idx_access_url_rel_user_access_url_user", columns={"user_id", "access_url_id"})})
 * @ORM\Entity
 */
class AccessUrlRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;



    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return AccessUrlRelUser
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return AccessUrlRelUser
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
}
