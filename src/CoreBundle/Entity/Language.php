<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platform languages.
 *
 * @ORM\Table(
 *     name="language",
 *     options={"row_format"="DYNAMIC"}
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\LanguageRepository")
 */
class Language
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="original_name", type="string", length=255, nullable=true)
     */
    #[Assert\NotBlank]
    protected ?string $originalName = null;

    /**
     * @ORM\Column(name="english_name", type="string", length=255)
     */
    #[Assert\NotBlank]
    protected string $englishName;

    /**
     * @ORM\Column(name="isocode", type="string", length=10)
     */
    #[Assert\NotBlank]
    protected string $isocode;

    /**
     * @ORM\Column(name="available", type="boolean", nullable=false)
     */
    protected bool $available;

    /**
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="subLanguages")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected ?Language $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="Language", mappedBy="parent")
     */
    protected Collection $subLanguages;

    public function __construct()
    {
        $this->subLanguages = new ArrayCollection();
        $this->direction = 'rtl';
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setEnglishName(string $englishName): self
    {
        $this->englishName = $englishName;

        return $this;
    }

    public function getEnglishName(): string
    {
        return $this->englishName;
    }

    public function setIsocode(string $isocode): self
    {
        $this->isocode = $isocode;

        return $this;
    }

    public function getIsocode(): string
    {
        return $this->isocode;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    public function getAvailable(): bool
    {
        return $this->available;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getSubLanguages(): Collection
    {
        return $this->subLanguages;
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
