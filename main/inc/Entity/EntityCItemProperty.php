<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * EntityCItemProperty
 *
 * @Table(name="c_item_property")
 * @Entity(repositoryClass="Entity\Repository\ItemPropertyRepository")
 */
class EntityCItemProperty
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="tool", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $tool;

    /**
     * @var integer
     *
     * @Column(name="insert_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $insertUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="insert_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $insertDate;

    /**
     * @var \DateTime
     *
     * @Column(name="lastedit_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditDate;

    /**
     * @var integer
     *
     * @Column(name="ref", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ref;

    /**
     * @var string
     *
     * @Column(name="lastedit_type", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditType;

    /**
     * @var integer
     *
     * @Column(name="lastedit_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditUserId;

    /**
     * @var integer
     *
     * @Column(name="to_group_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toGroupId;

    /**
     * @var integer
     *
     * @Column(name="to_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toUserId;

    /**
     * @var boolean
     *
     * @Column(name="visibility", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var \DateTime
     *
     * @Column(name="start_visible", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $startVisible;

    /**
     * @var \DateTime
     *
     * @Column(name="end_visible", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $endVisible;

    /**
     * @var integer
     *
     * @Column(name="id_session", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $idSession;

    /**
     *
     * @ManyToOne(targetEntity="EntityUser")
     * @JoinColumn(name="to_user_id", referencedColumnName="user_id")
     **/
    private $user;


    /**
     * @ManyToOne(targetEntity="EntityCourse")
     * @JoinColumn(name="c_id", referencedColumnName="id")
     */
    private $course;

    public function __construct(EntityUser $user, EntityCourse $course)
    {
        $this->user = $user;
        $this->course = $course;

        $this->setCId($course->getId());
        $this->setToUserId($user->getUserId());
        $this->setInsertUserId(api_get_user_id());
        $this->setLasteditUserId(api_get_user_id());
        $this->setInsertDate(new \DateTime());
        $this->setLasteditDate(new \DateTime());
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCItemProperty
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCItemProperty
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
     * @return EntityCItemProperty
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
     * Set insertUserId
     *
     * @param integer $insertUserId
     * @return EntityCItemProperty
     */
    public function setInsertUserId($insertUserId)
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    /**
     * Get insertUserId
     *
     * @return integer
     */
    public function getInsertUserId()
    {
        return $this->insertUserId;
    }

    /**
     * Set insertDate
     *
     * @param \DateTime $insertDate
     * @return EntityCItemProperty
     */
    public function setInsertDate($insertDate)
    {
        $this->insertDate = $insertDate;

        return $this;
    }

    /**
     * Get insertDate
     *
     * @return \DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * Set lasteditDate
     *
     * @param \DateTime $lasteditDate
     * @return EntityCItemProperty
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate
     *
     * @return \DateTime
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set ref
     *
     * @param integer $ref
     * @return EntityCItemProperty
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

    /**
     * Set lasteditType
     *
     * @param string $lasteditType
     * @return EntityCItemProperty
     */
    public function setLasteditType($lasteditType)
    {
        $this->lasteditType = $lasteditType;

        return $this;
    }

    /**
     * Get lasteditType
     *
     * @return string
     */
    public function getLasteditType()
    {
        return $this->lasteditType;
    }

    /**
     * Set lasteditUserId
     *
     * @param integer $lasteditUserId
     * @return EntityCItemProperty
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId
     *
     * @return integer
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set toGroupId
     *
     * @param integer $toGroupId
     * @return EntityCItemProperty
     */
    public function setToGroupId($toGroupId)
    {
        $this->toGroupId = $toGroupId;

        return $this;
    }

    /**
     * Get toGroupId
     *
     * @return integer
     */
    public function getToGroupId()
    {
        return $this->toGroupId;
    }

    /**
     * Set toUserId
     *
     * @param integer $toUserId
     * @return EntityCItemProperty
     */
    public function setToUserId($toUserId)
    {
        $this->toUserId = $toUserId;

        return $this;
    }

    /**
     * Get toUserId
     *
     * @return integer
     */
    public function getToUserId()
    {
        return $this->toUserId;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     * @return EntityCItemProperty
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set startVisible
     *
     * @param \DateTime $startVisible
     * @return EntityCItemProperty
     */
    public function setStartVisible($startVisible)
    {
        $this->startVisible = $startVisible;

        return $this;
    }

    /**
     * Get startVisible
     *
     * @return \DateTime
     */
    public function getStartVisible()
    {
        return $this->startVisible;
    }

    /**
     * Set endVisible
     *
     * @param \DateTime $endVisible
     * @return EntityCItemProperty
     */
    public function setEndVisible($endVisible)
    {
        $this->endVisible = $endVisible;

        return $this;
    }

    /**
     * Get endVisible
     *
     * @return \DateTime
     */
    public function getEndVisible()
    {
        return $this->endVisible;
    }

    /**
     * Set idSession
     *
     * @param integer $idSession
     * @return EntityCItemProperty
     */
    public function setIdSession($idSession)
    {
        $this->idSession = $idSession;

        return $this;
    }

    /**
     * Get idSession
     *
     * @return integer
     */
    public function getIdSession()
    {
        return $this->idSession;
    }
}
