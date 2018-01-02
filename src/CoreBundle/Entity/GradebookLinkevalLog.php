<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookLinkevalLog
 *
 * @ORM\Table(name="gradebook_linkeval_log")
 * @ORM\Entity
 */
class GradebookLinkevalLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_linkeval_log", type="integer", nullable=false)
     */
    private $idLinkevalLog;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="smallint", nullable=true)
     */
    private $weight;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", nullable=true)
     */
    private $visible;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id_log", type="integer", nullable=false)
     */
    private $userIdLog;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idLinkevalLog
     *
     * @param integer $idLinkevalLog
     * @return GradebookLinkevalLog
     */
    public function setIdLinkevalLog($idLinkevalLog)
    {
        $this->idLinkevalLog = $idLinkevalLog;

        return $this;
    }

    /**
     * Get idLinkevalLog
     *
     * @return integer
     */
    public function getIdLinkevalLog()
    {
        return $this->idLinkevalLog;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return GradebookLinkevalLog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return GradebookLinkevalLog
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return GradebookLinkevalLog
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
     * Set weight
     *
     * @param integer $weight
     * @return GradebookLinkevalLog
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return GradebookLinkevalLog
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return GradebookLinkevalLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set userIdLog
     *
     * @param integer $userIdLog
     * @return GradebookLinkevalLog
     */
    public function setUserIdLog($userIdLog)
    {
        $this->userIdLog = $userIdLog;

        return $this;
    }

    /**
     * Get userIdLog
     *
     * @return integer
     */
    public function getUserIdLog()
    {
        return $this->userIdLog;
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
