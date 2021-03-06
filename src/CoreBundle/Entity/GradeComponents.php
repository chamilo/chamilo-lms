<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="percentage", type="string", length=255, nullable=false)
     */
    protected string $percentage;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="acronym", type="string", length=255, nullable=false)
     */
    protected string $acronym;

    /**
     * @ORM\Column(name="grade_model_id", type="integer", nullable=false)
     */
    protected int $gradeModelId;

    /**
     * Set percentage.
     *
     * @return GradeComponents
     */
    public function setPercentage(string $percentage)
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
     * @return GradeComponents
     */
    public function setTitle(string $title)
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
     * @return GradeComponents
     */
    public function setAcronym(string $acronym)
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
     * @return GradeComponents
     */
    public function setGradeModelId(int $gradeModelId)
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
