<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradeModel
 *
 * @ORM\Table(name="grade_model")
 * @ORM\Entity
 */
class GradeModel
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_lowest_eval_exclude", type="boolean", nullable=true)
     */
    private $defaultLowestEvalExclude;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_external_eval", type="boolean", nullable=true)
     */
    private $defaultExternalEval;

    /**
     * @var string
     *
     * @ORM\Column(name="default_external_eval_prefix", type="string", length=140, nullable=true)
     */
    private $defaultExternalEvalPrefix;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
