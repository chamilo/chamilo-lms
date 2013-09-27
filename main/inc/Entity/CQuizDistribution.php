<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * CQuizDistribution
 *
 * @ORM\Table(name="c_quiz_distribution")
 * @ORM\Entity
 */
class CQuizDistribution
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;


    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var string
     *
     * @ORM\Column(name="data_tracking", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dataTracking;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="author_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_generation_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastGenerationDate;

    /**
     * @ORM\OneToMany(targetEntity="CQuizDistributionQuestions", mappedBy="distribution", cascade={"persist", "remove"} )
     */
    private $questions;

    public function __construct()
    {
        $this->lastGenerationDate = new \DateTime();
        $this->questions = new ArrayCollection();
    }

    public function getQuestions()
    {
        return $this->questions;
    }

    public function setQuestions($questions)
    {
        $this->questions = $questions;
        return $this;
    }

    public function setQuestion(CQuizDistributionQuestions $questions)
    {
        $this->questions->add($questions);
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
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return CQuizDistribution
     */
    public function setExerciseId($exerciseId)
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CQuizDistribution
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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

    /**
     * Set dataTracking
     *
     * @param string $dataTracking
     * @return CQuizDistribution
     */
    public function setDataTracking($dataTracking)
    {
        $this->dataTracking = $dataTracking;

        return $this;
    }

    /**
     * Get dataTracking
     *
     * @return string
     */
    public function getDataTracking()
    {
        return $this->dataTracking;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return CQuizDistribution
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set authorUserId
     *
     * @param integer $authorUserId
     * @return CQuizDistribution
     */
    public function setAuthorUserId($authorUserId)
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    /**
     * Get authorUserId
     *
     * @return integer
     */
    public function getAuthorUserId()
    {
        return $this->authorUserId;
    }

    /**
     * Set lastGenerationDate
     *
     * @param \DateTime $lastGenerationDate
     * @return CQuizDistribution
     */
    public function setLastGenerationDate($lastGenerationDate)
    {
        $this->lastGenerationDate = $lastGenerationDate;

        return $this;
    }

    /**
     * Get lastGenerationDate
     *
     * @return \DateTime
     */
    public function getLastGenerationDate()
    {
        return $this->lastGenerationDate;
    }
}
