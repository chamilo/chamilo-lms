<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradeComponents
 *
 * @ORM\Table(name="grade_components")
 * @ORM\Entity
 */
class GradeComponents
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
     * @ORM\Column(name="percentage", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $percentage;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="acronym", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $acronym;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $prefix;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_elements", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $countElements;

    /**
     * @var integer
     *
     * @ORM\Column(name="exclusions", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exclusions;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_model_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $gradeModelId;


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
     * Set percentage
     *
     * @param string $percentage
     * @return GradeComponents
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage
     *
     * @return string
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return GradeComponents
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
     * Set acronym
     *
     * @param string $acronym
     * @return GradeComponents
     */
    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;

        return $this;
    }

    /**
     * Get acronym
     *
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     * @return GradeComponents
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set countElements
     *
     * @param integer $countElements
     * @return GradeComponents
     */
    public function setCountElements($countElements)
    {
        $this->countElements = $countElements;

        return $this;
    }

    /**
     * Get countElements
     *
     * @return integer
     */
    public function getCountElements()
    {
        return $this->countElements;
    }

    /**
     * Set exclusions
     *
     * @param integer $exclusions
     * @return GradeComponents
     */
    public function setExclusions($exclusions)
    {
        $this->exclusions = $exclusions;

        return $this;
    }

    /**
     * Get exclusions
     *
     * @return integer
     */
    public function getExclusions()
    {
        return $this->exclusions;
    }

    /**
     * Set gradeModelId
     *
     * @param integer $gradeModelId
     * @return GradeComponents
     */
    public function setGradeModelId($gradeModelId)
    {
        $this->gradeModelId = $gradeModelId;

        return $this;
    }

    /**
     * Get gradeModelId
     *
     * @return integer
     */
    public function getGradeModelId()
    {
        return $this->gradeModelId;
    }
}
