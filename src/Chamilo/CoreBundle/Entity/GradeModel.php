<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradeModel.
 *
 * @ORM\Table(name="grade_model")
 * @ORM\Entity
 */
class GradeModel
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="default_lowest_eval_exclude", type="boolean", nullable=true)
     */
    protected $defaultLowestEvalExclude;

    /**
     * @var bool
     *
     * @ORM\Column(name="default_external_eval", type="boolean", nullable=true)
     */
    protected $defaultExternalEval;

    /**
     * @var string
     *
     * @ORM\Column(name="default_external_eval_prefix", type="string", length=140, nullable=true)
     */
    protected $defaultExternalEvalPrefix;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return GradeModel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return GradeModel
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
     * Set defaultLowestEvalExclude.
     *
     * @param bool $defaultLowestEvalExclude
     *
     * @return GradeModel
     */
    public function setDefaultLowestEvalExclude($defaultLowestEvalExclude)
    {
        $this->defaultLowestEvalExclude = $defaultLowestEvalExclude;

        return $this;
    }

    /**
     * Get defaultLowestEvalExclude.
     *
     * @return bool
     */
    public function getDefaultLowestEvalExclude()
    {
        return $this->defaultLowestEvalExclude;
    }

    /**
     * Set defaultExternalEval.
     *
     * @param bool $defaultExternalEval
     *
     * @return GradeModel
     */
    public function setDefaultExternalEval($defaultExternalEval)
    {
        $this->defaultExternalEval = $defaultExternalEval;

        return $this;
    }

    /**
     * Get defaultExternalEval.
     *
     * @return bool
     */
    public function getDefaultExternalEval()
    {
        return $this->defaultExternalEval;
    }

    /**
     * Set defaultExternalEvalPrefix.
     *
     * @param string $defaultExternalEvalPrefix
     *
     * @return GradeModel
     */
    public function setDefaultExternalEvalPrefix($defaultExternalEvalPrefix)
    {
        $this->defaultExternalEvalPrefix = $defaultExternalEvalPrefix;

        return $this;
    }

    /**
     * Get defaultExternalEvalPrefix.
     *
     * @return string
     */
    public function getDefaultExternalEvalPrefix()
    {
        return $this->defaultExternalEvalPrefix;
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
