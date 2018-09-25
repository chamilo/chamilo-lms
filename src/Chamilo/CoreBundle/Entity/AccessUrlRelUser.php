<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelUser.
 *
 * @ORM\Table(
 *     name="access_url_rel_user",
 *     indexes={
 *      @ORM\Index(name="idx_access_url_rel_user_user", columns={"user_id"}),
 *      @ORM\Index(name="idx_access_url_rel_user_access_url", columns={"access_url_id"}),
 *      @ORM\Index(name="idx_access_url_rel_user_access_url_user", columns={"user_id", "access_url_id"})
 *     }
 * )
 * @ORM\Entity
 */
class AccessUrlRelUser
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="access_url_id", type="integer")
     */
    protected $accessUrlId;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $userId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\AccessUrl")
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected $portal;

    /**
     * Set accessUrlId.
     *
     * @param int $accessUrlId
     *
     * @return AccessUrlRelUser
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return AccessUrlRelUser
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
}
