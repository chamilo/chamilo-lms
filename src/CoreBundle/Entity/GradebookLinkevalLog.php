<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookLinkevalLog.
 *
 * @ORM\Table(name="gradebook_linkeval_log")
 * @ORM\Entity
 */
class GradebookLinkevalLog
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="id_linkeval_log", type="integer", nullable=false)
     */
    protected $idLinkevalLog;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="smallint", nullable=true)
     */
    protected $weight;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=true)
     */
    protected $visible;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    protected $type;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookLinkEvalLogs")
     * @ORM\JoinColumn(name="user_id_log", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * Set idLinkevalLog.
     *
     * @param int $idLinkevalLog
     *
     * @return GradebookLinkevalLog
     */
    public function setIdLinkevalLog($idLinkevalLog)
    {
        $this->idLinkevalLog = $idLinkevalLog;

        return $this;
    }

    /**
     * Get idLinkevalLog.
     *
     * @return int
     */
    public function getIdLinkevalLog()
    {
        return $this->idLinkevalLog;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return GradebookLinkevalLog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return GradebookLinkevalLog
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return GradebookLinkevalLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set weight.
     *
     * @param int $weight
     *
     * @return GradebookLinkevalLog
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     *
     * @return GradebookLinkevalLog
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return GradebookLinkevalLog
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
