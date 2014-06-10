<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyQuestion
 *
 * @ORM\Table(name="c_survey_question")
 * @ORM\Entity
 */
class CSurveyQuestion
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
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    private $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_question", type="text", nullable=false)
     */
    private $surveyQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_question_comment", type="text", nullable=false)
     */
    private $surveyQuestionComment;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=250, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=10, nullable=false)
     */
    private $display;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="shared_question_id", type="integer", nullable=true)
     */
    private $sharedQuestionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_value", type="integer", nullable=true)
     */
    private $maxValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_pri", type="integer", nullable=false)
     */
    private $surveyGroupPri;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_sec1", type="integer", nullable=false)
     */
    private $surveyGroupSec1;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_sec2", type="integer", nullable=false)
     */
    private $surveyGroupSec2;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
