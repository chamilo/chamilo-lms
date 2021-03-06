<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="default_lowest_eval_exclude", type="boolean", nullable=true)
     */
    protected ?bool $defaultLowestEvalExclude = null;

    /**
     * @ORM\Column(name="default_external_eval", type="boolean", nullable=true)
     */
    protected ?bool $defaultExternalEval = null;

    /**
     * @ORM\Column(name="default_external_eval_prefix", type="string", length=140, nullable=true)
     */
    protected ?string $defaultExternalEvalPrefix = null;

    /**
     * Set name.
     *
     * @return GradeModel
     */
    public function setName(string $name)
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
     * @return GradeModel
     */
    public function setDescription(string $description)
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
     * @return GradeModel
     */
    public function setDefaultLowestEvalExclude(bool $defaultLowestEvalExclude)
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
     * @return GradeModel
     */
    public function setDefaultExternalEval(bool $defaultExternalEval)
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
     * @return GradeModel
     */
    public function setDefaultExternalEvalPrefix(string $defaultExternalEvalPrefix)
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
