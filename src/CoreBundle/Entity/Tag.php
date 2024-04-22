<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'tag')]
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'tag', type: 'string', length: 255, nullable: false)]
    protected string $tag;

    #[ORM\ManyToOne(targetEntity: ExtraField::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ExtraField $field;

    /**
     * @var Collection<int, UserRelTag>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: UserRelTag::class, cascade: ['persist'])]
    protected Collection $userRelTags;

    /**
     * @var Collection<int, ExtraFieldRelTag>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: ExtraFieldRelTag::class, cascade: ['persist'])]
    protected Collection $extraFieldRelTags;

    #[ORM\Column(name: 'count', type: 'integer', nullable: false)]
    protected int $count;

    public function __construct()
    {
        $this->userRelTags = new ArrayCollection();
        $this->count = 0;
        $this->extraFieldRelTags = new ArrayCollection();
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

    /**
     * @return Collection<int, UserRelTag>
     */
    public function getUserRelTags(): Collection
    {
        return $this->userRelTags;
    }

    public function addUserRelTag(UserRelTag $userRelTag): static
    {
        if (!$this->userRelTags->contains($userRelTag)) {
            $this->userRelTags->add($userRelTag);
            $userRelTag->setTag($this);
        }

        return $this;
    }

    public function removeUserRelTag(UserRelTag $userRelTag): static
    {
        if ($this->userRelTags->removeElement($userRelTag)) {
            // set the owning side to null (unless already changed)
            if ($userRelTag->getTag() === $this) {
                $userRelTag->setTag(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExtraFieldRelTag>
     */
    public function getExtraFieldRelTags(): Collection
    {
        return $this->extraFieldRelTags;
    }

    public function addExtraFieldRelTag(ExtraFieldRelTag $extraFieldRelTag): static
    {
        if (!$this->extraFieldRelTags->contains($extraFieldRelTag)) {
            $this->extraFieldRelTags->add($extraFieldRelTag);
            $extraFieldRelTag->setTag($this);
        }

        return $this;
    }

    public function removeExtraFieldRelTag(ExtraFieldRelTag $extraFieldRelTag): static
    {
        if ($this->extraFieldRelTags->removeElement($extraFieldRelTag)) {
            // set the owning side to null (unless already changed)
            if ($extraFieldRelTag->getTag() === $this) {
                $extraFieldRelTag->setTag(null);
            }
        }

        return $this;
    }
}
