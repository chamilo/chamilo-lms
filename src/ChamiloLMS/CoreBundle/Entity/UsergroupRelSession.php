<?php

namespace ChamiloLMS\CoreBundle\Entity;

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
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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
}
