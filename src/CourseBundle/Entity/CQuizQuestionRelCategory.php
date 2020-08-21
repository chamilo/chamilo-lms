<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizQuestionRelCategory.
 *
 * @ORM\Table(name="c_quiz_question_rel_category")
 * @ORM\Entity
 */
class CQuizQuestionRelCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var CQuizQuestion $question
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestion", inversedBy="questionCategories")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid", nullable=false)
     */
    private $question;

    /**
     * @var CQuizQuestionCategory $category
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestionCategory", inversedBy="questionCategories")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid", nullable=false)
     */
    private $category;

    /**
     * @return CQuizQuestion
     */
    public function getQuestion(): self
    {
        return $this->question;
    }

    /**
     * @param CQuizQuestion $question
     *
     * @return CQuizQuestionRelCategory
     */
    public function setQuestion(CQuizQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @return CQuizQuestionCategory
     */
    public function getCategory(): self
    {
        return $this->category;
    }

    /**
     * @param CQuizQuestionCategory $category
     *
     * @return CQuizQuestionRelCategory
     */
    public function setCategory(CQuizQuestionCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
