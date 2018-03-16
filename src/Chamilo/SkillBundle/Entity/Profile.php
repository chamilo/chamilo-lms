<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SkillBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Profile.
 *
 * @ORM\Table(
 *  name="skill_level_profile"
 * )
 * @ORM\Entity
 */
class Profile
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Skill", mappedBy="profile", cascade={"persist"})
     */
    protected $skills;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\SkillBundle\Entity\Level", mappedBy="profile", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $levels;

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
     * @param int $id
     *
     * @return Profile
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Profile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @param mixed $skills
     *
     * @return Profile
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevels()
    {
        return $this->levels;
    }

    /**
     * @param mixed $levels
     *
     * @return Profile
     */
    public function setLevels($levels)
    {
        $this->levels = $levels;

        return $this;
    }
}
