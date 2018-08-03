<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelSession.
 *
 * @ORM\Table(name="usergroup_rel_session")
 * @ORM\Entity
 */
class UsergroupRelSession
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
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    protected $usergroupId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set usergroupId.
     *
     * @param int $usergroupId
     *
     * @return UsergroupRelSession
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId.
     *
     * @return int
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return UsergroupRelSession
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
