<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * CQuizDistributionQuestions
 *
 * @ORM\Table(name="c_quiz_distribution_questions")
 * @ORM\Entity
 */
class CQuizDistributionQuestions
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
     * @var integer
     *
     * @ORM\Column(name="quiz_distribution_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $quizDistributionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionId;

    /**
     * @ORM\ManyToOne(targetEntity="CQuizDistribution")
     * @ORM\JoinColumn(name="quiz_distribution_id", referencedColumnName="id", nullable=true)
     */
    private $distribution;


    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;
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
     * Set quizDistributionId
     *
     * @param integer $quizDistributionId
     * @return CQuizDistributionQuestions
     */
    public function setQuizDistributionId($quizDistributionId)
    {
        $this->quizDistributionId = $quizDistributionId;

        return $this;
    }

    /**
     * Get quizDistributionId
     *
     * @return integer
     */
    public function getQuizDistributionId()
    {
        return $this->quizDistributionId;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return CQuizDistributionQuestions
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return CQuizDistributionQuestions
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }
}
