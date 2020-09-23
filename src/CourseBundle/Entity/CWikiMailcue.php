<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiMailcue.
 *
 * @ORM\Table(
 *  name="c_wiki_mailcue",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user", columns={"user_id"}),
 *      @ORM\Index(name="c_id", columns={"c_id", "iid"})
 *  }
 * )
 * @ORM\Entity
 */
class CWikiMailcue
{
    use UserTrait;

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
     * @var string
     *
     * @ORM\Column(name="type", type="text", nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cWikiMailcues"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

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
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CWikiMailcue
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }
}
