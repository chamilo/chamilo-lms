<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelSession
 *
 * @ORM\Table(name="usergroup_rel_session")
 * @ORM\Entity
 */
class UsergroupRelSession
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return UsergroupRelSession
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId
     *
     * @return integer
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return UsergroupRelSession
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
