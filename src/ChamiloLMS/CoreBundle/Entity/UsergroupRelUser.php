<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * UsergroupRelUser
 *
 * @ORM\Table(name="usergroup_rel_user")
 * @ORM\Entity
 */
class UsergroupRelUser
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
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="classes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Usergroup", inversedBy="users")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id")
     **/
    private $class;

    /**
     * Gets the class obj
    */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Gets an user obj
     */
    public function getUser()
    {
        return $this->user;
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

    /**
     * Set usergroupId
     *
     * @param integer $usergroupId
     *
     * @return UsergroupRelUser
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
     * Set userId
     *
     * @param integer $userId
     * @return UsergroupRelUser
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
