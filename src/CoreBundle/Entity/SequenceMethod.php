<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'sequence_method')]
#[ORM\Entity]
class SequenceMethod
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected string $description;

    #[ORM\Column(name: 'formula', type: 'text')]
    protected string $formula;

    #[ORM\Column(name: 'assign', type: 'integer')]
    protected int $assign;

    #[ORM\Column(name: 'met_type', type: 'string')]
    protected string $metType;

    #[ORM\Column(name: 'act_false', type: 'string')]
    protected string $actFalse;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormula()
    {
        return $this->formula;
    }

    public function setFormula(string $formula): self
    {
        $this->formula = $formula;

        return $this;
    }

    public function getAssign(): int
    {
        return $this->assign;
    }

    public function setAssign(int $assign): self
    {
        $this->assign = $assign;

        return $this;
    }

    public function getMetType(): string
    {
        return $this->metType;
    }

    public function setMetType(string $metType): self
    {
        $this->metType = $metType;

        return $this;
    }

    /**
     * @return string
     */
    public function getActFalse()
    {
        return $this->actFalse;
    }

    public function setActFalse(string $actFalse): self
    {
        $this->actFalse = $actFalse;

        return $this;
    }
}
