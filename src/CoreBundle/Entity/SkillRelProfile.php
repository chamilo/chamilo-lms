<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'skill_rel_profile')]
#[ORM\Entity]
class SkillRelProfile
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['skill_profile:write', 'skill_profile:read'])]
    #[ORM\ManyToOne(inversedBy: 'profiles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Skill $skill = null;

    #[ORM\ManyToOne(inversedBy: 'skills')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?SkillProfile $profile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): static
    {
        $this->skill = $skill;

        return $this;
    }

    public function getProfile(): ?SkillProfile
    {
        return $this->profile;
    }

    public function setProfile(?SkillProfile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }
}
