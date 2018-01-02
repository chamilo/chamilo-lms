<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelProfile
 *
 * @ORM\Table(name="skill_rel_profile")
 * @ORM\Entity
 */
class SkillRelProfile
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="skill_id", type="integer", nullable=false)
     */
    private $skillId;

    /**
     * @var integer
     *
     * @ORM\Column(name="profile_id", type="integer", nullable=false)
     */
    private $profileId;

    /**
     * Set skillId
     *
     * @param integer $skillId
     * @return SkillRelProfile
     */
    public function setSkillId($skillId)
    {
        $this->skillId = $skillId;

        return $this;
    }

    /**
     * Get skillId
     *
     * @return integer
     */
    public function getSkillId()
    {
        return $this->skillId;
    }

    /**
     * Set profileId
     *
     * @param integer $profileId
     * @return SkillRelProfile
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * Get profileId
     *
     * @return integer
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
