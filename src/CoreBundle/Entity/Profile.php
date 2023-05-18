<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'skill_level_profile')]
#[ORM\Entity]
class Profile implements Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    protected string $name;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Skill::class, cascade: ['persist'])]
    protected Collection $skills;

    /**
     * @var Collection<int, Level>
     */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Level::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $levels;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->levels = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Skill[]|Collection
     */
    public function getSkills(): array|Collection
    {
        return $this->skills;
    }

    /**
     * @param Skill[]|Collection $skills
     */
    public function setSkills(array|Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Level[]|Collection
     */
    public function getLevels(): array|Collection
    {
        return $this->levels;
    }

    /**
     * @param Collection $levels
     */
    public function setLevels($levels): self
    {
        $this->levels = $levels;

        return $this;
    }
}
