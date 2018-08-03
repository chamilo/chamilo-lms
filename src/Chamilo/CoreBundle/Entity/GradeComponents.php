<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradeComponents.
 *
 * @ORM\Table(name="grade_components")
 * @ORM\Entity
 */
class GradeComponents
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="percentage", type="string", length=255, nullable=false)
     */
    protected $percentage;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="acronym", type="string", length=255, nullable=false)
     */
    protected $acronym;

    /**
     * @var int
     *
     * @ORM\Column(name="grade_model_id", type="integer", nullable=false)
     */
    protected $gradeModelId;

    /**
     * Set percentage.
     *
     * @param string $percentage
     *
     * @return GradeComponents
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage.
     *
     * @return string
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return GradeComponents
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set acronym.
     *
     * @param string $acronym
     *
     * @return GradeComponents
     */
    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;

        return $this;
    }

    /**
     * Get acronym.
     *
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
    }

    /**
     * Set gradeModelId.
     *
     * @param int $gradeModelId
     *
     * @return GradeComponents
     */
    public function setGradeModelId($gradeModelId)
    {
        $this->gradeModelId = $gradeModelId;

        return $this;
    }

    /**
     * Get gradeModelId.
     *
     * @return int
     */
    public function getGradeModelId()
    {
        return $this->gradeModelId;
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
