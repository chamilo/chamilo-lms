<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="tag")
 * @ORM\Entity
 */
class Tag
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="tag", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $tag;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField", inversedBy="tags")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ExtraField $field;

    /**
     * @ORM\Column(name="count", type="integer", nullable=false)
     */
    protected int $count;

    public function __construct()
    {
        $this->count = 0;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
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

    public function getField(): ExtraField
    {
        return $this->field;
    }

    public function setField(ExtraField $field): self
    {
        $this->field = $field;

        return $this;
    }
}
