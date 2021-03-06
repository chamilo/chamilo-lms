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
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected int $skillId;

    /**
     * @ORM\Column(name="profile_id", type="integer", nullable=false)
     */
    protected int $profileId;

    /**
     * Set skillId.
     *
     * @return SkillRelProfile
     */
    public function setSkillId(int $skillId)
    {
        $this->skillId = $skillId;

        return $this;
    }

    /**
     * Get skillId.
     *
     * @return int
     */
    public function getSkillId()
    {
        return $this->skillId;
    }

    /**
     * Set profileId.
     *
     * @return SkillRelProfile
     */
    public function setProfileId(int $profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * Get profileId.
     *
     * @return int
     */
    public function getProfileId()
    {
        return $this->profileId;
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
}
