<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizRelCategory
 *
 * @ORM\Table(name="c_quiz_rel_category")
 * @ORM\Entity
 */
class CQuizRelCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", nullable=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_questions", type="integer", nullable=false)
     */
    private $countQuestions;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
