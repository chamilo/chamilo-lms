<?php

namespace ChamiloLMS\CoreBundle\Entity;

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
     * @var string
     *
     * @ORM\Column(name="percentage", type="string", length=255, nullable=false)
     */
    private $percentage;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="acronym", type="string", length=255, nullable=false)
     */
    private $acronym;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=255, nullable=true)
     */
    private $prefix;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_elements", type="integer", nullable=true)
     */
    private $countElements;

    /**
     * @var integer
     *
     * @ORM\Column(name="exclusions", type="integer", nullable=true)
     */
    private $exclusions;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_model_id", type="integer", nullable=false)
     */
    private $gradeModelId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
