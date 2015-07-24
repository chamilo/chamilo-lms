<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

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
     * Set name
     *
     * @param string $name
     * @return GradeModel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return GradeModel
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set defaultLowestEvalExclude
     *
     * @param boolean $defaultLowestEvalExclude
     * @return GradeModel
     */
    public function setDefaultLowestEvalExclude($defaultLowestEvalExclude)
    {
        $this->defaultLowestEvalExclude = $defaultLowestEvalExclude;

        return $this;
    }

    /**
     * Get defaultLowestEvalExclude
     *
     * @return boolean
     */
    public function getDefaultLowestEvalExclude()
    {
        return $this->defaultLowestEvalExclude;
    }

    /**
     * Set defaultExternalEval
     *
     * @param boolean $defaultExternalEval
     * @return GradeModel
     */
    public function setDefaultExternalEval($defaultExternalEval)
    {
        $this->defaultExternalEval = $defaultExternalEval;

        return $this;
    }

    /**
     * Get defaultExternalEval
     *
     * @return boolean
     */
    public function getDefaultExternalEval()
    {
        return $this->defaultExternalEval;
    }

    /**
     * Set defaultExternalEvalPrefix
     *
     * @param string $defaultExternalEvalPrefix
     * @return GradeModel
     */
    public function setDefaultExternalEvalPrefix($defaultExternalEvalPrefix)
    {
        $this->defaultExternalEvalPrefix = $defaultExternalEvalPrefix;

        return $this;
    }

    /**
     * Get defaultExternalEvalPrefix
     *
     * @return string
     */
    public function getDefaultExternalEvalPrefix()
    {
        return $this->defaultExternalEvalPrefix;
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
}
