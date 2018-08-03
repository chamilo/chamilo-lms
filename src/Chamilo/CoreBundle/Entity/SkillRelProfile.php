<?php
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    protected $skillId;

    /**
     * @var int
     *
     * @ORM\Column(name="profile_id", type="integer", nullable=false)
     */
    protected $profileId;

    /**
     * Set skillId.
     *
     * @param int $skillId
     *
     * @return SkillRelProfile
     */
    public function setSkillId($skillId)
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
     * @param int $profileId
     *
     * @return SkillRelProfile
     */
    public function setProfileId($profileId)
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
