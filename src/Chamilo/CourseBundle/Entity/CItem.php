<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CItemProperty
 *
 * @ORM\Table(name="c_item")
 * @ORM\Entity
 */
class CItem
{
    /**
    * @var integer
    *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $tool;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="ref", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ref;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="items")
     * @ORM\JoinColumn(name="to_user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CItem
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


    /**
     * Set id
     *
     * @param integer $id
     *
     * @return CItemProperty
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set tool
     *
     * @param string $tool
     *
     * @return CItemProperty
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Get tool
     *
     * @return string
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * Set ref
     *
     * @param integer $ref
     *
     * @return CItemProperty
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref
     *
     * @return integer
     */
    public function getRef()
    {
        return $this->ref;
    }
}
