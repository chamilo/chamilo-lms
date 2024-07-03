<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizQuestion.
 *
 * @ORM\Table(
 *  name="c_quiz_question",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="position", columns={"position"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Entity\Repository\CQuizQuestionRepository")
 */
class CQuizQuestion
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
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @deprecated Now using iid
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="question", type="text", nullable=false)
     */
    protected $question;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var float
     *
     * @ORM\Column(name="ponderation", type="float", precision=6, scale=2, nullable=false, options={"default": 0})
     */
    protected $ponderation;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected $position;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="picture", type="string", length=50, nullable=true)
     */
    protected $picture;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected $level;

    /**
     * @var string
     *
     * @ORM\Column(name="extra", type="string", length=255, nullable=true)
     */
    protected $extra;

    /**
     * @var string
     *
     * @ORM\Column(name="question_code", type="string", length=10, nullable=true)
     */
    protected $questionCode;

    /**
     * CQuizQuestion constructor.
     */
    public function __construct()
    {
        $this->ponderation = 0.0;
    }

    /**
     * Set question.
     *
     * @param string $question
     *
     * @return CQuizQuestion
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CQuizQuestion
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
     * Set ponderation.
     *
     * @param float $ponderation
     *
     * @return CQuizQuestion
     */
    public function setPonderation($ponderation)
    {
        $this->ponderation = $ponderation;

        return $this;
    }

    /**
     * Get ponderation.
     *
     * @return float
     */
    public function getPonderation()
    {
        return $this->ponderation;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return CQuizQuestion
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return CQuizQuestion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set picture.
     *
     * @param string $picture
     *
     * @return CQuizQuestion
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture.
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set level.
     *
     * @param int $level
     *
     * @return CQuizQuestion
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set extra.
     *
     * @param string $extra
     *
     * @return CQuizQuestion
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra.
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set questionCode.
     *
     * @param string $questionCode
     *
     * @return CQuizQuestion
     */
    public function setQuestionCode($questionCode)
    {
        $this->questionCode = $questionCode;

        return $this;
    }

    /**
     * Get questionCode.
     *
     * @return string
     */
    public function getQuestionCode()
    {
        return $this->questionCode;
    }

    /**
     * Set id.
     *
     * @param int $iid
     *
     * @return CQuizQuestion
     *
     * @deprecated Use setIid()
     */
    public function setId($iid)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     *
     * @deprecated Use getIid()
     */
    public function getId()
    {
        return $this->iid;
    }

    /**
     * Set id.
     *
     * @param int $iid
     *
     * @return CQuizQuestion
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * Get id.
     * This method is also used by Twig to get the iid property.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CQuizQuestion
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
