<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: [
        'groups' => ['skill_profile:read'],
    ],
    denormalizationContext: [
        'groups' => ['skill_profile:write'],
    ],
    paginationEnabled: false,
    security: "is_granted('ROLE_ADMIN')",
)]
#[ORM\Table(name: 'skill_profile')]
#[ORM\Entity]
class SkillProfile
{
    #[Groups(['skill_profile:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['skill_profile:write', 'skill_profile:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Groups(['skill_profile:write', 'skill_profile:read'])]
    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected string $description;

    /**
     * @var Collection<int, SkillRelProfile>
     */
    #[Groups(['skill_profile:write', 'skill_profile:read'])]
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: SkillRelProfile::class, cascade: ['persist'])]
    private Collection $skills;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, SkillRelProfile>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(SkillRelProfile $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setProfile($this);
        }

        return $this;
    }

    public function removeSkill(SkillRelProfile $skill): static
    {
        if ($this->skills->removeElement($skill)) {
            // set the owning side to null (unless already changed)
            if ($skill->getProfile() === $this) {
                $skill->setProfile(null);
            }
        }

        return $this;
    }
}
