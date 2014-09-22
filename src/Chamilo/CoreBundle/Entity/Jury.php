<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Jury
 *
 * @ORM\Table(name="jury")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\JuryRepository")
 */
class Jury
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="branch_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $branchId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="opening_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $openingDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="closure_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $closureDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="opening_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $openingUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="closure_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $closureUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @ORM\OneToMany(targetEntity="JuryMembers", mappedBy="jury")
     * @ORM\OrderBy({"roleId" = "ASC"})
     **/
    private $members;

    /**
     * @ORM\OneToMany(targetEntity="TrackEExercices", mappedBy="attempt")
     **/
    private $exerciseAttempts;

    /**
     * @ORM\ManyToOne(targetEntity="BranchSync")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id")
     */
    private $branch;

    /**
     *
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->exerciseAttempts = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @return ArrayCollection
     */
    public function getExerciseAttempts()
    {
        return $this->exerciseAttempts;
    }

    /**
     * Get branch
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Set branch
     *
     * @param BranchSync $branch
     * @return Jury
     */
    public function setBranch(BranchSync $branch)
    {
        $this->branch = $branch;

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
     * Set name
     *
     * @param string $name
     * @return Jury
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
     * Set branchId
     *
     * @param integer $branchId
     * @return Jury
     */
    public function setBranchId($branchId)
    {
        $this->branchId = $branchId;

        return $this;
    }

    /**
     * Get branchId
     *
     * @return integer
     */
    public function getBranchId()
    {
        return $this->branchId;
    }

    /**
     * Set openingDate
     *
     * @param \DateTime $openingDate
     * @return Jury
     */
    public function setOpeningDate($openingDate)
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    /**
     * Get openingDate
     *
     * @return \DateTime
     */
    public function getOpeningDate()
    {
        return $this->openingDate;
    }

    /**
     * Set closureDate
     *
     * @param \DateTime $closureDate
     * @return Jury
     */
    public function setClosureDate($closureDate)
    {
        $this->closureDate = $closureDate;

        return $this;
    }

    /**
     * Get closureDate
     *
     * @return \DateTime
     */
    public function getClosureDate()
    {
        return $this->closureDate;
    }

    /**
     * Set openingUserId
     *
     * @param integer $openingUserId
     * @return Jury
     */
    public function setOpeningUserId($openingUserId)
    {
        $this->openingUserId = $openingUserId;

        return $this;
    }

    /**
     * Get openingUserId
     *
     * @return integer
     */
    public function getOpeningUserId()
    {
        return $this->openingUserId;
    }

    /**
     * Set closureUserId
     *
     * @param integer $closureUserId
     * @return Jury
     */
    public function setClosureUserId($closureUserId)
    {
        $this->closureUserId = $closureUserId;

        return $this;
    }

    /**
     * Get closureUserId
     *
     * @return integer
     */
    public function getClosureUserId()
    {
        return $this->closureUserId;
    }

    /**
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return Jury
     */
    public function setExerciseId($exerciseId)
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    /**
     * Get exerciseId
     *
     * @return integer
     */
    public function getExerciseId()
    {
        return $this->exerciseId;
    }
}
