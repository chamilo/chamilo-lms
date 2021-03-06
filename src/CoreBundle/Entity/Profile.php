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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Profile
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @return Profile
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;

        return $this;
    }

    public function getLevels()
    {
        return $this->levels;
    }

    /**
     * @return Profile
     */
    public function setLevels($levels)
    {
        $this->levels = $levels;

        return $this;
    }
}
