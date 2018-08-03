<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxCategory.
 *
 * @ORM\Table(
 *  name="c_dropbox_category",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CDropboxCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_id", type="integer")
     */
    protected $catId;

    /**
     * @var string
     *
     * @ORM\Column(name="cat_name", type="text", nullable=false)
     */
    protected $catName;

    /**
     * @var bool
     *
     * @ORM\Column(name="received", type="boolean", nullable=false)
     */
    protected $received;

    /**
     * @var bool
     *
     * @ORM\Column(name="sent", type="boolean", nullable=false)
     */
    protected $sent;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set catName.
     *
     * @param string $catName
     *
     * @return CDropboxCategory
     */
    public function setCatName($catName)
    {
        $this->catName = $catName;

        return $this;
    }

    /**
     * Get catName.
     *
     * @return string
     */
    public function getCatName()
    {
        return $this->catName;
    }

    /**
     * Set received.
     *
     * @param bool $received
     *
     * @return CDropboxCategory
     */
    public function setReceived($received)
    {
        $this->received = $received;

        return $this;
    }

    /**
     * Get received.
     *
     * @return bool
     */
    public function getReceived()
    {
        return $this->received;
    }

    /**
     * Set sent.
     *
     * @param bool $sent
     *
     * @return CDropboxCategory
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent.
     *
     * @return bool
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CDropboxCategory
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
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CDropboxCategory
     */
    public function setSessionId($sessionId)
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
     * Set catId.
     *
     * @param int $catId
     *
     * @return CDropboxCategory
     */
    public function setCatId($catId)
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CDropboxCategory
     */
    public function setCId($cId)
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
}
