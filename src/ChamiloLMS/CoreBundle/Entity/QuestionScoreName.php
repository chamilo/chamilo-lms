<?php
namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="question_score_name")
 * @ORM\Entity()
 */
class QuestionScoreName
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

     /**
     * @ORM\Column(name="score", type="string", length=255, unique=true)
     */
    private $score;

    /**
     * @ORM\Column(name="description", type="string", length=255, unique=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="QuestionScore", inversedBy="items")
     * @ORM\JoinColumn(name="question_score_id", referencedColumnName="id")
     **/
    private $questionScore;

    public function __construct()
    {
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
     * @return QuestionScoreName
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
     * @return QuestionScore
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
     * Set score id
     *
     * @param string $description
     * @return QuestionScore
     */
    public function setQuestionScore($scoreId)
    {
        $this->questionScore = $scoreId;

        return $this;
    }

    /**
     * Get score id
     *
     * @return string
     */
    public function getQuestionScore()
    {
        return $this->questionScore;
    }

    /**
     * Set score
     *
     * @param string $description
     * @return QuestionScore
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return string
     */
    public function getScore()
    {
        return $this->score;
    }

}
