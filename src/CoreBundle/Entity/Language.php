<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platform languages.
 */
#[ApiResource(paginationEnabled: false)]
#[ApiFilter(BooleanFilter::class, properties: ['available'])]
#[ApiFilter(OrderFilter::class, properties: ['english_name' => 'DESC'])]
#[ORM\Table(name: 'language', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Entity(repositoryClass: LanguageRepository::class)]
class Language
{
    #[Groups(['language:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['language:read', 'language:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'original_name', type: 'string', length: 255, nullable: true)]
    protected ?string $originalName = null;

    #[Groups(['language:read', 'language:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'english_name', type: 'string', length: 255)]
    protected string $englishName;

    #[Groups(['language:read', 'language:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'isocode', type: 'string', length: 10)]
    protected string $isocode;

    #[Groups(['language:read', 'language:write'])]
    #[ORM\Column(name: 'available', type: 'boolean', nullable: false)]
    protected bool $available;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subLanguages')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    protected ?Language $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[Groups(['language:read', 'language:write'])]
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    protected Collection $subLanguages;

    public function __construct()
    {
        $this->subLanguages = new ArrayCollection();
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

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?static
    {
        return $this->parent;
    }

    /**
     * @return Collection<int, self>
     */
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

    public function addSubLanguage(self $subLanguage): static
    {
        if (!$this->subLanguages->contains($subLanguage)) {
            $this->subLanguages->add($subLanguage);
            $subLanguage->setParent($this);
        }

        return $this;
    }

    public function removeSub(self $sub): static
    {
        if ($this->subLanguages->removeElement($sub)) {
            // set the owning side to null (unless already changed)
            if ($sub->getParent() === $this) {
                $sub->setParent(null);
            }
        }

        return $this;
    }
}
