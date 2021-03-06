<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="c_wiki_mailcue",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user", columns={"user_id"}),
 *         @ORM\Index(name="c_id", columns={"c_id", "iid"})
 *     }
 * )
 * @ORM\Entity
 */
class CWikiMailcue
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="type", type="text", nullable=false)
     */
    protected string $type;

    /**
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    protected ?int $groupId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected int $userId;

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return CWikiMailcue
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return CWikiMailcue
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CWikiMailcue
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CWikiMailcue
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

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CWikiMailcue
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
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
