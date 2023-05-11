<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'skill_level_profile')]
#[ORM\Entity]
class Profile implements \Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    /**
     * @var Skill[]|Collection
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\Skill::class, mappedBy: 'profile', cascade: ['persist'])]
    protected Collection $skills;

    /**
     *
     * @var Level[]|Collection
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\Level::class, mappedBy: 'profile', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $levels;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->levels = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Skill[]|Collection
     */
    public function getSkills(): array|\Doctrine\Common\Collections\Collection
    {
        return $this->skills;
    }

    /**
     * @param Skill[]|Collection $skills
     */
    public function setSkills(array|\Doctrine\Common\Collections\Collection $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Level[]|Collection
     */
    public function getLevels(): array|\Doctrine\Common\Collections\Collection
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
