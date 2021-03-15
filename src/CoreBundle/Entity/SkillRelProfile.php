<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelProfile.
 *
 * @ORM\Table(name="skill_rel_profile")
 * @ORM\Entity
 */
class SkillRelProfile
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Skill", cascade={"persist"})
     * @ORM\JoinColumn(name="skill_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Skill $skill;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SkillProfile", cascade={"persist"})
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected SkillProfile $profile;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Skill
     */
    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getProfile(): SkillProfile
    {
        return $this->profile;
    }

    public function setProfile(SkillProfile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }



}
