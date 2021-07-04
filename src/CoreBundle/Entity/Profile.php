<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Profile.
 *
 * @ORM\Table(name="skill_level_profile")
 * @ORM\Entity
 */
class Profile
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
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Skill", mappedBy="profile", cascade={"persist"})
     *
     * @var Skill[]|Collection
     */
    protected Collection $skills;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Level", mappedBy="profile", cascade={"persist"})
     * @ORM\OrderBy({"position"="ASC"})
     *
     * @var Level[]|Collection
     */
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
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @param Skill[]|Collection $skills
     */
    public function setSkills($skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return Level[]|Collection
     */
    public function getLevels()
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
